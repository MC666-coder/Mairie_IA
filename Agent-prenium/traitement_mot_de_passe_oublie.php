<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Config/app.php'; // APP_URL, MAIL_FROM, NOM_COMMUNE (.env)

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
if (!isset($_POST['csrf_forgot']) ||
   !isset($_SESSION['csrf_forgot']) ||
   !hash_equals($_SESSION['csrf_forgot'], $_POST['csrf_forgot'])) {
    die("Requête non autorisée.");
}

/* ---------------------------------------------------------
   2) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = 0;
}
$_SESSION['forgot_attempts']++;

if ($_SESSION['forgot_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   3) Récupération + sanitation de l’email
--------------------------------------------------------- */
function sanitize($str) {
    // Nettoyage uniquement : on stocke/compare la valeur BRUTE.
    // L'échappement HTML se fait à l'affichage (htmlspecialchars), jamais avant la base.
    return trim((string) $str);
}

$email = sanitize($_POST['email'] ?? "");

/* ---------------------------------------------------------
   4) Vérification email valide
--------------------------------------------------------- */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_forgot'] = "Veuillez entrer une adresse email valide.";
    header("Location:mot_de_passe_oublie.php");
    exit;
}

/* ---------------------------------------------------------
   5) Vérification si l’email existe
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
$sql->execute([$email]);
$agent = $sql->fetch();

/* ---------------------------------------------------------
   6) Si l’email n’existe pas → même comportement
--------------------------------------------------------- */
if (!$agent) {
    $_SESSION['error_forgot'] = "L’adresse email n’existe pas dans notre système.";
    header("Location:mot_de_passe_oublie.php");
    exit;
}

/* ---------------------------------------------------------
   7) Génération du token de réinitialisation
--------------------------------------------------------- */
$token = bin2hex(random_bytes(32));
$expiration = date("Y-m-d H:i:s", time() + 86400); // 24h

/* ---------------------------------------------------------
   8) Enregistrement du token dans les colonnes dédiées
--------------------------------------------------------- */
$update = $pdo->prepare("
    UPDATE agents 
    SET token_reset = ?, reset_expiration = ?
    WHERE id = ?
");
$update->execute([$token, $expiration, $agent['id']]);

/* ---------------------------------------------------------
   9) Envoi de l’email de réinitialisation
--------------------------------------------------------- */
// Adresse du site lue dans .env (APP_URL) : le lien fonctionne en local ET en ligne
$reset_link = url_app("Agent-prenium/reset_password.php?token=" . urlencode($token));
$commune = NOM_COMMUNE;

$subject = mb_encode_mimeheader("Réinitialisation de votre mot de passe - Portail Agent", 'UTF-8');

$message = "
Bonjour,

Une demande de réinitialisation de mot de passe a été effectuée pour votre compte.

Si vous êtes à l'origine de cette demande, cliquez sur le lien suivant :

$reset_link

Ce lien est valable 24 heures.

Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.

Cordialement,
$commune
";

$headers = entetes_email(); // expéditeur lu dans .env (MAIL_FROM, MAIL_FROM_NAME)

// En local (sans serveur SMTP), l'envoi échoue : on journalise au lieu d'afficher un warning
if (!@mail($email, $subject, $message, $headers)) {
    error_log("Email de réinitialisation non envoyé (SMTP indisponible)");
}

/* ---------------------------------------------------------
   10) Log de sécurité
--------------------------------------------------------- */
journal_securite('reset_request.log', "Demande de reset pour email : $email");

/* ---------------------------------------------------------
   11) Redirection vers confirmation
--------------------------------------------------------- */
header("Location:confirmation_mot_de_passe_oublie.php");
exit;
?>
