<?php
/* =========================================================
   Mot de passe oublié (citoyens) : enregistrement du nouveau mot de passe
   ========================================================= */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/email_citoyen.php';
require_once __DIR__ . '/../Backend/Fonctions/regles_mot_de_passe.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Requête invalide.");
}

$jeton = (string) ($_POST['token'] ?? '');
$empreinte = empreinteJeton($jeton);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$retour = 'Location: nouveau_mot_de_passe_citoyen.php?token=' . urlencode($jeton);

// Jeton encore valable ?
$citoyen = false;
if ($empreinte !== null) {
    $requete = $pdo->prepare("SELECT id, reset_expiration FROM citoyens WHERE token_reset = ? LIMIT 1");
    $requete->execute([$empreinte]);
    $citoyen = $requete->fetch(PDO::FETCH_ASSOC);
}
if (!$citoyen || strtotime($citoyen['reset_expiration']) < time()) {
    header($retour); // la page affiche « lien invalide ou expiré »
    exit;
}

$reglesManquantes = erreursMotDePasse($password);
if ($reglesManquantes) {
    $_SESSION['nouveau_mdp_error'] = "Mot de passe trop faible. Il manque : " . implode(', ', $reglesManquantes) . ".";
    header($retour);
    exit;
}
if ($password !== $password_confirm) {
    $_SESSION['nouveau_mdp_error'] = "Les deux mots de passe ne correspondent pas.";
    header($retour);
    exit;
}

// Nouveau mot de passe ; le lien ne peut plus resservir
$pdo->prepare("
    UPDATE citoyens
    SET password = ?, token_reset = NULL, reset_expiration = NULL
    WHERE id = ?
")->execute([password_hash($password, PASSWORD_DEFAULT), $citoyen['id']]);

$_SESSION['login_success'] = "Votre mot de passe a été modifié. Vous pouvez vous connecter.";
header('Location: connexion_citoyen.php');
exit;
