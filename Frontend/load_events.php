<?php
require_once __DIR__ . '/../Backend/Config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Récupération des filtres
$categorie = $_GET['categorie'] ?? '';
$lieu = $_GET['lieu'] ?? '';
$public = $_GET['public'] ?? '';

$query = "SELECT * FROM agenda WHERE statut = 'publié'";
$params = [];

// Filtre catégorie
if (!empty($categorie)) {
    $query .= " AND categorie = ?";
    $params[] = $categorie;
}

// Filtre lieu
if (!empty($lieu)) {
    $query .= " AND lieu = ?";
    $params[] = $lieu;
}

// Filtre public
if (!empty($public)) {
    $query .= " AND public_cible = ?";
    $params[] = $public;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$events = [];

while ($event = $stmt->fetch()) {

    // Couleur selon catégorie
    $color = match($event['categorie']) {
        'culture' => '#1b3a61',
        'sport' => '#2e7d32',
        'jeunesse' => '#ef6c00',
        'environnement' => '#1b5e20',
        'ccas' => '#6a1b9a',
        'urbanisme' => '#455a64',
        default => '#1b3a61'
    };

    // Construction de l'événement
    $item = [
        'title' => $event['titre'],
        'url'   => 'evenement.php?slug=' . $event['slug'],
        'color' => $color
    ];

    // Si événement récurrent
    if (!empty($event['recurrence'])) {
        $item['rrule'] = $event['recurrence'];
        $item['duration'] = [
            'hours' => (strtotime($event['date_fin']) - strtotime($event['date_debut'])) / 3600
        ];
    } else {
        // Événement normal
        $item['start'] = $event['date_debut'];
        $item['end']   = $event['date_fin'];
    }

    $events[] = $item;
}

echo json_encode($events);
