<?php
/*vérification de la session pour éviter que n'importe qui téléchrage un file juste avec l'url*/
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Config/db.php';

if (!isset($_SESSION['agent_id'])) {
    die("Accès refusé");
}

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
