<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Fonctions/historique_demande.php';
require_once __DIR__ . '/../Fonctions/conversation_demande.php';

function refuser(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

// Vérification demande_id
$demande_id = intval($_POST['demande_id'] ?? 0);
if ($demande_id <= 0) {
    refuser("ID de demande invalide.");
}

// Règle de la mairie : seul l'agent ASSIGNÉ à la demande écrit au citoyen
$stmt = $pdo->prepare("SELECT agent_id, statut FROM demandes WHERE id = ?");
$stmt->execute([$demande_id]);
$demande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$demande) {
    refuser("Demande introuvable.", 404);
}
if ((int) $demande['agent_id'] !== (int) $agent_id) {
    refuser("Seul l'agent assigné à cette demande peut écrire au citoyen. Assignez-vous la demande d'abord.", 403);
}
if ($demande['statut'] === 'cloturee') {
    refuser("Cette demande est clôturée : la conversation est fermée.", 409);
}

// Vérification message
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    refuser("Le message ne peut pas être vide.");
}
if (mb_strlen($message) > 5000) {
    refuser("Le message est trop long (5000 caractères maximum).");
}

// Document facultatif destiné au citoyen (ex. attestation), au choix :
// - un nouveau fichier envoyé avec le message ;
// - un document INTERNE déjà rangé dans le dossier (fichier_id).
$document = null;
if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE) {
    $document = verifierDocument($_FILES['fichier']);
    if (!$document['ok']) {
        refuser($document['message'], $document['code']);
    }
}

$documentDossier = null;
$fichierId = (int) ($_POST['fichier_id'] ?? 0);
if ($fichierId > 0) {
    if ($document) {
        refuser("Joignez soit un nouveau fichier, soit un document du dossier, pas les deux.");
    }
    // Il doit appartenir à CETTE demande et être interne (ni reçu, ni déjà envoyé)
    $stmt = $pdo->prepare("SELECT id, nom_fichier FROM fichiers_demandes
                           WHERE id = ? AND demande_id = ? AND agent_id IS NOT NULL AND visible_citoyen = 0");
    $stmt->execute([$fichierId, $demande_id]);
    $documentDossier = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$documentDossier) {
        refuser("Ce document n'est pas un document interne de cette demande (ou il a déjà été envoyé).", 409);
    }
}

// parent_id optionnel (si l’agent répond à un message citoyen précis)
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

try {

    // Insertion du message agent : « nouveau » tant que le citoyen ne l'a pas lu
    $insert = $pdo->prepare("
        INSERT INTO messages_demandes
        (demande_id, auteur_type, agent_id, message, parent_id, statut, created_at)
        VALUES (?, 'agent', ?, ?, ?, 'nouveau', NOW())
    ");
    $insert->execute([$demande_id, $agent_id, $message, $parent_id]);

    $message_id = (int) $pdo->lastInsertId();

    // Document joint, visible par le citoyen dans la conversation
    if ($document) {
        if (enregistrerDocument($pdo, $demande_id, (int) $agent_id, $document, true, $message_id) === null) {
            $pdo->prepare("DELETE FROM messages_demandes WHERE id = ?")->execute([$message_id]);
            refuser("Le document n'a pas pu être enregistré. Le message n'a pas été envoyé.", 500);
        }
        journaliserAction($pdo, $demande_id, (int) $agent_id, "Document envoyé au citoyen", "Nom du fichier : " . $document['nom']);
    }

    // Document du dossier : il devient visible par le citoyen et rejoint le
    // message (il passe de « Internes » à « Envoyés au citoyen »)
    if ($documentDossier) {
        $pdo->prepare("UPDATE fichiers_demandes SET visible_citoyen = 1, message_id = ?
                       WHERE id = ? AND visible_citoyen = 0")
            ->execute([$message_id, $documentDossier['id']]);
        journaliserAction($pdo, $demande_id, (int) $agent_id, "Document du dossier envoyé au citoyen",
                          "Nom du fichier : " . $documentDossier['nom_fichier']);
    }

    // Trace dans l'historique, écrite par le serveur au moment de l'action
    journaliserAction($pdo, $demande_id, (int) $agent_id, "Message envoyé au citoyen", $message);

    // Email au citoyen (sans le contenu du message, RGPD)
    notifierCitoyenNouveauMessage($pdo, $demande_id);

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
        "message" => ($document || $documentDossier) ? "Message et document envoyés au citoyen" : "Message envoyé au citoyen",
        "html" => $html,
        "message_id" => $message_id,
        "created_at" => $created_at,
        "statut" => "nouveau"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne."
    ]);
}