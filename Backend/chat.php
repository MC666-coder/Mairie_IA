<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Config/api_mairie.php';
require_once __DIR__ . '/../Backend/Config/save_requete.php';

/* ---------------------------------------
   Sécurisation session
---------------------------------------- */

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);

// Vérification connexion citoyen
if (!isset($_SESSION['id']) || !isset($_SESSION['email_citoyen'])) {
    echo json_encode(['error' => 'Vous devez être connecté pour accéder à cette API.']);
    exit();
}

$citoyenId = (int) $_SESSION['id'];

/* ---------------------------------------
   Vérification méthode POST + JSON valide
---------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['error' => 'Format JSON invalide.']);
    exit();
}

/* ---------------------------------------
   Anti-spam simple : 1 message / 2 sec
---------------------------------------- */

if (!isset($_SESSION['last_ia_message'])) {
    $_SESSION['last_ia_message'] = 0;
}
if (time() - $_SESSION['last_ia_message'] < 2) {
    echo json_encode(['error' => 'Veuillez patienter avant d’envoyer un nouveau message.']);
    exit();
}
$_SESSION['last_ia_message'] = time();

/* ---------------------------------------
   Validation du message
---------------------------------------- */

$message = trim($data['message'] ?? '');

if ($message === '' || strlen($message) < 2) {
    echo json_encode(['error' => 'Message invalide.']);
    exit();
}

/* ---------------------------------------
   Appel IA mairie
---------------------------------------- */

$IA = callMairieAPI($message, $citoyenId);

// Sécurisation de la réponse IA
if (!is_array($IA)) {
    $IA = [];
}

$IA_response   = trim($IA['response']   ?? "L'IA est indisponible pour le moment.");
$IA_intent     = trim($IA['intent']     ?? "unknown");
$IA_confidence = floatval($IA['confidence'] ?? 0);

/* ---------------------------------------
   Si l’IA demande de créer une requête
---------------------------------------- */

if ($IA_intent === 'creation_requete') {

    $requeteId = saveRequete($pdo, $citoyenId, $IA, $message);

    echo json_encode([
        "response"   => "Votre demande a été créée avec succès. Numéro : $requeteId.",
        "intent"     => "confirmation_creation",
        "confidence" => 1,
        "data"       => ["id" => $requeteId]
    ]);
    exit();
}

/* ---------------------------------------
   Sinon : enregistrer la conversation
---------------------------------------- */

$save = $pdo->prepare("
    INSERT INTO ia_conversations 
    (citoyens_id, user_message, ia_reponse, intent, confidence, created_at) 
    VALUES (?, ?, ?, ?, ?, NOW())
");
$save->execute([
    $citoyenId,
    $message,
    $IA_response,
    $IA_intent,
    $IA_confidence
]);

/* ---------------------------------------
   Réponse finale
---------------------------------------- */

echo json_encode([
    'response' => $IA_response,
    'intent'   => $IA_intent,
    'confidence' => $IA_confidence
]);
exit();
?>