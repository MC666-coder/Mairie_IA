<?php
require_once __DIR__ . '/../Config/db.php';

$id = $_POST['id'];

$stmt = $pdo->prepare("SELECT * FROM fichiers_demandes WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(["success" => false, "message" => "Fichier introuvable."]);
    exit;
}

unlink("../uploads/demandes/" . $file["chemin"]);

$stmt = $pdo->prepare("DELETE FROM fichiers_demandes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["success" => true, "message" => "Pièce jointe supprimée.", "nom_fichier" => $file["nom_fichier"]]);
