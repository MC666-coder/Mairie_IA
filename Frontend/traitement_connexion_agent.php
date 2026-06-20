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
    header("Location: connexion_agent.php");
    exit;
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    // On détruit la session pour éviter toute exploitation
    session_destroy();
    die("Requête invalide.");
}

// Nettoyage des données
$email_agent = filter_var($_POST['email_agent'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email_agent || empty($password)) {
    die("Veuillez remplir tous les champs.");
}

// Prévention bruteforce (limite 5 tentatives / 10 minutes)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    if (time() - $_SESSION['first_attempt_time'] < 600) {
        die("Trop de tentatives. Réessayez dans quelques minutes.");
    } else {
        // Reset après 10 minutes
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }
}

// Requête SQL sécurisée
$stmt = $pdo->prepare("
    SELECT id, nom_agent, prenom_agent, email_agent, password, role 
    FROM agents 
    WHERE email_agent = ?
    LIMIT 1
");
$stmt->execute([$email_agent]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérification du mot de passe
if ($agent && password_verify($password, $agent['password'])) {

    // Reset bruteforce
    $_SESSION['login_attempts'] = 0;

    // Regénération de l’ID de session (anti fixation)
    session_regenerate_id(true);

    // Stockage des infos agent
    $_SESSION['id'] = $agent['id'];
    $_SESSION['nom_agent'] = $agent['nom_agent'];
    $_SESSION['prenom_agent'] = $agent['prenom_agent'];
    $_SESSION['email_agent'] = $agent['email_agent'];
    $_SESSION['role'] = $agent['role'];

    header("Location: portail_agent.php");
    exit;

} else {

    $_SESSION['login_attempts_citoyen']++;

    $_SESSION['login_error'] = "Email ou mot de passe incorrect.";
    header("Location: connexion_agent.php");
    exit;

}
?>
