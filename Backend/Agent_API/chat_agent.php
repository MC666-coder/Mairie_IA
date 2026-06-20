<?php
/* initialisation de la session */
require_once __DIR__ . '/../Config/session_init.php';
ob_start();
ob_clean();


header('Content-Type: application/json');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

/* connexion à la base de données */
require_once __DIR__ . '/../Config/db.php';
/* chargement des clés API */
require_once __DIR__ . '/../Config/env.php';
/* fonctions API mairie */
require_once __DIR__ . '/gemini.php';
/* fonction d'enregistrement de la conversation IA */
require_once __DIR__ . '/save_ai_conversation_agent.php';
/* contexte métier pour l'agent IA */
$context_metier = require_once __DIR__ . '/context_agent_IA.php';






// Vérification connexion agent

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_role'])) {
    echo json_encode(['error' => 'Vous devez être connecté pour accéder à cette API.']);
    exit();
}

$agentId = (int) $_SESSION['agent_id'];
$agentRole = $_SESSION['agent_role'];

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
   Anti-spam simple : 1 message / 1 sec
---------------------------------------- */

if (!isset($_SESSION['last_ia_agent_message'])) {
    $_SESSION['last_ia_agent_message'] = 0;
}
if (time() - $_SESSION['last_ia_agent_message'] < 1) {
    echo json_encode(['error' => 'Veuillez patienter avant d’envoyer un nouveau message.']);
    exit();
}
$_SESSION['last_ia_agent_message'] = time();

/* ---------------------------------------
   Validation du message
---------------------------------------- */

$message = trim($data['message'] ?? '');

if ($message === '' || strlen($message) < 2) {
    echo json_encode(['error' => 'Message invalide.']);
    exit();
}

/* ---------------------------------------
   Construction du contexte IA
---------------------------------------- */

$context = [
    'page'   => $data['page']   ?? null,   // ex: demandes, messages, notes, dashboard
    'action' => $data['action'] ?? null,   // ex: analyse, resume, reponse, workflow
    'agent' => [
        'id'   => $agentId,
        'role' => $agentRole,
        'service' => $data['agent']['service'] ?? null
    ],
    'demande' => $data['demande'] ?? null, // ID demande si présent
    'statut'  => $data['statut']  ?? null,
    'messages' => $data['messages'] ?? [],
    'notes'    => $data['notes'] ?? [],
    'agents_internes' => $data['agents_internes'] ?? [], // liste des agents internes
];
//fallback : si aucun service n'est précisé, on force "voirie" pour bénéficier du contexte métier spécifique
if (empty($context['agent']['service'])) {
    $context['agent']['service'] = 'voirie';
}

/* sécurisation et passage à l'action */
$action = $data['action'] ?? 'free_prompt';
$allowed_actions= ['free_prompt', 'analyse', 'action', 'explication'];
if (!in_array($action, $allowed_actions, true)){
    $action = 'free_prompt';
}
$context['action'] = $action;

/* ---------------------------------------
   Appel IA agent
---------------------------------------- */

$IA_response = callAgentAI($message, $context);

if (!$IA_response) {
    $IA_response = "L'assistant IA est momentanément indisponible.";
}

/* ---------------------------------------
   Enregistrement de la conversation IA
---------------------------------------- */

saveAIConversationAgent(
    $pdo,
    $agentId,
    $context,
    $message,
    $IA_response
);



/* ---------------------------------------
   Réponse finale
---------------------------------------- */
$debug = ob_get_clean();
ob_end_clean();
if ($debug !== '') {
    error_log("DEBUG IA AGENT OUTPUT: " . $debug);
}

echo json_encode([
    'response' => $IA_response,
    'context_used' => $context
]);

exit();
?>