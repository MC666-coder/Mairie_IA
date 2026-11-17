<?php
/* =========================================================
   Agenda : événements publics au format FullCalendar (JSON)
   ---------------------------------------------------------
   GET, lecture seule, accessible sans connexion (agenda public).
   FullCalendar envoie la période affichée (start / end) : on ne charge
   que les événements qui la chevauchent.
   ========================================================= */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/evenements_publics.php';

/** Date ISO envoyée par FullCalendar -> « AAAA-MM-JJ HH:MM:SS », sinon null. */
function dateValide(?string $valeur): ?string
{
    if (!$valeur) {
        return null;
    }
    $t = strtotime($valeur);
    return $t ? date('Y-m-d H:i:s', $t) : null;
}

$filtres = [
    'service' => filter_input(INPUT_GET, 'service', FILTER_VALIDATE_INT) ?: null,
    'lieu'    => mb_substr(trim((string) ($_GET['lieu'] ?? '')), 0, 255),
    'debut'   => dateValide($_GET['start'] ?? null),
    'fin'     => dateValide($_GET['end'] ?? null),
];

try {
    $evenements = evenementsPublics($pdo, $filtres);
} catch (PDOException $e) {
    error_log("load_events : lecture des événements impossible");
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$resultat = [];
foreach ($evenements as $e) {
    $couleur = couleurService($e['service_id'] !== null ? (int) $e['service_id'] : null);
    $resultat[] = [
        'id'              => (int) $e['id'],
        'title'           => $e['titre'],                 // affiché en texte par FullCalendar
        // Heure locale de la mairie, SANS fuseau : FullCalendar l'affiche
        // telle quelle (14h00 reste 14h00, quel que soit le fuseau du serveur)
        'start'           => date('Y-m-d\TH:i:s', strtotime($e['date_debut'])),
        'end'             => date('Y-m-d\TH:i:s', strtotime($e['date_fin'])),
        'url'             => 'evenement.php?id=' . (int) $e['id'],
        'backgroundColor' => $couleur,
        'borderColor'     => $couleur,
        'extendedProps'   => ['lieu' => $e['lieu'], 'service' => $e['nom_service']],
    ];
}

echo json_encode($resultat, JSON_UNESCAPED_UNICODE);
