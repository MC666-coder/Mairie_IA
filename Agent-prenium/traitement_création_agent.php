<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php';
require_once __DIR__ . '/../Backend/Config/app.php'; // APP_URL, MAIL_FROM, NOM_COMMUNE (.env)

/* ---------------------------------------------------------
   0) Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   1) Vérification des permissions (avant tout traitement)
   - seul un manager ou un admin connecté peut créer un agent
--------------------------------------------------------- */
$createur_id     = exiger_agent();
$niveau_createur = exiger_niveau(['manager', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    refuser_acces(405, "Méthode non autorisée.");
}

require_once __DIR__ . '/../Backend/Config/db.php'; // Connexion PDO (après le contrôle d'accès)

/* ---------------------------------------------------------
   2) En cas d'erreur : retour au formulaire avec un message rouge
      et les champs déjà remplis (au lieu d'une page blanche)
--------------------------------------------------------- */
function sanitize($str) {
    // Nettoyage uniquement : on stocke/compare la valeur BRUTE.
    // L'échappement HTML se fait à l'affichage (htmlspecialchars), jamais avant la base.
    return trim((string) $str);
}

$nom          = sanitize($_POST['nom_agent'] ?? '');
$prenom       = sanitize($_POST['prenom_agent'] ?? '');
$email        = sanitize($_POST['email_agent'] ?? '');
$service_id   = (int) ($_POST['service_id'] ?? 0);
$role         = sanitize($_POST['role'] ?? '');
$niveau_acces = sanitize($_POST['niveau_acces'] ?? '');

function retourFormulaireAgent(string $erreur): never
{
    global $nom, $prenom, $email, $service_id, $role, $niveau_acces;
    $_SESSION['creation_agent_erreur'] = $erreur;
    $_SESSION['creation_agent_saisie'] = [
        'nom_agent' => $nom, 'prenom_agent' => $prenom, 'email_agent' => $email,
        'service_id' => (string) $service_id, 'role' => $role, 'niveau_acces' => $niveau_acces,
    ];
    header('Location: inscription_agent.php');
    exit;
}

/* ---------------------------------------------------------
   3) Vérification CSRF
--------------------------------------------------------- */
if (!isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // nouveau jeton pour le prochain essai
    retourFormulaireAgent('Le formulaire a expiré. Vérifiez les informations puis recommencez.');
}

/* ---------------------------------------------------------
   4) Protection anti-bruteforce (10 essais par session)
--------------------------------------------------------- */
$_SESSION['creation_attempts'] = ($_SESSION['creation_attempts'] ?? 0) + 1;
if ($_SESSION['creation_attempts'] > 10) {
    retourFormulaireAgent('Trop de tentatives. Reconnectez-vous avant de créer un nouvel agent.');
}

/* ---------------------------------------------------------
   5) Validation stricte des données
--------------------------------------------------------- */
if (mb_strlen($nom, 'UTF-8') < 2 || mb_strlen($prenom, 'UTF-8') < 2
    || mb_strlen($nom, 'UTF-8') > 50 || mb_strlen($prenom, 'UTF-8') > 50) {
    retourFormulaireAgent('Le nom et le prénom doivent contenir entre 2 et 50 caractères.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    retourFormulaireAgent('L’adresse e-mail n’est pas valide. Exemple : prenom.nom@mairie.fr');
}

$roles_valides = ['agent', 'responsable'];
if (!in_array($role, $roles_valides, true)) {
    retourFormulaireAgent('Choisissez un rôle : agent ou responsable.');
}

$acces_valides = ['agent', 'manager', 'admin'];
if (!in_array($niveau_acces, $acces_valides, true)) {
    retourFormulaireAgent('Choisissez un niveau d’accès.');
}

// Un manager ne peut pas créer d'administrateur (anti-élévation de privilèges)
if ($niveau_acces === 'admin' && $niveau_createur !== 'admin') {
    refuser_acces(403, "Seul un administrateur peut créer un compte administrateur.");
}

/* ---------------------------------------------------------
   6) Vérifier si le service existe, puis si l'e-mail est libre
--------------------------------------------------------- */
$check_service = $pdo->prepare("SELECT id FROM services WHERE id = ?");
$check_service->execute([$service_id]);
if ($check_service->fetchColumn() === false) {
    retourFormulaireAgent('Choisissez le service de l’agent dans la liste.');
}

$check = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
$check->execute([$email]);
if ($check->fetchColumn() !== false) {
    retourFormulaireAgent('Un agent utilise déjà cette adresse e-mail.');
}

/* ---------------------------------------------------------
   7) Génération du token d’activation + expiration
--------------------------------------------------------- */
$token = bin2hex(random_bytes(32));
$expiration = date("Y-m-d H:i:s", time() + 86400); // 24h

/* ---------------------------------------------------------
   8) Insertion de l’agent en base
--------------------------------------------------------- */
$sql = $pdo->prepare("
    INSERT INTO agents 
    (nom, prenom, email, mot_de_passe, doit_definir_mot_de_passe, token_activation, token_expiration, service_id, role, niveau_acces, statut)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'inactif')
");

$placeholder_password = password_hash("TEMP", PASSWORD_DEFAULT);

$sql->execute([
    $nom,
    $prenom,
    $email,
    $placeholder_password,
    1,
    $token,
    $expiration,
    $service_id,
    $role,
    $niveau_acces
]);

/* ---------------------------------------------------------
   8.1) Génération automatique de l’identifiant agent
--------------------------------------------------------- */
$agent_id = $pdo->lastInsertId(); // ID interne auto-incrémenté

// Fonction à placer en haut du fichier
function generateIdentifiantAgent($id) {
    $year = date("Y");
    return "AGT-$year-" . str_pad($id, 6, "0", STR_PAD_LEFT);
}

$identifiant = generateIdentifiantAgent($agent_id);

$update = $pdo->prepare("UPDATE agents SET identifiant_agent = ? WHERE id = ?");
$update->execute([$identifiant, $agent_id]);


/* ---------------------------------------------------------
   9) Envoi de l’email d’activation (sécurisé)
--------------------------------------------------------- */
// Adresse du site lue dans .env (APP_URL) : le lien fonctionne en local ET en ligne
$activation_link = url_app("Agent-prenium/activation.php?token=" . urlencode($token));
$commune = NOM_COMMUNE;

$subject = mb_encode_mimeheader("Activation de votre compte agent - $commune", 'UTF-8');

$message = "
Bonjour $prenom $nom,

Votre compte agent a été créé par votre responsable.

Pour activer votre compte et définir votre mot de passe, cliquez sur le lien suivant :

$activation_link

Ce lien est valable 24 heures.

Cordialement,
$commune
";

$headers = entetes_email(); // expéditeur lu dans .env (MAIL_FROM, MAIL_FROM_NAME)

// En local (sans serveur SMTP), l'envoi échoue : on journalise au lieu d'afficher un warning
if (!@mail($email, $subject, $message, $headers)) {
    error_log("Email d'activation non envoyé à $email (SMTP indisponible)");
}

/* ---------------------------------------------------------
   10) Log de sécurité
--------------------------------------------------------- */
journal_securite('creation_agents.log', "Agent créé : $email (niveau $niveau_acces) par agent ID " . (int) $createur_id);

/* ---------------------------------------------------------
   11) Redirection
--------------------------------------------------------- */
$_SESSION['creation_attempts'] = 0; // création réussie : le compteur d'essais repart de zéro
header("Location: confirmation_creation_agent.php");
exit;
