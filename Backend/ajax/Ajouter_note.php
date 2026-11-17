<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';



// Vérification ID demande
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "ID de demande invalide."
    ]);
    exit;
}

$demande_id = intval($_GET['id']);
$note = trim($_POST['note'] ?? '');

if ($note === '') {
    echo json_encode([
        "success" => false,
        "message" => "La note ne peut pas être vide."
    ]);
    exit;
}

try {
    // Insertion de la note
    $insert = $pdo->prepare("
        INSERT INTO notes_demandes (demande_id, agent_id, note, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $insert->execute([$demande_id, $agent_id, $note]);

    // Récupération du nom de l’agent
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

    $initiales = mb_strtoupper(
        mb_substr($info['prenom'], 0, 1) .
        mb_substr($info['nom'], 0, 1)
    );

    // AJOUT DANS L'HISTORIQUE
    $log = $pdo->prepare("
        INSERT INTO historique_demandes (demande_id, agent_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $log->execute([
        $demande_id,
        $agent_id,
        "Ajout d'une note interne",
        $note
    ]);

    // HTML pour la timeline des notes
    $note_html = "
        <div class='notes-date-group' data-date-group='" . date('Y-m-d') . "'>
            <div class='notes-date-separator'>
                <span>Aujourd'hui</span>
            </div>
            <div class='note-item'
                 data-agent-id='" . (int)$agent_id . "'
                 data-created-at='" . date('Y-m-d H:i:s') . "'
                 data-text='" . htmlspecialchars($note, ENT_QUOTES) . "'>
                <div class='note-timeline-dot'></div>
                <div class='note-content'>
                    <div class='note-header'>
                        <div class='note-avatar'>{$initiales}</div>
                        <div class='note-meta-main'>
                            <span class='note-author'>{$info['prenom']} {$info['nom']}</span>
                            <span class='note-date'>" . date('Y-m-d H:i:s') . "</span>
                        </div>
                        <div class='note-tags'>
                            <span class='note-tag note-tag-info'>Note</span>
                        </div>
                    </div>
                    <div class='note-body'>" . nl2br(htmlspecialchars($note)) . "</div>
                </div>
            </div>
        </div>
    ";

    // HTML pour la timeline historique
    $history_html = "
        <div class='history-item' data-type='notes' data-text='" . htmlspecialchars($note, ENT_QUOTES) . "'>
            <div class='history-dot history-dot-notes'></div>
            <div class='history-content'>
                <div class='history-header'>
                    <div class='history-avatar'>{$initiales}</div>
                    <div class='history-meta'>
                        <span class='history-author'>{$info['prenom']} {$info['nom']}</span>
                        <span class='history-time'>" . date('Y-m-d H:i:s') . "</span>
                    </div>
                    <span class='history-tag history-tag-notes'>Note</span>
                </div>
                <div class='history-body'>
                    <strong>Ajout d'une note interne</strong><br>
                    " . nl2br(htmlspecialchars($note)) . "
                </div>
            </div>
        </div>
    ";

    echo json_encode([
        "success" => true,
        "message" => "Note ajoutée avec succès",
        "note_html" => $note_html,
        "history_html" => $history_html
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}

exit;
