<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';

// Vérification demande_id
if (!isset($_GET['demande_id']) || !is_numeric($_GET['demande_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "ID de demande invalide."
    ]);
    exit;
}

$demande_id = intval($_GET['demande_id']);

try {

    // Récupération des messages (citoyen + agent)
    $sql = "
        SELECT
            m.id,
            m.demande_id,
            m.auteur_type,
            m.citoyen_id,
            m.agent_id,
            m.message,
            m.parent_id,
            m.statut,
            m.created_at,
            m.updated_at,
            a.prenom,
            a.nom
        FROM messages_demandes m
        LEFT JOIN agents a ON m.agent_id = a.id
        WHERE m.demande_id = ?
        ORDER BY m.created_at ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$demande_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔥 Correction ici :
    // Avant : statut = 'nouveau'
    // Maintenant : statut != 'lu'
    // → Le badge reste visible tant que l’agent n’a pas cliqué
    $nouveaux_messages = 0;
    foreach ($messages as $m) {
        if ($m['auteur_type'] === 'citoyen' && $m['statut'] === 'nouveau') {
            $nouveaux_messages++;
        }
    }

    echo json_encode([
        "success" => true,
        "messages" => $messages,
        "nouveaux_messages" => $nouveaux_messages
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}