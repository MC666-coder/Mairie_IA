<?php
/* =========================================================
   Conversation mairie -> citoyen, pour UNE demande du citoyen
   ---------------------------------------------------------
   GET  ?demande_id=N            messages, documents, droit de répondre
   POST action=reponse           réponse du citoyen (multipart) :
                                 document et/ou court message
   POST action=lu                messages de la mairie marqués comme lus

   Règles (voir Backend/Fonctions/conversation_demande.php) :
   - c'est l'agent assigné qui écrit au citoyen ; le citoyen ne peut
     jamais écrire le premier, il RÉPOND (5 réponses au plus tant que
     l'agent n'a pas réécrit) ;
   - plus aucun échange une fois la demande clôturée.

   Sécurité : citoyen connecté, demande qui lui appartient (anti-IDOR),
   jeton CSRF sur les POST, limite anti-abus, fichiers vérifiés.
   ========================================================= */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Middleware/auth.php';
require_once __DIR__ . '/../Fonctions/historique_demande.php';
require_once __DIR__ . '/../Fonctions/conversation_demande.php';

const REPONSE_MAX            = 500;   // caractères d'un message citoyen
const REPONSES_SANS_AGENT    = 5;     // réponses possibles avant que l'agent réécrive
const ENVOIS_PAR_HEURE       = 30;
const DOCUMENTS_CITOYEN_MAX  = 20;    // par demande

function repondre(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 1. Citoyen connecté */
if (!isset($_SESSION['citoyen_id'])) {
    repondre(['success' => false, 'message' => 'Session expirée, veuillez vous reconnecter.'], 401);
}
$citoyenId = (int) $_SESSION['citoyen_id'];
$methode = $_SERVER['REQUEST_METHOD'];
if ($methode === 'POST') {
    verifier_csrf();
} elseif ($methode !== 'GET') {
    repondre(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

/* 2. La demande doit appartenir au citoyen (sinon : introuvable) */
$demandeId = (int) ($methode === 'GET' ? ($_GET['demande_id'] ?? 0) : ($_POST['demande_id'] ?? 0));
$stmt = $pdo->prepare("SELECT id, statut FROM demandes WHERE id = ? AND citoyen_id = ?");
$stmt->execute([$demandeId, $citoyenId]);
$demande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$demande) {
    repondre(['success' => false, 'message' => 'Demande introuvable.'], 404);
}

/**
 * Le citoyen peut-il répondre ? (un message de l'agent existe, demande
 * ouverte, et pas plus de REPONSES_SANS_AGENT depuis le dernier message
 * de l'agent)
 * @return array{peut: bool, raison: string}
 */
function droitDeReponse(PDO $pdo, int $demandeId, string $statut): array
{
    if ($statut === 'cloturee') {
        return ['peut' => false, 'raison' => 'Cette demande est clôturée : la conversation est fermée.'];
    }
    $stmt = $pdo->prepare("SELECT MAX(id) FROM messages_demandes WHERE demande_id = ? AND auteur_type = 'agent'");
    $stmt->execute([$demandeId]);
    $dernierAgent = (int) $stmt->fetchColumn();
    if ($dernierAgent === 0) {
        return ['peut' => false, 'raison' => 'Si un document ou une précision est nécessaire, l’agent qui traite votre demande vous écrira ici. Vous pourrez alors lui répondre.'];
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages_demandes WHERE demande_id = ? AND auteur_type = 'citoyen' AND id > ?");
    $stmt->execute([$demandeId, $dernierAgent]);
    if ((int) $stmt->fetchColumn() >= REPONSES_SANS_AGENT) {
        return ['peut' => false, 'raison' => 'Vous avez déjà envoyé plusieurs réponses. Merci d’attendre le retour de l’agent.'];
    }
    return ['peut' => true, 'raison' => ''];
}

/* ---------------------------------------------------------
   GET : conversation + documents
--------------------------------------------------------- */
if ($methode === 'GET') {
    session_write_close();

    // Messages, avec le document éventuellement joint (visible du citoyen uniquement)
    $stmt = $pdo->prepare("SELECT m.id, m.auteur_type, m.message, m.statut, m.created_at, a.prenom, a.nom,
                                  f.id AS fichier_id, f.nom_fichier
                           FROM messages_demandes m
                           LEFT JOIN agents a ON a.id = m.agent_id
                           LEFT JOIN fichiers_demandes f ON f.message_id = m.id
                                AND (f.agent_id IS NULL OR f.visible_citoyen = 1)
                           WHERE m.demande_id = ?
                           ORDER BY m.created_at ASC, m.id ASC");
    $stmt->execute([$demandeId]);
    $messages = [];
    $nonLus = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $t = strtotime($m['created_at']);
        $estAgent = $m['auteur_type'] === 'agent';
        if ($estAgent && $m['statut'] === 'nouveau') {
            $nonLus++;
        }
        $messages[] = [
            'id'      => (int) $m['id'],
            'auteur'  => $estAgent ? 'agent' : 'citoyen',
            // Côté citoyen : prénom et initiale de l'agent, pas son nom complet
            'nom'     => $estAgent
                ? trim(($m['prenom'] ?? 'Agent') . ' ' . mb_substr($m['nom'] ?? '', 0, 1) . '.') . ' · Mairie'
                : 'Vous',
            'message' => $m['message'],
            'document' => $m['fichier_id'] ? [
                'nom' => $m['nom_fichier'],
                'url' => '../Backend/citoyen/document.php?id=' . (int) $m['fichier_id'],
            ] : null,
            'jour'         => date('Y-m-d', $t),
            'jour_libelle' => libelleGroupeDate(date('Y-m-d', $t)),
            'heure'        => date('H\hi', $t),
        ];
    }

    // Documents : ceux de la mairie destinés au citoyen, et les siens
    $stmt = $pdo->prepare("SELECT id, agent_id, nom_fichier, taille, date_upload FROM fichiers_demandes
                           WHERE demande_id = ? AND (agent_id IS NULL OR visible_citoyen = 1)
                           ORDER BY date_upload DESC, id DESC");
    $stmt->execute([$demandeId]);
    $documents = ['mairie' => [], 'citoyen' => []];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $documents[$f['agent_id'] === null ? 'citoyen' : 'mairie'][] = [
            'id'     => (int) $f['id'],
            'nom'    => $f['nom_fichier'],
            'taille' => (int) $f['taille'],
            'date'   => jourLongFr(strtotime($f['date_upload'])) . ' à ' . date('H\hi', strtotime($f['date_upload'])),
            'url'    => '../Backend/citoyen/document.php?id=' . (int) $f['id'],
        ];
    }

    $droit = droitDeReponse($pdo, $demandeId, $demande['statut']);
    $donnees = ['messages' => $messages, 'documents' => $documents, 'droit' => $droit];
    repondre([
        'success'   => true,
        'signature' => hash('sha256', json_encode($donnees)),
        'non_lus'   => $nonLus,
        'reponse_max' => REPONSE_MAX,
        'taille_max_mo' => round(tailleMaxDocument() / 1048576, 1),
    ] + $donnees);
}

$action = $_POST['action'] ?? '';

/* ---------------------------------------------------------
   POST action=lu : le citoyen a vu les messages de la mairie
--------------------------------------------------------- */
if ($action === 'lu') {
    $pdo->prepare("UPDATE messages_demandes SET statut = 'lu'
                   WHERE demande_id = ? AND auteur_type = 'agent' AND statut = 'nouveau'")
        ->execute([$demandeId]);
    repondre(['success' => true]);
}

/* ---------------------------------------------------------
   POST action=reponse : document et/ou court message
--------------------------------------------------------- */
if ($action !== 'reponse') {
    repondre(['success' => false, 'message' => 'Action inconnue.'], 400);
}

$droit = droitDeReponse($pdo, $demandeId, $demande['statut']);
if (!$droit['peut']) {
    repondre(['success' => false, 'message' => $droit['raison']], 403);
}

// Anti-abus : 1 envoi toutes les 2 secondes, 30 par heure
$maintenant = time();
$_SESSION['messagerie_envois'] = array_values(array_filter(
    $_SESSION['messagerie_envois'] ?? [], fn($t) => $t > $maintenant - 3600));
$dernier = end($_SESSION['messagerie_envois']) ?: 0;
if ($maintenant - $dernier < 2) {
    repondre(['success' => false, 'message' => "Merci de patienter un instant avant d'envoyer à nouveau."], 429);
}
if (count($_SESSION['messagerie_envois']) >= ENVOIS_PAR_HEURE) {
    repondre(['success' => false, 'message' => 'Vous avez envoyé beaucoup de réponses. Merci de réessayer plus tard.'], 429);
}

$texte = trim((string) ($_POST['message'] ?? ''));
if (mb_strlen($texte) > REPONSE_MAX) {
    repondre(['success' => false, 'message' => 'Votre message est trop long (' . REPONSE_MAX . ' caractères maximum).'], 400);
}

$document = null;
if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE) {
    $document = verifierDocument($_FILES['fichier']);
    if (!$document['ok']) {
        repondre(['success' => false, 'message' => $document['message']], $document['code']);
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fichiers_demandes WHERE demande_id = ? AND agent_id IS NULL");
    $stmt->execute([$demandeId]);
    if ((int) $stmt->fetchColumn() >= DOCUMENTS_CITOYEN_MAX) {
        repondre(['success' => false, 'message' => 'Nombre maximal de documents atteint pour cette demande (' . DOCUMENTS_CITOYEN_MAX . ').'], 409);
    }
}
if ($texte === '' && !$document) {
    repondre(['success' => false, 'message' => 'Ajoutez un document ou écrivez un message.'], 400);
}

// Le message (texte du citoyen, ou mention du document s'il n'a rien écrit)
$pdo->prepare("INSERT INTO messages_demandes (demande_id, auteur_type, citoyen_id, message, statut, created_at)
               VALUES (?, 'citoyen', ?, ?, 'nouveau', NOW())")
    ->execute([$demandeId, $citoyenId, $texte !== '' ? $texte : 'Document joint : ' . $document['nom']]);
$messageId = (int) $pdo->lastInsertId();

if ($document) {
    // agent_id NULL = déposé par le citoyen : le compteur de l'agent augmente
    if (enregistrerDocument($pdo, $demandeId, null, $document, true, $messageId) === null) {
        $pdo->prepare("DELETE FROM messages_demandes WHERE id = ?")->execute([$messageId]);
        repondre(['success' => false, 'message' => "Le document n'a pas pu être enregistré. Merci de réessayer."], 500);
    }
    // Trace dans l'historique de l'agent (agent 0 = action du citoyen)
    journaliserAction($pdo, $demandeId, 0, "Ajout d'une pièce jointe par le citoyen", 'Nom du fichier : ' . $document['nom']);
}

$_SESSION['messagerie_envois'][] = $maintenant;
repondre(['success' => true, 'message' => $document ? 'Document envoyé à la mairie.' : 'Message envoyé à la mairie.']);
