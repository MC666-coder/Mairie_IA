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
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

// Événements publiés et visibles des citoyens (table `evenements`)
try {
    $filtres   = filtresAgenda($pdo);
    $aVenir    = evenementsPublics($pdo, ['a_venir' => true], 20);
} catch (PDOException $e) {
    error_log("agenda : lecture des événements impossible");
    $filtres = ['services' => [], 'lieux' => []];
    $aVenir  = [];
}

/** Morceaux d'une date pour la pastille « À venir » : 25 / NOV. / mercredi / 18h00 */
function partiesDate(string $date): array
{
    $t = strtotime($date);
    $mois = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    return [
        'jour'    => date('j', $t),
        'mois'    => $mois[(int) date('n', $t)],
        'semaine' => ucfirst($jours[(int) date('w', $t)]),
        'heure'   => date('H\\hi', $t),
    ];
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

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen" class="page-detail page-agenda">

        <!-- En-tête (dégradé bleu nuit) -->
        <div class="mb-5 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
            <p class="m-0 text-sm font-medium text-blue-100">Votre commune</p>
            <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Agenda municipal</h1>
            <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                Conseils municipaux, marchés, concerts, ateliers : tous les rendez-vous de la commune.
            </p>
        </div>

        <!-- FILTRES (le calendrier se met à jour aussitôt : fullcalendar.js) -->
        <div class="mb-5 flex flex-wrap gap-2 text-left">
            <label class="relative inline-flex items-center">
                <span class="sr-only">Filtrer par service</span>
                <span class="pointer-events-none absolute left-3.5 text-[#003b8e]"><?= icone('tableau') ?></span>
                <select id="filtre-service" class="filtre-pastille">
                    <option value="">Tous les services</option>
                    <?php foreach ($filtres['services'] as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nom_service'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pointer-events-none absolute right-3.5 text-slate-400"><?= icone('chevron-bas') ?></span>
            </label>

            <label class="relative inline-flex items-center">
                <span class="sr-only">Filtrer par lieu</span>
                <span class="pointer-events-none absolute left-3.5 text-[#003b8e]"><?= icone('position') ?></span>
                <select id="filtre-lieu" class="filtre-pastille">
                    <option value="">Tous les lieux</option>
                    <?php foreach ($filtres['lieux'] as $lieu): ?>
                        <option value="<?= htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lieu, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pointer-events-none absolute right-3.5 text-slate-400"><?= icone('chevron-bas') ?></span>
            </label>
        </div>

        <div class="grid items-start gap-6 text-left lg:grid-cols-[minmax(0,1fr)_340px]">

            <!-- CALENDRIER (rempli par assets/js/fullcalendar.js via load_events.php) -->
            <section class="min-w-0 rounded-3xl bg-white p-3 shadow-sm ring-1 ring-slate-900/5 sm:p-5" aria-label="Calendrier des événements">
                <div id="calendar"></div>

                <!-- LÉGENDE : une couleur par service, la même que dans le calendrier -->
                <?php if ($filtres['services']): ?>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        <?php foreach ($filtres['services'] as $s): ?>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-900/5">
                                <span class="size-2.5 rounded-full" style="background:<?= couleurService((int) $s['id']) ?>;"></span>
                                <?= htmlspecialchars($s['nom_service'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- À VENIR : pastille de date à la couleur du service -->
            <aside class="min-w-0 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5" aria-labelledby="titre-a-venir">
                <h2 id="titre-a-venir" class="m-0 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                    <?= icone('calendrier') ?> À venir
                </h2>

                <?php if (!$aVenir): ?>
                    <p class="m-0 mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500 ring-1 ring-slate-900/5">
                        Aucun événement à venir pour le moment.
                    </p>
                <?php else: ?>
                    <ul class="m-0 mt-4 flex list-none flex-col gap-2 p-0">
                        <?php foreach ($aVenir as $event): $date = partiesDate($event['date_debut']); ?>
                            <li>
                                <a href="evenement.php?id=<?= (int) $event['id'] ?>"
                                   class="group flex items-center gap-3 rounded-2xl p-2 no-underline transition hover:bg-slate-50">
                                    <span class="grid w-12 shrink-0 place-items-center rounded-xl py-1.5 leading-none text-white shadow-sm"
                                          style="background:<?= couleurService(isset($event['service_id']) ? (int) $event['service_id'] : null) ?>;">
                                        <span class="text-lg font-extrabold"><?= $date['jour'] ?></span>
                                        <span class="mt-0.5 text-[11px] font-semibold uppercase"><?= $date['mois'] ?></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-slate-900 group-hover:text-[#003b8e]"><?= htmlspecialchars($event['titre'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="block truncate text-xs text-slate-500">
                                            <?= $date['semaine'] ?> · <?= $date['heure'] ?><?php if (!empty($event['lieu'])): ?> · <?= htmlspecialchars($event['lieu'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                        </span>
                                    </span>
                                    <span class="text-slate-300 transition group-hover:text-[#003b8e]"><?= icone('chevron-droite') ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>
        </div>
    </main>


</div>

<?= piedDePage() ?>

<!-- FULLCALENDAR JS -->

<script src="assets/fullcalendar/index.global.min.js?v=<?= @filemtime(__DIR__ . '/assets/fullcalendar/index.global.min.js') ?>"></script>


<!-- SCRIPT D'INITIALISATION (AJAX) -->
<script src="assets/js/fullcalendar.js?v=<?= @filemtime(__DIR__ . '/assets/js/fullcalendar.js') ?>"></script>

</body>
</html>
