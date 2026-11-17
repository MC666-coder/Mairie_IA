<?php
/* =========================================================
   Tableau de bord citoyen : données + gabarits HTML
   ---------------------------------------------------------
   UNE SEULE source pour l'affichage des statistiques et des demandes :
   - Frontend/Portail_citoyen.php l'utilise au chargement de la page ;
   - Backend/citoyen/tableau_de_bord.php l'utilise pour la mise à jour
     automatique (AJAX).
   Tout texte est échappé ici, côté serveur : le JavaScript se contente
   d'insérer un HTML déjà sûr.
   ========================================================= */

const LIBELLES_STATUT = [
    'nouvelle'   => 'Nouvelle',
    'analyse_ia' => 'Analysée par l\'IA',
    'en_cours'   => 'En cours',
    'en_attente' => 'En attente',
    'resolue'    => 'Résolue',
    'cloturee'   => 'Clôturée',
];

const LIBELLES_PRIORITE = [
    'basse'   => 'Basse',
    'normale' => 'Normale',
    'haute'   => 'Haute',
    'urgente' => 'Urgente',
];

/** Échappement HTML court (texte venant de la base ou du citoyen). */
function echapper(?string $texte, string $defaut = 'non renseigné'): string
{
    return htmlspecialchars($texte ?? $defaut, ENT_QUOTES, 'UTF-8');
}

/**
 * Statistiques et demandes du citoyen connecté (uniquement les siennes).
 *
 * @return array{stats: array<string,int>, demandes: list<array>}
 */
function donneesTableauCitoyen(PDO $pdo, int $citoyenId): array
{
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(statut = 'nouvelle'), 0)   AS nouvelle,
            COALESCE(SUM(statut = 'analyse_ia'), 0) AS analyse_ia,
            COALESCE(SUM(statut = 'en_cours'), 0)   AS en_cours,
            COALESCE(SUM(statut = 'resolue'), 0)    AS resolue
        FROM demandes
        WHERE citoyen_id = ?
    ");
    $stmt->execute([$citoyenId]);
    $stats = array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC) ?: []);

    $stmt = $pdo->prepare("
        SELECT d.id, t.libelle AS type, d.message, d.priorite, d.statut, d.created_at,
               d.date_mise_a_jour, s.nom_service, s.description_service,
               -- Messages de la mairie que le citoyen n'a pas encore lus
               (SELECT COUNT(*) FROM messages_demandes m
                WHERE m.demande_id = d.id AND m.auteur_type = 'agent' AND m.statut = 'nouveau') AS messages_non_lus
        FROM demandes d
        LEFT JOIN services s       ON d.service_id = s.id
        LEFT JOIN types_demandes t ON d.type_id = t.id
        WHERE d.citoyen_id = ?
        ORDER BY d.created_at DESC, d.id DESC
    ");
    $stmt->execute([$citoyenId]);

    return ['stats' => $stats, 'demandes' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Empreinte des données : le navigateur ne redessine que si elle change
 * (nouvelle demande, changement de statut, de service...).
 */
function signatureTableauCitoyen(array $donnees): string
{
    return hash('sha256', json_encode($donnees));
}

function htmlStatsCitoyen(array $stats): string
{
    $cartes = [
        'nouvelle'   => 'Nouvelles demandes',
        'analyse_ia' => 'Analysées par l\'IA',
        'en_cours'   => 'En cours',
        'resolue'    => 'Résolues',
    ];
    $html = '';
    foreach ($cartes as $cle => $titre) {
        $html .= '<div class="stat-card"><h3>' . echapper($titre) . '</h3><p>' . (int) ($stats[$cle] ?? 0) . '</p></div>';
    }
    return $html;
}

function htmlDemandesCitoyen(array $demandes): string
{
    if (!$demandes) {
        return "<p class='no-data'>Aucune demande trouvée.</p>";
    }

    $html = '';
    foreach ($demandes as $d) {
        $statut   = LIBELLES_STATUT[$d['statut']] ?? $d['statut'];
        $priorite = LIBELLES_PRIORITE[$d['priorite']] ?? $d['priorite'];
        $date     = $d['created_at'] ? date('d/m/Y à H\hi', strtotime($d['created_at'])) : null;
        $service  = $d['nom_service']
            ? echapper($d['nom_service']) . ' - ' . echapper($d['description_service'], '')
            : 'À déterminer par la mairie';

        $nonLus = (int) ($d['messages_non_lus'] ?? 0);
        $badge = $nonLus > 0
            ? '<a class="badge-message-mairie" href="details_demande.php?id=' . (int) $d['id'] . '#messagerie-citoyen">'
              . '✉️ ' . ($nonLus > 1 ? $nonLus . ' nouveaux messages' : 'Nouveau message') . ' de la mairie</a>'
            : '';

        $html .= '<div class="request-card' . ($nonLus > 0 ? ' a-un-message' : '') . '" data-id="' . (int) $d['id'] . '">'
               . $badge
               . '<p><strong>Type :</strong> ' . echapper($d['type']) . '</p>'
               . '<p><strong>Message :</strong> <span dir="auto">' . nl2br(echapper($d['message'])) . '</span></p>'
               . '<p><strong>Priorité :</strong> ' . echapper($priorite) . '</p>'
               . '<p><strong>Statut :</strong> ' . echapper($statut) . '</p>'
               . '<p><strong>Date :</strong> ' . echapper($date) . '</p>'
               . '<p><strong>Service :</strong> ' . $service . '</p>'
               . '<a class="btn-details" href="details_demande.php?id=' . (int) $d['id'] . '">Voir les détails</a>'
               . '</div>';
    }
    return $html;
}
