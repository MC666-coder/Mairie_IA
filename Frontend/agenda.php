<?php
// Sécurisation cohérente avec tout le portail
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/evenements_publics.php';

// Événements publiés et visibles des citoyens (table `evenements`)
try {
    $filtres   = filtresAgenda($pdo);
    $aVenir    = evenementsPublics($pdo, ['a_venir' => true], 20);
} catch (PDOException $e) {
    error_log("agenda : lecture des événements impossible");
    $filtres = ['services' => [], 'lieux' => []];
    $aVenir  = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda municipal - Commune de L’Aiguillon-sur-Vie</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
    <!-- FullCalendar 6 injecte lui-même ses styles : pas de fichier CSS à charger -->

    <!-- Content Security Policy stricte -->
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   font-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   frame-ancestors 'none';">
</head>

<body>
<div class="page-wrapper">

    <?= enTetePublic('agenda') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Agenda municipal</h1>
            <p>Découvrez tous les événements organisés par la commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="agenda-premium">
            <h2 class="section-title">Calendrier & Événements</h2>
            <p class="section-subtitle">Consultez les événements à venir dans votre commune.</p>

            <!-- FILTRES AVANCÉS -->
            <div class="agenda-filtres">
                <label class="visually-hidden" for="filtre-service">Filtrer par service</label>
                <select id="filtre-service">
                    <option value="">Tous les services</option>
                    <?php foreach ($filtres['services'] as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="visually-hidden" for="filtre-lieu">Filtrer par lieu</label>
                <select id="filtre-lieu">
                    <option value="">Tous les lieux</option>
                    <?php foreach ($filtres['lieux'] as $lieu): ?>
                        <option value="<?= htmlspecialchars($lieu) ?>"><?= htmlspecialchars($lieu) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="agenda-grid">

                <!-- CALENDRIER (rempli par assets/js/fullcalendar.js via load_events.php) -->
                <div class="agenda-calendar">
                    <div id="calendar"></div>

                    <!-- LÉGENDE : une couleur par service, la même que dans le calendrier -->
                    <?php if ($filtres['services']): ?>
                        <div class="agenda-legende">
                            <?php foreach ($filtres['services'] as $s): ?>
                                <span><span class="legende-couleur" style="background:<?= couleurService((int) $s['id']) ?>;"></span>
                                    <?= htmlspecialchars($s['nom_service']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- LISTE D'ÉVÉNEMENTS -->
                <div class="agenda-list">
                    <h3 class="list-title">Événements à venir</h3>

                    <?php if (!$aVenir): ?>
                        <p class="no-data">Aucun événement à venir pour le moment.</p>
                    <?php endif; ?>

                    <?php foreach ($aVenir as $event): ?>
                        <div class="event-card">
                            <div class="event-info">
                                <h4><?= htmlspecialchars($event['titre']) ?></h4>
                                <p class="event-date"><?= htmlspecialchars(dateEvenement($event['date_debut'])) ?></p>
                                <?php if (!empty($event['lieu'])): ?>
                                    <p class="event-lieu"><?= htmlspecialchars($event['lieu']) ?></p>
                                <?php endif; ?>
                                <a href="evenement.php?id=<?= (int) $event['id'] ?>" class="btn">Voir l’événement</a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

            </div>
        </section>

    </main>


</div>

<?= piedDePage() ?>

<!-- FULLCALENDAR JS -->

<script src="assets/fullcalendar/index.global.min.js?v=<?= @filemtime(__DIR__ . '/assets/fullcalendar/index.global.min.js') ?>"></script>


<!-- SCRIPT D'INITIALISATION (AJAX) -->
<script src="assets/js/fullcalendar.js?v=<?= @filemtime(__DIR__ . '/assets/js/fullcalendar.js') ?>"></script>

</body>
</html>
