<?php
/* =========================================================
   Ajout d'un document INTERNE au dossier par un agent
   ---------------------------------------------------------
   Un document ajouté ici n'est JAMAIS visible par le citoyen
   (visible_citoyen = 0). Pour envoyer un document au citoyen
   (attestation...), l'agent assigné passe par la conversation
   (Ajouter_message_agent.php).
   ========================================================= */
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
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

$demande_id = (int) ($_POST['demande_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id FROM demandes WHERE id = ?");
$stmt->execute([$demande_id]);
if (!$stmt->fetchColumn()) {
    refuser("Demande introuvable.", 404);
}

// Même contrôle que pour la conversation : vrai type du contenu, taille,
// nom aléatoire sur le disque
$document = verifierDocument($_FILES['fichier'] ?? null);
if (!$document['ok']) {
    refuser($document['message'], $document['code']);
}
if (enregistrerDocument($pdo, $demande_id, (int) $agent_id, $document, false, null) === null) {
    refuser("Le document n'a pas pu être enregistré. Merci de réessayer.", 500);
}

// Trace dans l'historique, écrite par le serveur au moment de l'action
journaliserAction($pdo, $demande_id, (int) $agent_id, "Ajout d'un document interne", "Nom du fichier : " . $document['nom']);

echo json_encode([
    "success" => true,
    "message" => "Document interne ajouté (non visible par le citoyen).",
    "nom_fichier" => $document['nom']
]);
