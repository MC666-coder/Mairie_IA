<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Config/db.php';

/* ---------------------------------------------------------
   0) Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
// Ne jamais journaliser $_POST ni $_SESSION : ils contiennent le mot de passe et les jetons CSRF

/* Retour au formulaire avec un message rouge dans la carte (et l'adresse déjà saisie) */
function erreurConnexion(string $message, string $email = ''): void
{
    $_SESSION['login_error_agent'] = $message;
    $_SESSION['login_email_agent'] = $email;
    header("Location: connexion_agent.php");
    exit;
}

/* ---------------------------------------------------------
   1) Vérification CSRF
--------------------------------------------------------- */
if (!isset($_POST['csrf_login']) ||
    !isset($_SESSION['csrf_login']) ||
    !hash_equals($_SESSION['csrf_login'], $_POST['csrf_login'])) {
    erreurConnexion("La page a expiré. Merci de réessayer.");
}

/* ---------------------------------------------------------
   2) Protection anti-bruteforce : 10 essais, puis 15 minutes d'attente
--------------------------------------------------------- */
if (empty($_SESSION['login_debut']) || time() - $_SESSION['login_debut'] > 900) {
    $_SESSION['login_debut'] = time();
    $_SESSION['login_attempts'] = 0;
}
$_SESSION['login_attempts']++;

if ($_SESSION['login_attempts'] > 10) {
    erreurConnexion("Trop de tentatives. Réessayez dans quelques minutes.");
}

/* ---------------------------------------------------------
   3) Récupération + sanitation des données
--------------------------------------------------------- */
function sanitize($str) {
    // Nettoyage uniquement : on stocke/compare la valeur BRUTE.
    // L'échappement HTML se fait à l'affichage (htmlspecialchars), jamais avant la base.
    return trim((string) $str);
}

$email = sanitize($_POST['email'] ?? "");
$password = $_POST['password'] ?? "";

/* ---------------------------------------------------------
   4) Vérification email valide
--------------------------------------------------------- */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    erreurConnexion("L'adresse e-mail n'est pas valide.", $email);
}

/* ---------------------------------------------------------
   5) Vérification de l'agent et du mot de passe
   Anti-énumération : on ne doit pas pouvoir deviner quelles adresses
   sont des comptes agents. Donc :
   - un seul message pour « adresse inconnue » et « mauvais mot de passe » ;
   - le mot de passe est TOUJOURS vérifié (avec un hachage factice si
     l'adresse est inconnue) : le temps de réponse est le même ;
   - « compte pas encore activé » n'est dit qu'à celui qui a donné le bon
     mot de passe (il prouve alors que c'est bien son compte).
--------------------------------------------------------- */
const MESSAGE_IDENTIFIANTS = "Adresse e-mail ou mot de passe incorrect.";
// Vrai hachage bcrypt (coût 10, comme les mots de passe des agents) d'un mot de passe
// aléatoire que personne ne connaît : sa vérification prend le même temps qu'une vraie
const HACHAGE_FACTICE = '$2y$10$MzBlAb1zTuclB1UeZGAwqeZ7X/7Fja/XD5btCq.wEPCkh5pK/Fcga';

$sql = $pdo->prepare("SELECT * FROM agents WHERE email = ?");
$sql->execute([$email]);
$agent = $sql->fetch();

$motDePasseCorrect = password_verify($password, $agent ? (string) $agent['mot_de_passe'] : HACHAGE_FACTICE);

if (!$agent || !$motDePasseCorrect) {
    require_once __DIR__ . '/../Backend/Config/app.php';
    journal_securite('connexion.log', 'Échec de connexion agent'); // sans l'adresse ni le mot de passe
    erreurConnexion(MESSAGE_IDENTIFIANTS, $email);
}

/* ---------------------------------------------------------
   6) Compte pas encore activé (seulement après le bon mot de passe)
--------------------------------------------------------- */
if ($agent['statut'] !== 'actif' || (int) $agent['doit_definir_mot_de_passe'] === 1) {
    erreurConnexion("Votre compte n'est pas encore activé : cliquez sur le lien reçu par e-mail. Sans e-mail, contactez votre responsable.", $email);
}

/* ---------------------------------------------------------
   9) Réinitialisation des tentatives
--------------------------------------------------------- */
$_SESSION['login_attempts'] = 0;
unset($_SESSION['login_email_agent']);

/* ---------------------------------------------------------
   10) Création d’une session sécurisée
--------------------------------------------------------- */
session_regenerate_id(true);

$_SESSION['agent_id'] = $agent['id'];
$_SESSION['nom'] = $agent['nom'];
$_SESSION['prenom'] = $agent['prenom'];
$_SESSION['service_id'] = $agent['service_id'];
$_SESSION['agent_role'] = $agent['role'];
$_SESSION['niveau_acces'] = $agent['niveau_acces'];

/* ---------------------------------------------------------
   11) Log de sécurité
--------------------------------------------------------- */
require_once __DIR__ . '/../Backend/Config/app.php';
journal_securite('connexion.log', "Connexion réussie : Agent ID " . (int) $agent['id']);

/* ---------------------------------------------------------
   12) Redirection vers le dashboard
--------------------------------------------------------- */
header("Location: dashboard_agent.php");
exit;
?>
