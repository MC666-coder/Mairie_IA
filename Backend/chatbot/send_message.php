<?php
/* =========================================================
   Chatbot citoyen : envoi d'un message à l'IA d'accueil
   ---------------------------------------------------------
   - Aucune donnée n'est écrite en base : le fil de discussion vit
     dans la session (supprimé à la déconnexion ou après inactivité).
   - Si l'IA prépare une demande, le brouillon est vérifié puis gardé
     en session ; seule la confirmation du citoyen la crée
     (confirmer_demande.php).
   ========================================================= */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/api_mairie.php';
require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Config/connaissances_commune.php';
require_once __DIR__ . '/../Middleware/auth.php';

const HISTORIQUE_MAX = 6;              // échanges gardés pour le contexte
const MESSAGES_PAR_HEURE_MAX = 60;     // protection contre les abus (coût API)

function repondre(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Garde uniquement les libellés attendus, en texte court, sans les
 * guillemets que l'IA recopie parfois des consignes (« Annuler » -> Annuler).
 * Ils sont affichés avec textContent : aucun HTML n'est interprété.
 */
function libellesIA(mixed $source, array $cles): array
{
    $libelles = [];
    foreach ($cles as $cle) {
        $valeur = is_array($source) ? ($source[$cle] ?? null) : null;
        if (is_string($valeur) && trim($valeur) !== '' && mb_strlen($valeur) <= 300) {
            $libelles[$cle] = trim(preg_replace('/^[\s«»"“”„]+|[\s«»"“”„]+$/u', '', $valeur));
        }
    }
    return $libelles;
}

/* 1. Citoyen connecté + POST + jeton CSRF */
if (!isset($_SESSION['citoyen_id'])) {
    repondre(['response' => "Vous devez être connecté pour utiliser l'assistant."], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondre(['response' => 'Méthode non autorisée.'], 405);
}
verifier_csrf();

/* 2. Anti-abus : 1 message toutes les 2 secondes, 60 par heure */
$maintenant = time();
$_SESSION['chat_ia_horodatages'] = array_values(array_filter(
    $_SESSION['chat_ia_horodatages'] ?? [],
    fn($t) => $t > $maintenant - 3600
));
$dernier = end($_SESSION['chat_ia_horodatages']) ?: 0;
if ($maintenant - $dernier < 2) {
    repondre(['response' => "Merci de patienter un instant avant d'envoyer un nouveau message."], 429);
}
if (count($_SESSION['chat_ia_horodatages']) >= MESSAGES_PAR_HEURE_MAX) {
    repondre(['response' => "Vous avez envoyé beaucoup de messages. Merci de réessayer un peu plus tard ou de contacter l'accueil de la mairie."], 429);
}
$_SESSION['chat_ia_horodatages'][] = $maintenant;

/* 3. Message */
$data = json_decode(file_get_contents('php://input'), true);
$message = trim(is_array($data) ? (string) ($data['message'] ?? '') : '');
if ($message === '') {
    repondre(['response' => 'Votre message est vide.'], 400);
}
$message = mb_substr($message, 0, 1000);

/* 4. Appel de l'IA avec le fil de discussion et les connaissances de la commune */
$historique = $_SESSION['chat_ia_historique'] ?? [];
$IA = callMairieAPI($message, $historique, connaissancesCommune($pdo));

if ($IA === null) {
    repondre(['response' => "L'assistant est momentanément indisponible. Vous pouvez contacter l'accueil de la mairie ou réessayer dans quelques minutes."]);
}

// Le fil garde la version pseudonymisée du message (jamais en base)
$historique[] = ['citoyen' => sanitizeAgentMessage($message, true), 'assistant' => $IA['reponse']];
$_SESSION['chat_ia_historique'] = array_slice($historique, -HISTORIQUE_MAX);

/* 5. Brouillon de demande : vérifié côté serveur, jamais créé ici */
$brouillon = null;
if ($IA['brouillon'] !== null) {
    $b = $IA['brouillon'];
    $serviceId = null;
    $serviceNom = trim((string) ($b['service'] ?? ''));
    if ($serviceNom !== '') {
        $stmt = $pdo->prepare("SELECT id, nom_service FROM services WHERE actif = 1 AND nom_service = ?");
        $stmt->execute([$serviceNom]);
        if ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $serviceId = (int) $ligne['id'];
        }
    }
    $description = trim((string) ($b['description'] ?? ''));

    // Libellés de la carte traduits par l'IA
    $interface = libellesIA($b['interface'] ?? null,
        ['entete', 'service', 'priorite', 'aide', 'confirmer', 'annuler', 'envoyee', 'annulee']);
    if (isset($interface['envoyee']) && !str_contains($interface['envoyee'], '{numero}')) {
        unset($interface['envoyee']);   // sans le numéro, on garde le message français
    }

    if ($description !== '') {
        $brouillon = [
            'langue'      => $IA['langue'],
            'interface'   => $interface,
            'type'        => in_array($b['type'] ?? '', TYPES_DEMANDE_CITOYEN, true) ? $b['type'] : 'autre',
            'priorite'    => in_array($b['priorite'] ?? '', PRIORITES_CITOYEN, true) ? $b['priorite'] : 'normale',
            'service_id'  => $serviceId,            // null : un agent l'orientera
            'service'     => $serviceId ? $serviceNom : "À déterminer par la mairie",
            'titre'       => mb_substr(trim((string) ($b['titre'] ?? 'Demande')), 0, 120),
            'description' => mb_substr($description, 0, 2000),
        ];
        $_SESSION['brouillon_demande'] = $brouillon;
    }
}

/* Garde-fou langue : si le citoyen n'écrit pas en français mais que la
   réponse accompagnant un brouillon est revenue en français, on affiche
   plutôt les libellés de la carte, déjà traduits dans sa langue. */
if ($brouillon !== null && $IA['langue'] !== 'fr'
    // mots propres au français (pas « la » ni « le », qui existent en espagnol ou en italien)
    && preg_match_all('/(?<!\p{L})(vous|votre|vos|pour|puis|cliquez|cliquer|vérifiez|vérifier|texte|cette|avec|transmettre|signalement|demande|envoi)(?!\p{L})/iu', $IA['reponse']) >= 3
    && isset($brouillon['interface']['entete'], $brouillon['interface']['aide'])) {
    $IA['reponse'] = $brouillon['interface']['entete'] . "\n" . $brouillon['interface']['aide'];
}

/* 6. Réponse (le navigateur reçoit le brouillon pour l'afficher et le modifier) */
$reponse = ['response' => $IA['reponse'], 'intention' => $IA['intention'], 'langue' => $IA['langue']];

// Choix proposé au citoyen : faire sa demande lui-même ou la faire préparer.
// (pas de choix en même temps qu'un brouillon : il a déjà choisi)
if ($brouillon === null && $IA['choix'] !== null) {
    $choix = libellesIA($IA['choix'], ['assistant', 'moi_meme', 'ou_aller', 'lien']);
    if (isset($choix['assistant'], $choix['moi_meme'])) {
        $reponse['choix'] = $choix;
    }
}
if ($brouillon !== null) {
    unset($brouillon['service_id']);
    $reponse['brouillon'] = $brouillon;
}
repondre($reponse);
