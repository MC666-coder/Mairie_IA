<?php
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';

/** Retour à la page Newsletter avec un message rouge, la saisie conservée. */
function retourNewsletter(string $code, array $saisie = []): never
{
    $_SESSION['newsletter_saisie'] = $saisie;
    header('Location: Newsletter.php?newsletter_error=' . $code);
    exit();
}

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Newsletter.php');
    exit();
}

// Nettoyage des données
$nom   = trim((string) ($_POST['nom_complet'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$saisie = ['nom' => mb_substr($nom, 0, 50, 'UTF-8'), 'email' => mb_substr($email, 0, 150, 'UTF-8')];

// Vérification CSRF (sans détruire la session : un citoyen connecté resterait sinon déconnecté)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    retourNewsletter('csrf', $saisie);
}

// Vérification des champs (longueurs de la table newsletter_subscribers)
if ($nom === '' || mb_strlen($nom, 'UTF-8') > 50) {
    retourNewsletter('nom', $saisie);
}
if (mb_strlen($email, 'UTF-8') > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    retourNewsletter('email', $saisie);
}

// Anti-spam simple : 1 inscription toutes les 10 secondes
// (après la validation : une faute de frappe ne bloque pas le nouvel essai)
if (time() - (int) ($_SESSION['last_newsletter_submit'] ?? 0) < 10) {
    retourNewsletter('too_fast', $saisie);
}
$_SESSION['last_newsletter_submit'] = time();

// Inscription, seulement si l'adresse n'est pas déjà abonnée.
// Même réponse dans les deux cas : la page ne révèle pas qui est inscrit.
$checkEmail = $pdo->prepare("SELECT 1 FROM newsletter_subscribers WHERE email = ?");
$checkEmail->execute([$email]);

if ($checkEmail->fetchColumn() === false) {
    $insert = $pdo->prepare("
        INSERT INTO newsletter_subscribers (nom_complet, email, created_at)
        VALUES (?, ?, NOW())
    ");
    $insert->execute([$nom, $email]);
}

// Succès : message vert sur la page Newsletter
header('Location: Newsletter.php?inscription=ok');
exit();
