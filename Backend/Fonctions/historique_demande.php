<?php
/* =========================================================
   Historique des actions et notes internes d'une demande
   ---------------------------------------------------------
   Une seule source pour :
   - ÉCRIRE une action dans le journal (toujours côté serveur, au moment
     où l'action a lieu : jamais à partir d'un texte envoyé par le
     navigateur) ;
   - LIRE l'historique et les notes ;
   - les AFFICHER (même gabarit pour la page gestion_demande.php et pour
     la mise à jour après une action : Backend/ajax/historique_notes.php).
   Tout texte est échappé ici, côté serveur.
   ========================================================= */

/** Type d'une action, pour les filtres (Statuts, Assignations, Notes). */
function typeAction(string $action): string
{
    if (stripos($action, 'statut') !== false) return 'status';
    if (stripos($action, 'assign') !== false) return 'assignation';
    if (stripos($action, 'note') !== false)   return 'notes';
    return 'other';
}

const LIBELLES_TYPE_ACTION = [
    'status'      => 'Statut',
    'assignation' => 'Assignation',
    'notes'       => 'Note interne',
    'other'       => 'Autre',
];

/** Enregistre une action dans l'historique de la demande. */
function journaliserAction(PDO $pdo, int $demandeId, int $agentId, string $action, ?string $details = null): void
{
    $pdo->prepare("INSERT INTO historique_demandes (demande_id, agent_id, action, details, created_at)
                   VALUES (?, ?, ?, ?, NOW())")
        ->execute([$demandeId, $agentId, $action, $details]);
}

/** Tout l'historique de la demande, du plus récent au plus ancien. */
function historiqueDemande(PDO $pdo, int $demandeId): array
{
    $stmt = $pdo->prepare("SELECT h.id, h.agent_id, h.action, h.details, h.created_at, a.prenom, a.nom
                           FROM historique_demandes h
                           LEFT JOIN agents a ON h.agent_id = a.id
                           WHERE h.demande_id = ?
                           ORDER BY h.created_at DESC, h.id DESC");
    $stmt->execute([$demandeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Toutes les notes internes de la demande, de la plus récente à la plus ancienne. */
function notesDemande(PDO $pdo, int $demandeId): array
{
    $stmt = $pdo->prepare("SELECT n.id, n.agent_id, n.note, n.created_at, a.prenom, a.nom
                           FROM notes_demandes n
                           LEFT JOIN agents a ON n.agent_id = a.id
                           WHERE n.demande_id = ?
                           ORDER BY n.created_at DESC, n.id DESC");
    $stmt->execute([$demandeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   Dates en français (sans dépendre de l'extension intl)
--------------------------------------------------------- */
const JOURS_FR = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const MOIS_FR  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
                  'août', 'septembre', 'octobre', 'novembre', 'décembre'];

/** « mardi 17 novembre 2026 » */
function jourLongFr(int $t): string
{
    return JOURS_FR[(int) date('w', $t)] . ' ' . date('j', $t) . ' ' . MOIS_FR[(int) date('n', $t)] . ' ' . date('Y', $t);
}

/** Titre d'un groupe de dates : « Aujourd'hui · mardi 17 novembre 2026 » */
function libelleGroupeDate(string $jour): string
{
    $t = strtotime($jour);
    $prefixe = match ($jour) {
        date('Y-m-d')                     => "Aujourd'hui · ",
        date('Y-m-d', strtotime('-1 day')) => 'Hier · ',
        default                           => '',
    };
    return $prefixe . jourLongFr($t);
}

/** Initiales d'un agent (« ? » si l'agent a été supprimé). */
function initialesAgent(?string $prenom, ?string $nom): string
{
    $i = mb_strtoupper(mb_substr($prenom ?? '', 0, 1) . mb_substr($nom ?? '', 0, 1));
    return $i !== '' ? $i : '?';
}

/** Regroupe des lignes par jour (AAAA-MM-JJ), en gardant l'ordre. */
function grouperParJour(array $lignes): array
{
    $groupes = [];
    foreach ($lignes as $l) {
        $groupes[substr($l['created_at'], 0, 10)][] = $l;
    }
    return $groupes;
}

/* ---------------------------------------------------------
   Gabarits HTML
--------------------------------------------------------- */
function htmlHistorique(array $historique): string
{
    if (!$historique) {
        return '<p class="history-empty"><em>Aucune action enregistrée pour cette demande.</em></p>';
    }

    $html = '';
    foreach (grouperParJour($historique) as $jour => $actions) {
        $html .= '<div class="history-date-group" data-date="' . htmlspecialchars($jour) . '">'
               . '<div class="history-date-separator"><span>' . htmlspecialchars(libelleGroupeDate($jour)) . '</span></div>';

        foreach ($actions as $h) {
            $t      = strtotime($h['created_at']);
            $type   = typeAction($h['action']);
            $auteur = trim(($h['prenom'] ?? '') . ' ' . ($h['nom'] ?? '')) ?: 'Agent supprimé';

            $html .= '<div class="history-item" data-type="' . $type . '"'
                   . ' data-created-at="' . date('Y-m-d\TH:i:s', $t) . '"'
                   . ' data-text="' . htmlspecialchars($h['action'] . ' ' . ($h['details'] ?? '') . ' ' . $auteur) . '">'
                   . '<div class="history-dot history-dot-' . $type . '"></div>'
                   . '<div class="history-content"><div class="history-header">'
                   . '<div class="history-avatar">' . htmlspecialchars(initialesAgent($h['prenom'], $h['nom'])) . '</div>'
                   . '<div class="history-meta">'
                   . '<span class="history-author">' . htmlspecialchars($auteur) . '</span>'
                   . '<time class="history-time" datetime="' . date('c', $t) . '">'
                   . htmlspecialchars(jourLongFr($t) . ' à ' . date('H\hi', $t)) . '</time>'
                   . '</div>'
                   . '<span class="history-tag history-tag-' . $type . '">' . LIBELLES_TYPE_ACTION[$type] . '</span>'
                   . '</div>'
                   . '<div class="history-body"><strong>' . htmlspecialchars($h['action']) . '</strong><br>'
                   . nl2br(htmlspecialchars($h['details'] ?? '')) . '</div>'
                   . '</div></div>';
        }
        $html .= '</div>';
    }
    return $html;
}

function htmlNotes(array $notes): string
{
    if (!$notes) {
        return '<p class="notes-empty"><em>Aucune note interne pour le moment.</em></p>';
    }

    $html = '';
    foreach (grouperParJour($notes) as $jour => $notesDuJour) {
        $html .= '<div class="notes-date-group" data-date-group="' . htmlspecialchars($jour) . '">'
               . '<div class="notes-date-separator"><span>' . htmlspecialchars(libelleGroupeDate($jour)) . '</span></div>';

        foreach ($notesDuJour as $n) {
            $t      = strtotime($n['created_at']);
            $auteur = trim(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? '')) ?: 'Agent supprimé';

            $html .= '<div class="note-item" data-agent-id="' . (int) $n['agent_id'] . '"'
                   . ' data-created-at="' . date('Y-m-d\TH:i:s', $t) . '"'
                   . ' data-text="' . htmlspecialchars($n['note'] . ' ' . $auteur) . '">'
                   . '<div class="note-timeline-dot"></div>'
                   . '<div class="note-content"><div class="note-header">'
                   . '<div class="note-avatar">' . htmlspecialchars(initialesAgent($n['prenom'], $n['nom'])) . '</div>'
                   . '<div class="note-meta-main">'
                   . '<span class="note-author">' . htmlspecialchars($auteur) . '</span>'
                   . '<time class="note-date" datetime="' . date('c', $t) . '">'
                   . htmlspecialchars(jourLongFr($t) . ' à ' . date('H\hi', $t)) . '</time>'
                   . '</div>'
                   . '<div class="note-tags"><span class="note-tag note-tag-info">Note</span></div>'
                   . '</div>'
                   . '<div class="note-body">' . nl2br(htmlspecialchars($n['note'])) . '</div>'
                   . '</div></div>';
        }
        $html .= '</div>';
    }
    return $html;
}
