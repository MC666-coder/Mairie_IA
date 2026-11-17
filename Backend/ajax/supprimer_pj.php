<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
require_once __DIR__ . '/../Config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    refuser_acces(405, "Méthode non autorisée.");
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM fichiers_demandes WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(["success" => false, "message" => "Fichier introuvable."]);
    exit;
}

// Seuls les documents INTERNES peuvent être supprimés : un document reçu du
// citoyen ou envoyé au citoyen fait partie de l'échange (traçabilité)
if ($file['agent_id'] === null || (int) ($file['visible_citoyen'] ?? 0) === 1) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Ce document fait partie de l'échange avec le citoyen : il ne peut pas être supprimé."]);
    exit;
}

$chemin = __DIR__ . "/../uploads/demandes/" . basename($file["chemin"]);
if (is_file($chemin)) {
    unlink($chemin);
}

$stmt = $pdo->prepare("DELETE FROM fichiers_demandes WHERE id = ?");
$stmt->execute([$id]);

// Trace dans l'historique, écrite par le serveur au moment de l'action
require_once __DIR__ . '/../Fonctions/historique_demande.php';
journaliserAction($pdo, (int) $file['demande_id'], (int) $agent_id, "Suppression d'une pièce jointe", "Nom du fichier : " . $file['nom_fichier']);

echo json_encode(["success" => true, "message" => "Pièce jointe supprimée.", "nom_fichier" => $file["nom_fichier"]]);
