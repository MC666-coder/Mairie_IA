<?php
/*vérification de la session pour éviter qu'un bandit ne télécharge n'importe quel dossier*/
require_once __DIR__ . '/../Config/session_init.php';

require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

// Récupération de l'ID via GET (IMPORTANT)
$demande_id = $_GET['demande_id'] ?? null;

if (!$demande_id) {
    echo json_encode(["count" => 0, "grouped" => []]);
    exit;
}

// Récupération des fichiers
$stmt = $pdo->prepare("
    SELECT id, nom_fichier, chemin, type, taille, date_upload
    FROM fichiers_demandes
    WHERE demande_id = ?
    ORDER BY date_upload DESC
");
$stmt->execute([$demande_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];

foreach ($files as $f) {

    // Format date
    $date = date("Y-m-d", strtotime($f["date_upload"]));

    if (!isset($grouped[$date])) {
        $grouped[$date] = [];
    }

    // Construction de l'URL (IMPORTANT)
    $grouped[$date][] = [
        "id" => $f["id"],
        "nom" => $f["nom_fichier"],
        "url" => "../Backend/ajax/download_pj.php?id" . $f["id"],
        "type" => $f["type"]
    ];
}

echo json_encode([
    "count" => count($files),
    "grouped" => $grouped
]);
exit;
?>