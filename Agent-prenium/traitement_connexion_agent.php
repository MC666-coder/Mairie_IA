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
/* ---------------------------------------------------------
   1) Vérification CSRF
--------------------------------------------------------- */
if (!isset($_POST['csrf_login']) ||
    !isset($_SESSION['csrf_login']) ||
    !hash_equals($_SESSION['csrf_login'], $_POST['csrf_login'])) {
    die("Requête non autorisée.");
}

/* ---------------------------------------------------------
   2) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
$_SESSION['login_attempts']++;

if ($_SESSION['login_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
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
    die("Identifiants incorrects.");
}

/* ---------------------------------------------------------
   5) Récupération de l’agent
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT * FROM agents WHERE email = ?");
$sql->execute([$email]);
$agent = $sql->fetch();

if (!$agent) {
    // Pas d’info précise pour éviter fuite
    die("Identifiants incorrects.");
}

/* ---------------------------------------------------------
   6) Vérification statut du compte
--------------------------------------------------------- */
if ($agent['statut'] !== 'actif') {
    die("Votre compte n'est pas encore activé.");
}

/* ---------------------------------------------------------
   7) Vérification que l’agent a défini son mot de passe
--------------------------------------------------------- */
if ($agent['doit_definir_mot_de_passe'] == 1) {
    die("Votre compte n'est pas encore activé. Vérifiez vos emails.");
}

/* ---------------------------------------------------------
   8) Vérification du mot de passe
--------------------------------------------------------- */
if (!password_verify($password, $agent['mot_de_passe'])) {
    die("Identifiants incorrects.");
}

/* ---------------------------------------------------------
   9) Réinitialisation des tentatives
--------------------------------------------------------- */
$_SESSION['login_attempts'] = 0;

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
