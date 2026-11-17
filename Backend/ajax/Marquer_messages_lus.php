<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';


// Vérification demande_id
$demande_id = intval($_POST['demande_id'] ?? 0);
if ($demande_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID de demande invalide."
    ]);
    exit;
}

try {

    // Marquer les messages citoyens comme lus
    $sql = "
        UPDATE messages_demandes
        SET statut = 'lu'
        WHERE demande_id = ?
        AND auteur_type = 'citoyen'
        AND statut != 'lu'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$demande_id]);

    echo json_encode([
        "success" => true,
        "message" => "Messages marqués comme lus."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}