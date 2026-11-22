<?php
// Nom de la commune, coordonnées : lus dans .env (Backend/Config/app.php)
require_once __DIR__ . '/../Backend/Config/app.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/horaires_mairie.php'; // horaires et « Ouvert / Fermé »

$commune = htmlspecialchars(NOM_COMMUNE, ENT_QUOTES, 'UTF-8');
$etat = etatOuverture();

function e(string $texte): string
{
    return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

// Itinéraire : recherche de l'adresse sur OpenStreetMap (nouvel onglet)
$lienItineraire = 'https://www.openstreetmap.org/search?query=' . rawurlencode(MAIRIE_ADRESSE);

/* Les trois façons de joindre la mairie, de la plus suivie à la plus directe */
$moyens = [
    [
        'icone' => 'stylo',
        'titre' => 'Faire une demande',
        'texte' => 'Pour une démarche ou un signalement. Vous suivez son avancement et recevez la réponse écrite dans votre espace.',
        'atout' => 'Suivi en ligne',
        'lien'  => 'nouvelle_demande.php',
        'action' => 'Créer une demande',
    ],
    [
        'icone' => 'assistant',
        'titre' => 'Demander à l’assistant',
        'texte' => 'Pour une question rapide : horaires, démarches, bon service à contacter. Il peut aussi préparer votre demande.',
        'atout' => 'Réponse immédiate, 24h/24',
        'lien'  => 'accès_chatbot.php',
        'action' => 'Ouvrir l’assistant',
    ],
    [
        'icone' => 'telephone',
        'titre' => 'Appeler la mairie',
        'texte' => 'Pour parler à un agent de l’accueil, aux horaires d’ouverture.',
        'atout' => MAIRIE_TELEPHONE,
        'lien'  => 'tel:' . preg_replace('/[^0-9+]/', '', MAIRIE_TELEPHONE),
        'action' => 'Appeler',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - Mairie de <?= $commune ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('contacts') ?>

        <main id="content_portail_citoyen" class="page-detail">

            <!-- En-tête (dégradé bleu nuit) -->
            <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
                <p class="m-0 text-sm font-medium text-blue-100">Mairie de <?= $commune ?></p>
                <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Contacter la mairie</h1>
                <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                    Choisissez le moyen le plus adapté à votre besoin.
                </p>
            </div>

            <!-- LES TROIS MOYENS DE CONTACT -->
            <h2 class="m-0 mb-4 text-left text-lg font-bold text-slate-900">Comment nous joindre ?</h2>
            <ul class="m-0 grid list-none gap-4 p-0 text-left md:grid-cols-3">
                <?php foreach ($moyens as $moyen): ?>
                    <li class="min-w-0">
                        <a href="<?= e($moyen['lien']) ?>"
                           class="group flex h-full flex-col rounded-3xl bg-white p-5 no-underline shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-[#003b8e]/20">
                            <span class="grid size-11 place-items-center rounded-2xl bg-[#e6eef9] text-xl text-[#003b8e] transition group-hover:bg-gradient-to-br group-hover:from-[#003b8e] group-hover:to-indigo-600 group-hover:text-white">
                                <?= icone($moyen['icone']) ?>
                            </span>
                            <span class="mt-3 text-base font-bold text-slate-900"><?= e($moyen['titre']) ?></span>
                            <span class="mt-1 text-sm text-slate-600"><?= e($moyen['texte']) ?></span>
                            <span class="mt-3 w-fit rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700"><?= e($moyen['atout']) ?></span>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-[#003b8e]">
                                <?= e($moyen['action']) ?> <?= icone('fleche-droite', 'transition group-hover:translate-x-1') ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- ADRESSE, E-MAIL, HORAIRES -->
            <div class="mt-6 grid gap-4 text-left md:grid-cols-2">

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5" aria-labelledby="titre-adresse">
                    <h2 id="titre-adresse" class="m-0 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                        <?= icone('position') ?> Nous rendre visite
                    </h2>
                    <p class="m-0 mt-3 font-semibold text-slate-900">Mairie de <?= $commune ?></p>
                    <p class="m-0 mt-0.5 text-sm text-slate-600"><?= e(MAIRIE_ADRESSE) ?></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="<?= e($lienItineraire) ?>" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 rounded-full bg-[#e6eef9] px-4 py-2 text-sm font-semibold text-[#003b8e] no-underline transition hover:bg-[#d7e4f7]">
                            <?= icone('position') ?> Itinéraire <span class="sr-only">(nouvel onglet)</span>
                        </a>
                        <a href="mailto:<?= e(MAIRIE_EMAIL) ?>"
                           class="inline-flex min-w-0 items-center gap-1.5 rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold break-all text-slate-700 no-underline transition hover:bg-slate-200">
                            <?= icone('courrier') ?> <?= e(MAIRIE_EMAIL) ?>
                        </a>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5" aria-labelledby="titre-horaires">
                    <h2 id="titre-horaires" class="m-0 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                        <?= icone('horloge') ?> Horaires d’ouverture
                    </h2>
                    <p class="m-0 mt-3 flex flex-wrap items-center gap-2"><?= pastilleOuverture($etat) ?></p>
                    <dl class="m-0 mt-4 grid gap-2 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Lundi – vendredi</dt>
                            <dd class="m-0 text-right font-medium text-slate-800">
                                <?php foreach (CRENEAUX as $i => [$debut, $fin]): ?>
                                    <?= $i > 0 ? '<br>' : '' ?><?= heureLisible($debut) ?> – <?= heureLisible($fin) ?>
                                <?php endforeach; ?>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Samedi, dimanche</dt>
                            <dd class="m-0 font-medium text-slate-800">Fermé</dd>
                        </div>
                    </dl>
                    <p class="m-0 mt-4 text-xs text-slate-500">En dehors des horaires, l’assistant en ligne répond à vos questions.</p>
                </section>
            </div>
        </main>

    </div>

    <?= piedDePage() ?>

</body>
</html>
