<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';



// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: connexion_citoyen.php");
    exit;
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    session_destroy();
    die("Requête invalide.");
}

// Nettoyage des données
$email_citoyen = filter_var($_POST['email_citoyen'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email_citoyen || empty($password)) {
    die("Veuillez remplir tous les champs.");
}

// Prévention bruteforce (5 tentatives / 10 minutes)
if (!isset($_SESSION['login_attempts_citoyen'])) {
    $_SESSION['login_attempts_citoyen'] = 0;
    $_SESSION['first_attempt_time_citoyen'] = time();
}

if ($_SESSION['login_attempts_citoyen'] >= 5) {
    if (time() - $_SESSION['first_attempt_time_citoyen'] < 600) {
        die("Trop de tentatives. Réessayez dans quelques minutes.");
    } else {
        $_SESSION['login_attempts_citoyen'] = 0;
        $_SESSION['first_attempt_time_citoyen'] = time();
    }
}

// Requête SQL sécurisée
$stmt = $pdo->prepare("
    SELECT id, nom_citoyen, prenom_citoyen, email_citoyen, password
    FROM citoyens
    WHERE email_citoyen = ?
    LIMIT 1
");
$stmt->execute([$email_citoyen]);
$citoyen = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérification du mot de passe
if ($citoyen && password_verify($password, $citoyen['password'])) {

    // Reset bruteforce
    $_SESSION['login_attempts_citoyen'] = 0;

    // Regénération de l’ID de session
    session_regenerate_id(true);

    // Stockage des infos citoyen
    $_SESSION['id'] = $citoyen['id'];
    $_SESSION['nom_citoyen'] = $citoyen['nom_citoyen'];
    $_SESSION['prenom_citoyen'] = $citoyen['prenom_citoyen'];
    $_SESSION['email_citoyen'] = $citoyen['email_citoyen'];

    header('Location: Portail_citoyen.php');
    exit();

} else {

    $_SESSION['login_attempts_citoyen']++;

    $_SESSION['login_error'] = "Email ou mot de passe incorrect.";
    header("Location: connexion_citoyen.php");
    exit;

}
?>