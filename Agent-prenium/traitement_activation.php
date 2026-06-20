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

/* ---------------------------------------------------------
   1) Vérification CSRF
--------------------------------------------------------- */
if (!isset($_POST['csrf_token_activation']) ||
    !isset($_SESSION['csrf_token_activation']) ||
    !hash_equals($_SESSION['csrf_token_activation'], $_POST['csrf_token_activation'])) {
    die("Requête non autorisée.");
}

/* ---------------------------------------------------------
   2) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['activation_submit_attempts'])) {
    $_SESSION['activation_submit_attempts'] = 0;
}
$_SESSION['activation_submit_attempts']++;

if ($_SESSION['activation_submit_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   3) Vérification du token
--------------------------------------------------------- */
if (!isset($_POST['token']) || strlen($_POST['token']) !== 64) {
    die("Lien invalide.");
}

$token = $_POST['token'];

/* ---------------------------------------------------------
   4) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, token_expiration FROM agents WHERE token_activation = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    die("Lien invalide.");
}

/* ---------------------------------------------------------
   5) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['token_expiration']) < time()) {
    die("Ce lien d’activation a expiré. Contactez votre responsable.");
}

/* ---------------------------------------------------------
   6) Récupération + validation du mot de passe
--------------------------------------------------------- */
$password = $_POST['password'] ?? "";
$password_confirm = $_POST['password_confirm'] ?? "";

// Vérification correspondance
if ($password !== $password_confirm) {
    die("Les mots de passe ne correspondent pas.");
}

// Vérification force du mot de passe
if (
    strlen($password) < 10 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[\W]/', $password)
) {
    die("Mot de passe trop faible.");
}

/* ---------------------------------------------------------
   7) Hash du mot de passe
--------------------------------------------------------- */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/* ---------------------------------------------------------
   8) Mise à jour du compte : activation
--------------------------------------------------------- */
$update = $pdo->prepare("
    UPDATE agents 
    SET mot_de_passe = ?, 
        doit_definir_mot_de_passe = 0,
        token_activation = NULL,
        token_expiration = NULL,
        statut = 'actif'
    WHERE id = ?
");

$update->execute([
    $hashed_password,
    $agent['id']
]);

/* ---------------------------------------------------------
   9) Log de sécurité
--------------------------------------------------------- */
file_put_contents("logs/activation.log",
    date("Y-m-d H:i:s") . " - Activation du compte agent ID " . $agent['id'] . "\n",
    FILE_APPEND
);

/* ---------------------------------------------------------
   10) Suppression du token CSRF
--------------------------------------------------------- */
unset($_SESSION['csrf_token_activation']);

/* ---------------------------------------------------------
   11) Redirection vers page de succès
--------------------------------------------------------- */
header("Location: confirmation_activation.php");
exit;
?>
