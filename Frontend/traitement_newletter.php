<?php
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Newsletter.php?newsletter_error=method");
    exit();
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    session_destroy();
    header("Location: Newsletter.php?newsletter_error=csrf");
    exit();
}

// Anti-spam simple : 1 soumission toutes les 10 secondes
if (!isset($_SESSION['last_newsletter_submit'])) {
    $_SESSION['last_newsletter_submit'] = 0;
}
if (time() - $_SESSION['last_newsletter_submit'] < 10) {
    header("Location: Newsletter.php?newsletter_error=too_fast");
    exit();
}
$_SESSION['last_newsletter_submit'] = time();

// Nettoyage des données
$nom = trim($_POST['nom_complet'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

// Vérification des champs
if ($nom === '' || !$email) {
    header("Location: Newsletter.php?newsletter_error=invalid_fields");
    exit();
}

// Vérification email déjà inscrit
$checkEmail = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
$checkEmail->execute([$email]);

if ($checkEmail->rowCount() > 0) {
    header("Location: Newsletter.php?newsletter_error=email_exists");
    exit();
}

// Insertion sécurisée
$insert = $pdo->prepare("
    INSERT INTO newsletter_subscribers (nom_complet, email, created_at)
    VALUES (?, ?, NOW())
");
$insert->execute([$nom, $email]);

// Succès → popup dans index.php
$_SESSION['newsletter_success'] = true;
header("Location: index.php");
exit();
?>
