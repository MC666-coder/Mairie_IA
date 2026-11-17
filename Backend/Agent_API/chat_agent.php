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

/* RGPD : les échanges avec l'IA agent ne sont JAMAIS enregistrés
   (ni en base, ni dans les journaux). Ils ne vivent que le temps de
   la requête et dans la fenêtre du navigateur de l'agent. */






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

// Service de l'agent : lu en base à partir de SA session (jamais fourni
// par le navigateur, qui pourrait l'usurper).
$serviceAgent = null;
$services = [];
try {
    $stmt = $pdo->query("SELECT id, nom_service, description_service FROM services WHERE actif = 1 ORDER BY nom_service");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $services[$s['nom_service']] = $s['description_service'];
        if ((int) $s['id'] === (int) ($_SESSION['service_id'] ?? 0)) {
            $serviceAgent = $s['nom_service'];
        }
    }
} catch (PDOException $e) {
    error_log("chat_agent : lecture des services impossible");
}

$context = [
    'agent' => [
        'role'    => $agentRole,
        'service' => $serviceAgent ?? 'non renseigné',
    ],
    // Liste officielle des services de la commune (pour le routage)
    'services' => $services,
];

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
   Réponse finale (rien n'est conservé côté serveur)
---------------------------------------- */
$debug = ob_get_clean();
if ($debug !== '') {
    error_log("chat_agent : sortie inattendue (" . strlen($debug) . " octets)");
}

echo json_encode(['response' => $IA_response]);

exit();
?>