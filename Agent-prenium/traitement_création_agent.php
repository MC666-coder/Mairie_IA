<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Config/db.php'; // Connexion PDO

/* ---------------------------------------------------------
   0) Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   1) Vérification CSRF
--------------------------------------------------------- */
if (!isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Requête non autorisée.");
}

/* ---------------------------------------------------------
   2) Vérification des permissions
--------------------------------------------------------- */
/*if (!isset($_SESSION['niveau_acces']) || !in_array($_SESSION['niveau_acces'], ['manager', 'admin'])) {
    die("Accès refusé.");
}*/

/* ---------------------------------------------------------
   3) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['creation_attempts'])) {
    $_SESSION['creation_attempts'] = 0;
}
$_SESSION['creation_attempts']++;

if ($_SESSION['creation_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   4) Récupération + validation stricte des données
--------------------------------------------------------- */
function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

$nom = sanitize($_POST['nom_agent']);
$prenom = sanitize($_POST['prenom_agent']);
$email = sanitize($_POST['email_agent']);
$service_id = intval($_POST['service_id']);
$role = sanitize($_POST['role']);
$niveau_acces = sanitize($_POST['niveau_acces']);

if (strlen($nom) < 2 || strlen($prenom) < 2) {
    die("Nom ou prénom invalide.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Adresse email invalide.");
}

$roles_valides = ['agent', 'responsable'];
if (!in_array($role, $roles_valides)) {
    die("Rôle invalide.");
}

$acces_valides = ['agent', 'manager', 'admin'];
if (!in_array($niveau_acces, $acces_valides)) {
    die("Niveau d'accès invalide.");
}

/* ---------------------------------------------------------
   5) Vérifier si le service existe
--------------------------------------------------------- */
$check_service = $pdo->prepare("SELECT id FROM services WHERE id = ?");
$check_service->execute([$service_id]);

if ($check_service->rowCount() === 0) {
    die("Service inexistant.");
}

/* ---------------------------------------------------------
   6) Vérifier si l'email existe déjà
--------------------------------------------------------- */
$check = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
    die("Un agent avec cet email existe déjà.");
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
$activation_link = "http://localhost/Site_officiel/Agent-prenium/activation.php?token=" . urlencode($token);

$subject = "Activation de votre compte agent - Commune de L’Aiguillon-sur-Vie";

$message = "
Bonjour $prenom $nom,

Votre compte agent a été créé par votre responsable.

Pour activer votre compte et définir votre mot de passe, cliquez sur le lien suivant :

$activation_link

Ce lien est valable 24 heures.

Cordialement,
Commune de L’Aiguillon-sur-Vie
";

$headers = "From: mairie@localhost\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

mail($email, $subject, $message, $headers);

/* ---------------------------------------------------------
   10) Log de sécurité
--------------------------------------------------------- */
file_put_contents("logs/creation_agents.log", date("Y-m-d H:i:s") . " - Agent créé : $email par ID " . $_SESSION['id'] . "\n", FILE_APPEND);

/* ---------------------------------------------------------
   11) Redirection
--------------------------------------------------------- */
header("Location: confirmation_creation_agent.php");
exit;
?>
