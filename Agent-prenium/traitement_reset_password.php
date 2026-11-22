<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/auth_agent.php'; // gabarit « écran partagé » (agent.css)
require_once __DIR__ . '/../Backend/Config/db.php';

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
if (!isset($_POST['csrf_reset']) ||
    !isset($_SESSION['csrf_reset']) ||
    !hash_equals($_SESSION['csrf_reset'], $_POST['csrf_reset'])) {
    pageMessageAgent('Formulaire expiré', "La page est restée ouverte trop longtemps.\nRouvrez le lien reçu par e-mail pour recommencer.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   2) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['reset_submit_attempts'])) {
    $_SESSION['reset_submit_attempts'] = 0;
}
$_SESSION['reset_submit_attempts']++;

if ($_SESSION['reset_submit_attempts'] > 10) {
    pageMessageAgent('Trop de tentatives', "Vous avez fait trop d’essais.\nPatientez quelques minutes avant de réessayer.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   3) Vérification du token
--------------------------------------------------------- */
if (!isset($_POST['token']) || strlen($_POST['token']) !== 64) {
    pageMessageAgent('Lien invalide', "Ce lien n’est pas valide ou a déjà été utilisé.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

$token = $_POST['token'];

/* ---------------------------------------------------------
   4) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, reset_expiration FROM agents WHERE token_reset = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    pageMessageAgent('Lien invalide', "Ce lien n’est pas valide ou a déjà été utilisé.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   5) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['reset_expiration']) < time()) {
    pageMessageAgent('Lien expiré', "Ce lien de réinitialisation a expiré (il est valable 24 heures).
Refaites une demande pour en recevoir un nouveau.", 'mot_de_passe_oublie.php', 'Refaire une demande', 'erreur');
}


/* ---------------------------------------------------------
   6) Vérification du mot de passe
--------------------------------------------------------- */
$password = $_POST['password'] ?? "";
$password_confirm = $_POST['password_confirm'] ?? "";

if ($password !== $password_confirm) {
    $_SESSION['reset_erreur'] = 'Les deux mots de passe ne correspondent pas.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}

if (
    strlen($password) < 10 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[\W]/', $password)
) {
    $_SESSION['reset_erreur'] = 'Le mot de passe ne respecte pas toutes les règles indiquées.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}

/* ---------------------------------------------------------
   7) Hash du mot de passe
--------------------------------------------------------- */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/* ---------------------------------------------------------
   8) Mise à jour du compte : activation + suppression token
--------------------------------------------------------- */
$update = $pdo->prepare("
    UPDATE agents 
    SET mot_de_passe = ?, 
        doit_definir_mot_de_passe = 0,
        token_reset = NULL,
        reset_expiration = NULL
    WHERE id = ?
");


$update->execute([
    $hashed_password,
    $agent['id']
]);

/* ---------------------------------------------------------
   9) Log de sécurité
--------------------------------------------------------- */
require_once __DIR__ . '/../Backend/Config/app.php';
journal_securite('reset_password.log', "Réinitialisation du mot de passe : Agent ID " . (int) $agent['id']);

/* ---------------------------------------------------------
   10) Nettoyage des tokens de session
--------------------------------------------------------- */
unset($_SESSION['csrf_reset']);
unset($_SESSION['reset_attempts']);
unset($_SESSION['reset_submit_attempts']);

/* ---------------------------------------------------------
   11) Redirection vers confirmation
--------------------------------------------------------- */
header("Location: confirmation_reset.php");
exit;
?>
