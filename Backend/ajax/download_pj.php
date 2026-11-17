<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
require_once __DIR__ . '/../Config/db.php';


$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Fichier invalide");
}

$stmt = $pdo->prepare("SELECT nom_fichier, chemin, type FROM fichiers_demandes WHERE id = ?");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) {
    die("Fichier introuvable.");
}

$path = __DIR__ . "/../uploads/demandes/" . $f['chemin'];

if (!file_exists($path)) {
    die("Le fichier n'existe pas sur le serveur.");
}

header("Content-Type: " . $f['type']);
header("Content-Disposition: attachment; filename=\"" . basename($f['nom_fichier']) . "\"");
header("Content-Length: " . filesize($path));

readfile($path);
exit;
?>
