<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';


$agent_action_id = $agent_id; // agent qui effectue l'action

// Vérification ID demande
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "ID de demande invalide."
    ]);
    exit;
}

$demande_id = intval($_GET['id']);
$agent_id = $_POST['agent_id'] ?? null;

// Vérification agent assigné
if (!$agent_id || !is_numeric($agent_id)) {
    echo json_encode([
        "success" => false,
        "message" => "Agent manquant ou invalide."
    ]);
    exit;
}

try {
    // Mise à jour de l'assignation
    $update = $pdo->prepare("
        UPDATE demandes 
        SET agent_id = ?, date_mise_a_jour = NOW()
        WHERE id = ?
    ");
    $update->execute([$agent_id, $demande_id]);
    
    // Récupération du nom de l’agent assigné
    $agent = $pdo->prepare("SELECT prenom, nom FROM agents WHERE id = ?");
    $agent->execute([$agent_id]);
    $info = $agent->fetch();

    if (!$info) {
        echo json_encode([
            "success" => false,
            "message" => "Agent introuvable."
        ]);
        exit;
    }

    $nom_agent = $info['prenom'] . " " . $info['nom'];

    // Ajout dans l'historique
    $log = $pdo->prepare("
        INSERT INTO historique_demandes (demande_id, agent_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $log->execute([
        $demande_id,
        $agent_action_id,
        "Assignation d'un agent",
        "Assigné à : {$nom_agent}"
    ]);

    // Réponse JSON propre
    echo json_encode([
        "success" => true,
        "message" => "Agent assigné avec succès",
        "agent_nom" => $nom_agent,
        "date_mise_a_jour" => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}

exit;
