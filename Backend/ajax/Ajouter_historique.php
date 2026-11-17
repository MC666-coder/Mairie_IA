<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
function detectType($action) {
    if (stripos($action, "statut") !== false) return "status";
    if (stripos($action, "assign") !== false) return "assignation";
    if (stripos($action, "note") !== false) return "notes";
    return "other";
}
ini_set('display_errors', 0);
error_reporting(E_ALL);

/*ouverture de la session*/ 

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';



$demande_id = intval($_POST['demande_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$details = trim($_POST['details'] ?? '');

if ($demande_id <= 0 || $action === '') {
    echo json_encode(["success" => false, "message" => "Paramètres invalides."]);
    exit;
}

try {
    // Insert historique
    $insert = $pdo->prepare("
        INSERT INTO historique_demandes (demande_id, agent_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insert->execute([$demande_id, $agent_id, $action, $details]);

    // Récupération agent
    $agent = $pdo->prepare("SELECT prenom, nom FROM agents WHERE id = ?");
    $agent->execute([$agent_id]);
    $info = $agent->fetch();

    $initiales = mb_strtoupper(
        mb_substr($info['prenom'], 0, 1) .
        mb_substr($info['nom'], 0, 1)
    );
    // Échappement à l'affichage (les noms sont stockés bruts en base)
    $initiales = htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8');
    $auteur    = htmlspecialchars($info['prenom'] . ' ' . $info['nom'], ENT_QUOTES, 'UTF-8');

    $type = detectType($action);

    echo json_encode([
        "success" => true,
        "html" => "
            <div class='history-item' data-type='{$type}' data-text='" . htmlspecialchars($action . ' ' . $details) . "'>
                <div class='history-dot history-dot-{$type}'></div>
                <div class='history-content'>
                    <div class='history-header'>
                        <div class='history-avatar'>{$initiales}</div>
                        <div class='history-meta'>
                            <span class='history-author'>{$auteur}</span>
                            <span class='history-time'>" . date('Y-m-d H:i:s') . "</span>
                        </div>
                        <span class='history-tag history-tag-{$type}'>" . ucfirst($type) . "</span>
                    </div>
                    <div class='history-body'>
                        <strong>" . htmlspecialchars($action) . "</strong><br>
                        " . nl2br(htmlspecialchars($details)) . "
                    </div>
                </div>
            </div>
        "
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Erreur serveur interne."]);
}
?>