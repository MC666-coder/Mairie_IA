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
    http_response_code(405);
    die("Méthode non autorisée.");
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    session_destroy();
    die("Requête invalide.");
}

// Nettoyage des données
$nom = trim($_POST['nom_agent'] ?? '');
$prenom = trim($_POST['prenom_agent'] ?? '');
$email = filter_var($_POST['email_agent'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$role = $_POST['role'] ?? 'agent';

// Vérifications de base
if ($nom === '' || $prenom === '' || !$email || $password === '' || $password_confirm === '') {
    die("Veuillez remplir tous les champs.");
}

// Vérification longueur mot de passe
if (strlen($password) < 8) {
    die("Le mot de passe doit contenir au moins 8 caractères.");
}

// Vérification confirmation mot de passe
if ($password !== $password_confirm) {
    die("Les mots de passe ne correspondent pas.");
}

// Vérification rôle valide
$roles_valides = ['agent', 'admin'];
if (!in_array($role, $roles_valides, true)) {
    die("Rôle invalide.");
}

// Vérification email déjà utilisé
$checkEmail = $pdo->prepare("SELECT id FROM agents WHERE email_agent = ?");
$checkEmail->execute([$email]);

if ($checkEmail->rowCount() > 0) {
    die("Cette adresse email est déjà utilisée.");
}

// Hash du mot de passe
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insertion sécurisée
$insert = $pdo->prepare("
    INSERT INTO agents (nom_agent, prenom_agent, email_agent, password, role, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$insert->execute([$nom, $prenom, $email, $passwordHash, $role]);

// Création session agent
$_SESSION['id'] = $pdo->lastInsertId();
$_SESSION['nom_agent'] = $nom;
$_SESSION['prenom_agent'] = $prenom;
$_SESSION['email_agent'] = $email;
$_SESSION['role'] = $role;

// Redirection propre
$_SESSION['inscription_success'] = true;
header("Location: portail_agent.php");
exit();

?>