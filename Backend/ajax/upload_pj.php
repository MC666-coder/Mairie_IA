<?php
require_once __DIR__ . '/../Config/session_init.php'; // Initialise la session et les variables de session
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';
/*/ ajout : fonction notification
require_once __DIR__ . '/../Fonctions/pushnotification.php';
require_once __DIR__ . '/../Fonctions/getCitoyenIdFromDemande.php';*/

$demande_id = $_POST['demande_id'] ?? null;
$agent_id = $_SESSION['agent_id'] ?? null;

if (!$demande_id) {
    echo json_encode(["success" => false, "message" => "ID de demande manquant."]);
    exit;
}

if (!isset($_FILES['fichier'])) {
    echo json_encode(["success" => false, "message" => "Aucun fichier reçu."]);
    exit;
}

$fichier = $_FILES['fichier'];
$nom = basename($fichier['name']);
$extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));

$allowed = ["jpg","jpeg","png","pdf","docx","xlsx"];
if (!in_array($extension, $allowed)) {
    echo json_encode(["success" => false, "message" => "Format non autorisé."]);
    exit;
}

$uploadDir = "../uploads/demandes/";
$nom_unique = uniqid() . "_" . $nom;
$chemin = $uploadDir . $nom_unique;

if (!move_uploaded_file($fichier['tmp_name'], $chemin)) {
    echo json_encode(["success" => false, "message" => "Erreur lors du téléversement."]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO fichiers_demandes (demande_id, agent_id, nom_fichier, chemin, type, taille)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $demande_id,
    $agent_id,
    $nom,
    $nom_unique,
    $fichier['type'],
    $fichier['size']
]);
/*/ recuperer le citoyen_id pour la notification
$citoyen_id = getCitoyenIdFromDemande($pdo, $demande_id);
// envoie de la notification
pushNotifications($pdo, $citoyen_id, $demande_id, 'document', 'Une nouvelle pièce jointe a été ajoutée à votre demande.');*/

echo json_encode([
    "success" => true,
    "message" => "Pièce jointe ajoutée.",
    "nom_fichier" => $nom
]);
