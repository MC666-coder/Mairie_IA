<?php
session_start();
header('Content-Type: application/json');

// Charger les configs dans le bon ordre
require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../Config/api_mairie.php';
require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/save_conversation.php';
require_once __DIR__ . '/save_requete.php';

/* ---------------------------------------
   1. Vérification session citoyen
---------------------------------------- */

if (!isset($_SESSION['citoyen_id'])) {
    echo json_encode([
        "response" => "Vous devez être connecté pour utiliser l’assistant."
    ]);
    exit;
}

$citoyenId = (int) $_SESSION['citoyen_id'];

/* ---------------------------------------
   2. Vérification méthode + JSON
---------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "response" => "Méthode non autorisée."
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "response" => "Requête invalide (JSON incorrect)."
    ]);
    exit;
}

/* ---------------------------------------
   3. Validation du message
---------------------------------------- */

$message = trim($data['message'] ?? '');

if ($message === '') {
    echo json_encode([
        "response" => "Votre message est vide."
    ]);
    exit;
}

if (strlen($message) > 5000) {
    $message = substr($message, 0, 5000);
}

/* ---------------------------------------
   4. Appel IA mairie
---------------------------------------- */

$IA = callMairieAPI($message, $citoyenId);

// Sécurisation minimale de la réponse IA
if (!is_array($IA)) {
    $IA = [];
}

$IA['response']   = trim($IA['response'] ?? "Le service est momentanément indisponible.");
$IA['intent']     = $IA['intent']     ?? 'unknown';
$IA['confidence'] = $IA['confidence'] ?? 0;

/* ---------------------------------------
   5. Sauvegarde de la conversation
---------------------------------------- */

saveConversation($pdo, $citoyenId, $message, $IA);

/* ---------------------------------------
   6. Création automatique de requête si demandé
---------------------------------------- */

if (($IA['intent'] ?? '') === 'creation_requete') {

    $requeteId = saveRequete($pdo, $citoyenId, $IA, $message);

    if ($requeteId) {
        // On enrichit simplement la réponse, sans casser la structure
        $IA['response'] .= " Votre demande a bien été transmise au service concerné.";
        $IA['requete_id'] = $requeteId;
    } else {
        $IA['response'] .= " ⚠️ Une erreur est survenue lors de la création de votre demande.";
    }
}

/* ---------------------------------------
   7. Réponse finale JSON
---------------------------------------- */

echo json_encode($IA);
exit;
?>