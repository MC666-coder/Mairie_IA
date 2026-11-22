<?php
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

/* ---------------------------------------
   Pagination : la 1re page montre l'actualité « À la une »
   puis une grille ; les pages suivantes, seulement la grille.
---------------------------------------- */
const ACTUS_PAR_PAGE = 9;

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

try {
    $total = (int) $pdo->query("SELECT COUNT(*) FROM news WHERE statut = 'publie'")->fetchColumn();
    $nbPages = max(1, (int) ceil($total / ACTUS_PAR_PAGE));
    $page = min($page, $nbPages); // page trop grande : dernière page

    $stmt = $pdo->prepare("SELECT id, titre, resume, image, date_publication
                           FROM news
                           WHERE statut = 'publie'
                           ORDER BY date_publication DESC, id DESC
                           LIMIT ? OFFSET ?");
    $stmt->bindValue(1, ACTUS_PAR_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(2, ($page - 1) * ACTUS_PAR_PAGE, PDO::PARAM_INT);
    $stmt->execute();
    $actualites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Actualités : lecture impossible');
    $total = 0;
    $nbPages = 1;
    $actualites = [];
}

// « À la une » : la plus récente, seulement sur la 1re page
$aLaUne = ($page === 1 && $actualites) ? array_shift($actualites) : null;

/**
 * Adresse de l'image d'une actualité, ou null si elle n'existe pas
 * (nom vérifié par liste blanche de caractères : pas de « ../ » possible).
 */
function imageActualite(?string $nom): ?string
{
    if (!$nom || !preg_match('/^[a-zA-Z0-9._-]+\.(jpe?g|png|webp)$/i', $nom)) {
        return null;
    }
    return is_file(__DIR__ . '/uploads/' . $nom) ? 'uploads/' . rawurlencode($nom) : null;
}

/** « 20 novembre 2026 » */
function dateActualite(?string $date): string
{
    if (!$date) {
        return '';
    }
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
             'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $mois[(int) date('n', $t)] . ' ' . date('Y', $t);
}

/** Visuel d'une actualité : la photo, ou un dégradé avec une icône si elle n'en a pas. */
function visuelActualite(array $actu, string $classes): string
{
    $image = imageActualite($actu['image'] ?? null);
    if ($image !== null) {
        return '<img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy"'
            . ' class="' . $classes . ' object-cover">';
    }
    return '<div class="' . $classes . ' grid place-items-center bg-gradient-to-br from-[#e6eef9] via-blue-100 to-indigo-200 text-[#003b8e]">'
        . icone('megaphone', 'size-9 opacity-70') . '</div>';
}

function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- Content Security Policy -->
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   frame-ancestors 'none';">
</head>

<body>
<div class="page-wrapper">

    <?= enTetePublic('actualites') ?>

    <main id="content_portail_citoyen" class="page-detail">

        <!-- En-tête (dégradé bleu nuit) -->
        <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
            <p class="m-0 text-sm font-medium text-blue-100">Votre commune</p>
            <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Actualités</h1>
            <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                Les informations et annonces publiées par la mairie.
            </p>
        </div>

        <?php if ($total === 0): ?>

            <!-- Aucune actualité publiée -->
            <div class="mx-auto max-w-lg rounded-3xl bg-white px-6 py-10 text-center shadow-sm ring-1 ring-slate-900/5">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-[#e6eef9] text-2xl text-[#003b8e]">
                    <?= icone('megaphone') ?>
                </span>
                <h2 class="m-0 mt-4 text-lg font-bold text-slate-900">Pas encore d’actualité</h2>
                <p class="m-0 mt-1 text-sm text-slate-500">
                    Les annonces de la mairie apparaîtront ici. En attendant, découvrez les prochains rendez-vous de la commune.
                </p>
                <a href="agenda.php"
                   class="mt-5 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[#003b8e] to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white no-underline shadow-md transition hover:brightness-110">
                    <?= icone('calendrier') ?> Voir l’agenda
                </a>
            </div>

        <?php else: ?>

            <?php if ($aLaUne): ?>
                <!-- À LA UNE : la plus récente, en grand -->
                <a href="actualite_details.php?id=<?= (int) $aLaUne['id'] ?>"
                   class="group mb-6 grid overflow-hidden rounded-3xl bg-white text-left no-underline shadow-sm ring-1 ring-slate-900/5 transition hover:shadow-md md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                    <?= visuelActualite($aLaUne, 'h-56 w-full md:h-full md:min-h-72') ?>
                    <div class="flex flex-col justify-center gap-3 p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-full bg-[#e6eef9] px-2.5 py-1 font-bold text-[#003b8e]">À la une</span>
                            <span class="text-slate-500"><?= e(dateActualite($aLaUne['date_publication'])) ?></span>
                        </div>
                        <h2 class="m-0 text-xl font-extrabold tracking-tight text-slate-900 group-hover:text-[#003b8e] sm:text-2xl">
                            <?= e($aLaUne['titre']) ?>
                        </h2>
                        <p class="m-0 line-clamp-4 text-sm text-slate-600 sm:text-base"><?= e($aLaUne['resume']) ?></p>
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#003b8e]">
                            Lire l’article <?= icone('fleche-droite', 'transition group-hover:translate-x-1') ?>
                        </span>
                    </div>
                </a>
            <?php endif; ?>

            <?php if ($actualites): ?>
                <!-- GRILLE : les suivantes -->
                <?php if ($page === 1): ?>
                    <h2 class="m-0 mb-4 text-left text-lg font-bold text-slate-900">Les autres actualités</h2>
                <?php endif; ?>
                <ul class="m-0 grid list-none gap-5 p-0 text-left sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($actualites as $actu): ?>
                        <li class="min-w-0">
                            <a href="actualite_details.php?id=<?= (int) $actu['id'] ?>"
                               class="group flex h-full flex-col overflow-hidden rounded-3xl bg-white no-underline shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:shadow-md">
                                <?= visuelActualite($actu, 'h-40 w-full') ?>
                                <div class="flex flex-1 flex-col gap-2 p-5">
                                    <span class="text-xs text-slate-500"><?= e(dateActualite($actu['date_publication'])) ?></span>
                                    <h3 class="m-0 line-clamp-2 text-base font-bold text-slate-900 group-hover:text-[#003b8e]"><?= e($actu['titre']) ?></h3>
                                    <p class="m-0 line-clamp-3 text-sm text-slate-600"><?= e($actu['resume']) ?></p>
                                    <span class="mt-auto inline-flex items-center gap-1.5 pt-2 text-sm font-semibold text-[#003b8e]">
                                        Lire la suite <?= icone('fleche-droite', 'transition group-hover:translate-x-1') ?>
                                    </span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($nbPages > 1): ?>
                <!-- PAGINATION -->
                <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="Pages des actualités">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 no-underline shadow-sm ring-1 ring-slate-900/10 transition hover:text-[#003b8e]">
                            <?= icone('fleche-gauche') ?> Plus récentes
                        </a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $nbPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span aria-current="page" class="grid size-10 place-items-center rounded-full bg-gradient-to-r from-[#003b8e] to-indigo-600 text-sm font-bold text-white shadow-sm"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $p ?>" class="grid size-10 place-items-center rounded-full bg-white text-sm font-semibold text-slate-700 no-underline shadow-sm ring-1 ring-slate-900/10 transition hover:text-[#003b8e]"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $nbPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 no-underline shadow-sm ring-1 ring-slate-900/10 transition hover:text-[#003b8e]">
                            Plus anciennes <?= icone('fleche-droite') ?>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </main>

</div>

<?= piedDePage() ?>

</body>
</html>
