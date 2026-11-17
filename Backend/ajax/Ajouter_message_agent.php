<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';
/*/ ajout : fonction notification 
require_once __DIR__ . '/../Fonctions/pushnotification.php';
require_once __DIR__ . '/../Fonctions/getCitoyenIdFromDemande.php';*/



// Vérification demande_id
$demande_id = intval($_POST['demande_id'] ?? 0);
if ($demande_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID de demande invalide."
    ]);
    exit;
}

// Vérification message
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    echo json_encode([
        "success" => false,
        "message" => "Le message ne peut pas être vide."
    ]);
    exit;
}

// parent_id optionnel (si l’agent répond à un message citoyen précis)
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

try {

    // Insertion du message agent
    $insert = $pdo->prepare("
        INSERT INTO messages_demandes 
        (demande_id, auteur_type, agent_id, message, parent_id, statut, created_at)
        VALUES (?, 'agent', ?, ?, ?, 'répondu', NOW())
    ");
    $insert->execute([$demande_id, $agent_id, $message, $parent_id]);

    $message_id = $pdo->lastInsertId();

    // Si l’agent répond à un message citoyen → mettre à jour son statut
    if ($parent_id) {
        $pdo->prepare("
            UPDATE messages_demandes 
            SET statut = 'répondu'
            WHERE id = ?
        ")->execute([$parent_id]);
    }
    /*/ ajout : recuprer le citoyen_id pour la notification
    $citoyen_id = getCitoyenIdFromDemande($pdo, $demande_id);
    // ajout : envoie de la notification
    pushNotifications($pdo, $citoyen_id, $demande_id, 'message', 'Vous avez reçu un nouveau message de votre agent Concernant votre demande.');*/

    // Récupération infos agent
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

    // Date exacte pour le JSON + HTML
    $created_at = date('Y-m-d H:i:s');

    // HTML pour insertion instantanée dans la timeline
    $html = "
        <div class='message-item agent'
             data-message-id='{$message_id}'
             data-created-at='{$created_at}'
             data-parent-id='" . ($parent_id ?? "") . "'>

            <div class='message-avatar'>{$initiales}</div>

            <div class='message-content'>
                <div class='message-header'>
                    <span class='message-author'>{$auteur}</span>
                    <span class='message-date'>{$created_at}</span>
                </div>
                <div class='message-body'>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
    ";

    echo json_encode([
        "success" => true,
        "message" => "Réponse envoyée",
        "html" => $html,
        "message_id" => $message_id,
        "created_at" => $created_at,
        "statut" => "répondu"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}