<?php
/* ---------------------------------------
   Sécurisation session
---------------------------------------- */

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';



// Vérification connexion citoyen
if (!isset($_SESSION['id']) || !isset($_SESSION['email_citoyen'])) {
    http_response_code(403);
    die("Accès refusé.");
}

$citoyenId = (int) $_SESSION['id'];

/* ---------------------------------------
   Vérification méthode POST
---------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

/* ---------------------------------------
   Vérification CSRF
---------------------------------------- */

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    session_destroy();
    die("Requête invalide.");
}

/* ---------------------------------------
   Anti-spam simple : 1 demande / 5 sec
---------------------------------------- */

if (!isset($_SESSION['last_demande_submit'])) {
    $_SESSION['last_demande_submit'] = 0;
}
if (time() - $_SESSION['last_demande_submit'] < 5) {
    die("Veuillez patienter avant de soumettre une nouvelle demande.");
}
$_SESSION['last_demande_submit'] = time();

/* ---------------------------------------
   Nettoyage des données
---------------------------------------- */

$service_id = trim($_POST['services_id'] ?? '');
$type_demande = trim($_POST['type'] ?? '');
$message = trim($_POST['message'] ?? '');
$priorite = trim($_POST['priorite'] ?? '');

/* ---------------------------------------
   Validation des champs
---------------------------------------- */

$types_valides = ['plainte', 'question', 'suggestion'];
$priorites_valides = ['basse', 'moyenne', 'haute'];

if (!in_array($type_demande, $types_valides, true)) {
    die("Type de demande invalide.");
}

if (!in_array($priorite, $priorites_valides, true)) {
    die("Priorité invalide.");
}

if ($message === '' || strlen($message) < 5) {
    die("Le message est trop court.");
}

if (!ctype_digit($service_id)) {
    die("Service invalide.");
}

/* ---------------------------------------
   Vérification que le service existe
---------------------------------------- */

$checkService = $pdo->prepare("SELECT id FROM services WHERE id = ?");
$checkService->execute([$service_id]);

if ($checkService->rowCount() === 0) {
    die("Service introuvable.");
}

/* ---------------------------------------
   Insertion sécurisée
---------------------------------------- */

$insert = $pdo->prepare("
    INSERT INTO requette (type, services_id, message, priorite, statut, citoyens_id, created_at)
    VALUES (?, ?, ?, ?, 'nouvelle', ?, NOW())
");

$insert->execute([
    $type_demande,
    $service_id,
    $message,
    $priorite,
    $citoyenId
]);

/* ---------------------------------------
   Redirection propre
---------------------------------------- */

header("Location: portail_citoyen.php?demande=success");
exit();
?>