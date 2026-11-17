<?php
/* =========================================================
   Événements publics de la commune (agenda)
   ---------------------------------------------------------
   Une seule règle de visibilité pour tout le site : un événement n'est
   montré au public que s'il est PUBLIÉ et VISIBLE DES CITOYENS.
   Utilisé par : Frontend/agenda.php (liste + filtres),
                 Frontend/load_events.php (calendrier, JSON),
                 Frontend/evenement.php (page de détail).
   ========================================================= */

const CONDITION_EVENEMENT_PUBLIC = "e.statut = 'publie' AND e.visible_citoyens = 1";

/** Palette accessible (contraste suffisant avec du texte blanc), au moins
 *  une couleur par service pour éviter que deux services se ressemblent. */
const COULEURS_SERVICES = [
    '#1b3a61', '#2e7d32', '#b34700', '#6a1b9a', '#455a64', '#00695c', '#8e2440', '#5d4037',
    '#0d47a1', '#33691e', '#ad1457', '#4527a0', '#00838f', '#6d4c41', '#c62828', '#37474f',
];

/** Couleur stable d'un service (même service = même couleur partout). */
function couleurService(?int $serviceId): string
{
    return COULEURS_SERVICES[($serviceId ?? 0) % count(COULEURS_SERVICES)];
}

/**
 * Événements publics, avec filtres facultatifs.
 *
 * @param array{service?: int, lieu?: string, debut?: string, fin?: string, a_venir?: bool} $filtres
 */
function evenementsPublics(PDO $pdo, array $filtres = [], int $limite = 500): array
{
    $sql = "SELECT e.id, e.titre, e.description, e.version_courte, e.version_longue,
                   e.date_debut, e.date_fin, e.lieu, e.image_principale,
                   e.service_id, s.nom_service
            FROM evenements e
            LEFT JOIN services s ON s.id = e.service_id
            WHERE " . CONDITION_EVENEMENT_PUBLIC;
    $params = [];

    if (!empty($filtres['service'])) {
        $sql .= " AND e.service_id = ?";
        $params[] = (int) $filtres['service'];
    }
    if (!empty($filtres['lieu'])) {
        $sql .= " AND e.lieu = ?";
        $params[] = (string) $filtres['lieu'];
    }
    // Période affichée par le calendrier : événements qui la chevauchent
    if (!empty($filtres['debut']) && !empty($filtres['fin'])) {
        $sql .= " AND e.date_fin >= ? AND e.date_debut < ?";
        $params[] = $filtres['debut'];
        $params[] = $filtres['fin'];
    }
    if (!empty($filtres['a_venir'])) {
        $sql .= " AND e.date_fin >= NOW()";
    }

    $sql .= " ORDER BY e.date_debut ASC LIMIT " . max(1, min($limite, 1000));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Un événement public par son identifiant (null s'il n'est pas public). */
function evenementPublic(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT e.*, s.nom_service
                           FROM evenements e
                           LEFT JOIN services s ON s.id = e.service_id
                           WHERE e.id = ? AND " . CONDITION_EVENEMENT_PUBLIC);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** Services et lieux ayant des événements publics à venir (pour les filtres). */
function filtresAgenda(PDO $pdo): array
{
    $services = $pdo->query("SELECT DISTINCT s.id, s.nom_service
                             FROM evenements e JOIN services s ON s.id = e.service_id
                             WHERE " . CONDITION_EVENEMENT_PUBLIC . " AND e.date_fin >= NOW()
                             ORDER BY s.nom_service")->fetchAll(PDO::FETCH_ASSOC);
    $lieux = $pdo->query("SELECT DISTINCT e.lieu FROM evenements e
                          WHERE " . CONDITION_EVENEMENT_PUBLIC . " AND e.date_fin >= NOW()
                            AND e.lieu IS NOT NULL AND e.lieu <> ''
                          ORDER BY e.lieu")->fetchAll(PDO::FETCH_COLUMN);
    return ['services' => $services, 'lieux' => $lieux];
}

/** Date lisible : « samedi 21 novembre 2026 à 14h00 » (sans extension intl). */
function dateEvenement(string $date, bool $avecHeure = true): string
{
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
              'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $t = strtotime($date);
    $texte = $jours[(int) date('w', $t)] . ' ' . date('j', $t) . ' ' . $mois[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $avecHeure ? $texte . ' à ' . date('H\hi', $t) : $texte;
}
