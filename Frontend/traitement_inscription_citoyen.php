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
$nom = trim($_POST['nom_citoyen'] ?? '');
$prenom = trim($_POST['prenom_citoyen'] ?? '');
$email = filter_var($_POST['email_citoyen'] ?? '', FILTER_VALIDATE_EMAIL);
$telephone = trim($_POST['telephone_citoyen'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Vérification des champs obligatoires
if ($nom === '' || $prenom === '' || !$email || $telephone === '' || $password === '' || $password_confirm === '') {
    die("Veuillez remplir tous les champs.");
}

// Vérification du téléphone (format FR simple)
if (!preg_match('/^[0-9]{10}$/', $telephone)) {
    die("Numéro de téléphone invalide. Format attendu : 10 chiffres.");
}

// Vérification longueur mot de passe
if (strlen($password) < 8) {
    die("Le mot de passe doit contenir au moins 8 caractères.");
}

// Vérification confirmation mot de passe
if ($password !== $password_confirm) {
    die("Les mots de passe ne correspondent pas.");
}

// Vérification email déjà utilisé
$checkEmail = $pdo->prepare("SELECT id FROM citoyens WHERE email_citoyen = ?");
$checkEmail->execute([$email]);

if ($checkEmail->rowCount() > 0) {
    die("Cette adresse email est déjà utilisée.");
}

// Hash du mot de passe
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insertion sécurisée
$insert = $pdo->prepare("
    INSERT INTO citoyens (nom_citoyen, prenom_citoyen, email_citoyen, telephone_citoyen, password, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$insert->execute([$nom, $prenom, $email, $telephone, $passwordHash]);

// Création session citoyen
$_SESSION['citoyen_id'] = $pdo->lastInsertId();
$_SESSION['nom_citoyen'] = $nom;
$_SESSION['prenom_citoyen'] = $prenom;
$_SESSION['email_citoyen'] = $email;

// Redirection propre
$_SESSION['inscription_success'] = true;
header("Location: portail_citoyen.php");
exit();
?>