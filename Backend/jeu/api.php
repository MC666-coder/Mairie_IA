<?php
/* =========================================================
   API DU JEU D'ÉNIGMES (application React : Frontend/jeu/)
   ---------------------------------------------------------
   GET  ?action=etat                  parcours, score, classement
   POST action=repondre  enigme_id, reponse
   POST action=indice    enigme_id     (les points sont divisés par 2)
   POST action=reveler   enigme_id     (0 point, on passe à la suivante)
   POST action=pseudo    pseudo        (nom affiché dans le classement)

   Sécurité :
   - citoyen connecté (sinon 401 en JSON) ;
   - jeton CSRF sur tous les POST (en-tête X-CSRF-Token) ;
   - les RÉPONSES ne quittent jamais le serveur tant que l'énigme
     n'est pas terminée (sinon on les lirait avec F12) ;
   - on ne peut jouer qu'une énigme débloquée ;
   - limite anti-abus : 40 réponses par minute ;
   - classement : pseudonymes uniquement, jamais le nom ou l'e-mail.
   ========================================================= */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Middleware/auth.php';
require_once __DIR__ . '/../Fonctions/session_citoyen.php';
require_once __DIR__ . '/../Fonctions/jeu_enigmes.php';

const ACTIONS_PAR_MINUTE = 40;
const TAILLE_CLASSEMENT  = 10;

function repondreJson(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 1. Citoyen connecté (exigerCitoyen répond 401 en JSON pour un appel AJAX) */
$citoyenId = exigerCitoyen();

/* 2. Jeton CSRF sur les POST */
verifier_csrf();

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['action'] ?? '') : (string) ($_GET['action'] ?? 'etat');

/* 3. Limite anti-abus sur les actions qui modifient la partie */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fenetre = (int) floor(time() / 60);
    if (($_SESSION['jeu_fenetre'] ?? 0) !== $fenetre) {
        $_SESSION['jeu_fenetre'] = $fenetre;
        $_SESSION['jeu_actions'] = 0;
    }
    if (++$_SESSION['jeu_actions'] > ACTIONS_PAR_MINUTE) {
        repondreJson(['success' => false, 'message' => 'Doucement ! Patientez une minute avant de rejouer.'], 429);
    }
}

/* ---------------------------------------------------------
   Lecture de la partie
--------------------------------------------------------- */
function enigmesActives(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM enigmes WHERE actif = 1 ORDER BY ordre, id")->fetchAll(PDO::FETCH_ASSOC);
}

function progressionCitoyen(PDO $pdo, int $citoyenId): array
{
    $stmt = $pdo->prepare("SELECT enigme_id, statut, tentatives, indice_utilise, score FROM enigme_scores WHERE citoyens_id = ?");
    $stmt->execute([$citoyenId]);
    $lignes = [];
    foreach ($stmt as $l) {
        $lignes[(int) $l['enigme_id']] = $l;
    }
    return $lignes;
}

/** Adresse publique d'une image d'énigme (nom de fichier vérifié : pas de « ../ »). */
function urlImage(?string $image): ?string
{
    if (!$image || !preg_match('/^[a-zA-Z0-9._-]+\.(svg|png|jpe?g|webp)$/i', $image)) {
        return null;
    }
    return 'assets/images/jeu/' . $image;
}

/** État complet du jeu pour l'application React. */
function etatJeu(PDO $pdo, int $citoyenId): array
{
    $enigmes     = enigmesActives($pdo);
    $progression = progressionCitoyen($pdo, $citoyenId);
    $statuts     = statutsParcours($enigmes, array_map(fn ($p) => $p['statut'], $progression));

    $parcours = [];
    foreach ($enigmes as $e) {
        $id     = (int) $e['id'];
        $statut = $statuts[$id];
        $p      = $progression[$id] ?? ['tentatives' => 0, 'indice_utilise' => 0, 'score' => 0];

        $carte = [
            'id'          => $id,
            'titre'       => $e['titre'],
            'lieu'        => $e['lieu'],
            'type'        => $e['type'],
            'niveau'      => $e['niveau'],
            'points_max'  => pointsMaximum($e),
            'statut'      => $statut,
            'points'      => (int) $p['score'],
        ];

        if ($statut === 'verrouillee') {
            // Énigme pas encore débloquée : ni titre ni lieu (ils donnent déjà des indices)
            $carte['titre'] = '';
            $carte['lieu']  = null;
        } else {
            $carte['question'] = $e['question'];
            $carte['image']    = urlImage($e['image']);
        }
        if ($statut === 'disponible') {
            $carte['tentatives']     = (int) $p['tentatives'];
            $carte['essais_max']     = essaisMaximum($e);
            $carte['indice']         = $p['indice_utilise'] ? $e['indice'] : null;
            $carte['indice_existe']  = !empty($e['indice']);
            $carte['points_en_jeu']  = pointsGagnes($e, (int) $p['tentatives'], (bool) $p['indice_utilise']);
        }
        if (in_array($statut, ['resolue', 'revelee'], true)) {
            // La réponse n'est envoyée qu'une fois l'énigme terminée
            $carte['reponse']     = $e['reponse'];
            $carte['explication'] = $e['explication'];
        }
        $parcours[] = $carte;
    }

    // Pseudo et score du joueur
    $stmt = $pdo->prepare("SELECT pseudo_jeu FROM citoyens WHERE id = ?");
    $stmt->execute([$citoyenId]);
    $pseudo = $stmt->fetchColumn() ?: null;

    return [
        'success'     => true,
        'pseudo'      => $pseudo,
        'score'       => array_sum(array_map(fn ($c) => $c['points'], $parcours)),
        'score_max'   => array_sum(array_map(fn ($c) => $c['points_max'], $parcours)),
        'parcours'    => $parcours,
        'classement'  => classement($pdo, $citoyenId),
    ];
}

/** Top 10 (pseudonymes seulement) + rang du joueur. */
function classement(PDO $pdo, int $citoyenId): array
{
    $lignes = $pdo->query("
        SELECT c.id, c.pseudo_jeu AS pseudo, SUM(s.score) AS score,
               SUM(s.statut = 'resolue') AS trouvees, MAX(s.termine_at) AS derniere
        FROM enigme_scores s
        JOIN citoyens c ON c.id = s.citoyens_id
        WHERE c.pseudo_jeu IS NOT NULL
        GROUP BY c.id, c.pseudo_jeu
        HAVING score > 0
        ORDER BY score DESC, derniere ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $top = [];
    $monRang = null;
    foreach ($lignes as $i => $l) {
        $rang = $i + 1;
        $moi = (int) $l['id'] === $citoyenId;
        if ($moi) {
            $monRang = $rang;
        }
        if ($rang <= TAILLE_CLASSEMENT) {
            $top[] = ['rang' => $rang, 'pseudo' => $l['pseudo'], 'score' => (int) $l['score'], 'trouvees' => (int) $l['trouvees'], 'moi' => $moi];
        }
    }
    return ['top' => $top, 'mon_rang' => $monRang, 'joueurs' => count($lignes)];
}

/** Énigme jouable par ce citoyen (débloquée et pas terminée), sinon erreur. */
function enigmeJouable(PDO $pdo, int $citoyenId, int $enigmeId): array
{
    $enigmes = enigmesActives($pdo);
    $statuts = statutsParcours($enigmes, array_map(fn ($p) => $p['statut'], progressionCitoyen($pdo, $citoyenId)));
    if (($statuts[$enigmeId] ?? null) !== 'disponible') {
        repondreJson(['success' => false, 'message' => 'Cette énigme n’est pas disponible.'], 403);
    }
    foreach ($enigmes as $e) {
        if ((int) $e['id'] === $enigmeId) {
            return $e;
        }
    }
    repondreJson(['success' => false, 'message' => 'Énigme introuvable.'], 404);
}

/** Ligne de progression (créée au premier essai). */
function ligneProgression(PDO $pdo, int $citoyenId, int $enigmeId): array
{
    $pdo->prepare("INSERT IGNORE INTO enigme_scores (citoyens_id, enigme_id) VALUES (?, ?)")->execute([$citoyenId, $enigmeId]);
    $stmt = $pdo->prepare("SELECT * FROM enigme_scores WHERE citoyens_id = ? AND enigme_id = ?");
    $stmt->execute([$citoyenId, $enigmeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   Actions
--------------------------------------------------------- */

// Première partie : le pseudonyme du classement doit être choisi avant de jouer
if (in_array($action, ['repondre', 'indice', 'reveler'], true)) {
    $stmt = $pdo->prepare("SELECT pseudo_jeu FROM citoyens WHERE id = ?");
    $stmt->execute([$citoyenId]);
    if (!$stmt->fetchColumn()) {
        repondreJson(['success' => false, 'message' => 'Choisissez d’abord votre pseudonyme pour commencer à jouer.'], 403);
    }
}

switch ($action) {
    case 'etat':
        repondreJson(etatJeu($pdo, $citoyenId));

    case 'repondre':
        $enigmeId = (int) ($_POST['enigme_id'] ?? 0);
        $reponse  = mb_substr(trim((string) ($_POST['reponse'] ?? '')), 0, REPONSE_LONGUEUR_MAX, 'UTF-8');
        if ($reponse === '') {
            repondreJson(['success' => false, 'message' => 'Écrivez une réponse.'], 422);
        }
        $enigme = enigmeJouable($pdo, $citoyenId, $enigmeId);
        $ligne  = ligneProgression($pdo, $citoyenId, $enigmeId);

        if ($enigme['type'] === 'date' && !preg_match('/^\d{1,4}$/', $reponse)) {
            repondreJson(['success' => false, 'message' => 'Répondez par une année, par exemple 1850.'], 422);
        }

        $resultat = verifierReponse($enigme, $reponse);
        if ($resultat['correct']) {
            $points = pointsGagnes($enigme, (int) $ligne['tentatives'], (bool) $ligne['indice_utilise']);
            $pdo->prepare("UPDATE enigme_scores SET statut = 'resolue', score = ?, termine_at = NOW() WHERE id = ?")
                ->execute([$points, $ligne['id']]);
            repondreJson(['success' => true, 'correct' => true, 'points' => $points,
                          'message' => 'Bravo ! +' . $points . ' points.', 'etat' => etatJeu($pdo, $citoyenId)]);
        }

        // Mauvaise réponse : un essai de plus ; au-delà du maximum, la réponse est donnée
        $tentatives = (int) $ligne['tentatives'] + 1;
        $epuise = $tentatives >= essaisMaximum($enigme);
        $pdo->prepare("UPDATE enigme_scores SET tentatives = ?, statut = ?, termine_at = ? WHERE id = ?")
            ->execute([$tentatives, $epuise ? 'revelee' : 'en_cours', $epuise ? date('Y-m-d H:i:s') : null, $ligne['id']]);

        $message = match ($resultat['indication']) {
            'plus_ancien' => 'Ce n’est pas ça : c’est plus ancien.',
            'plus_recent' => 'Ce n’est pas ça : c’est plus récent.',
            default       => 'Ce n’est pas la bonne réponse. Essayez encore !',
        };
        if ($epuise) {
            $message = 'Plus d’essais pour cette énigme : la réponse est affichée, et la suivante est débloquée.';
        }
        repondreJson(['success' => true, 'correct' => false, 'indication' => $resultat['indication'],
                      'message' => $message, 'etat' => etatJeu($pdo, $citoyenId)]);

    case 'indice':
        $enigmeId = (int) ($_POST['enigme_id'] ?? 0);
        $enigme = enigmeJouable($pdo, $citoyenId, $enigmeId);
        if (empty($enigme['indice'])) {
            repondreJson(['success' => false, 'message' => 'Pas d’indice pour cette énigme.'], 404);
        }
        $ligne = ligneProgression($pdo, $citoyenId, $enigmeId);
        $pdo->prepare("UPDATE enigme_scores SET indice_utilise = 1 WHERE id = ?")->execute([$ligne['id']]);
        repondreJson(['success' => true, 'etat' => etatJeu($pdo, $citoyenId)]);

    case 'reveler':
        $enigmeId = (int) ($_POST['enigme_id'] ?? 0);
        enigmeJouable($pdo, $citoyenId, $enigmeId);
        $ligne = ligneProgression($pdo, $citoyenId, $enigmeId);
        $pdo->prepare("UPDATE enigme_scores SET statut = 'revelee', score = 0, termine_at = NOW() WHERE id = ?")->execute([$ligne['id']]);
        repondreJson(['success' => true, 'etat' => etatJeu($pdo, $citoyenId)]);

    case 'pseudo':
        $pseudo = trim((string) ($_POST['pseudo'] ?? ''));
        if (!pseudoValide($pseudo)) {
            repondreJson(['success' => false, 'message' => 'Pseudonyme : 3 à 20 caractères (lettres, chiffres, espace, point, tiret).'], 422);
        }
        try {
            $pdo->prepare("UPDATE citoyens SET pseudo_jeu = ? WHERE id = ?")->execute([$pseudo, $citoyenId]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) { // déjà pris (index unique)
                repondreJson(['success' => false, 'message' => 'Ce pseudonyme est déjà pris. Choisissez-en un autre.'], 409);
            }
            throw $e;
        }
        repondreJson(['success' => true, 'etat' => etatJeu($pdo, $citoyenId)]);

    default:
        repondreJson(['success' => false, 'message' => 'Action inconnue.'], 400);
}
