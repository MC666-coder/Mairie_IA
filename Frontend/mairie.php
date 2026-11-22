<?php
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Config/app.php';                // coordonnées (.env)
require_once __DIR__ . '/../Backend/Fonctions/horaires_mairie.php'; // horaires et « Ouvert / Fermé »

/* ---------------------------------------
   Services présentés sur la page (une carte par service)
---------------------------------------- */
$services = [
    ['nom' => 'État civil',           'description' => 'Actes de naissance, mariage, décès.',                       'icone' => 'carte-identite'],
    ['nom' => 'Urbanisme',            'description' => 'Permis de construire, déclarations préalables.',             'icone' => 'maison'],
    ['nom' => 'Voirie et travaux',    'description' => 'Entretien des routes, signalisation, éclairage public.',     'icone' => 'cone'],
    ['nom' => 'Environnement',        'description' => 'Déchets, espaces verts, nuisances sonores.',                 'icone' => 'feuille'],
    ['nom' => 'CCAS / Social',        'description' => 'Accompagnement social, aides financières, logement.',        'icone' => 'coeur'],
    ['nom' => 'Scolaire et jeunesse', 'description' => 'Inscriptions scolaires, cantine, garderie.',                 'icone' => 'ecole'],
    ['nom' => 'Communication',        'description' => 'Diffusion des informations municipales.',                    'icone' => 'megaphone'],
    ['nom' => 'Culture et sport',     'description' => 'Activités culturelles, associations, équipements sportifs.', 'icone' => 'musique'],
    ['nom' => 'Finances',             'description' => 'Paiements, factures, taxes locales, budget.',                'icone' => 'euro'],
    ['nom' => 'Ressources humaines',  'description' => 'Recrutement, gestion du personnel municipal.',               'icone' => 'groupe'],
];

$etat = etatOuverture();

/* Coordonnées de la mairie : MAIRIE_TELEPHONE et MAIRIE_EMAIL dans .env (Backend/Config/app.php) */
$telephone = MAIRIE_TELEPHONE;
$email     = MAIRIE_EMAIL;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La mairie - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('mairie') ?>

        <main id="content_portail_citoyen" class="page-detail">

            <!-- En-tête (dégradé bleu nuit) -->
            <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
                <p class="m-0 text-sm font-medium text-blue-100">Votre commune</p>
                <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">La mairie de L’Aiguillon-sur-Vie</h1>
                <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                    Le centre administratif de notre commune, au service des habitants :
                    état civil, urbanisme, aide sociale, écoles et bien plus encore.
                </p>
            </div>

            <div class="grid items-start gap-6 text-left lg:grid-cols-[minmax(0,1fr)_340px]">

                <!-- COLONNE PRINCIPALE : les services -->
                <section aria-labelledby="titre-services" class="min-w-0 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                    <h2 id="titre-services" class="m-0 text-lg font-bold text-slate-900">Nos services</h2>
                    <p class="m-0 mt-1 text-sm text-slate-500">Chaque service traite les demandes qui le concernent.</p>

                    <ul class="m-0 mt-4 grid list-none gap-3 p-0 sm:grid-cols-2">
                        <?php foreach ($services as $service): ?>
                            <li class="flex items-start gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-900/5 transition hover:bg-white hover:shadow-sm">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-lg text-[#003b8e] shadow-sm ring-1 ring-slate-900/5">
                                    <?= icone($service['icone']) ?>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($service['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="block text-sm text-slate-600"><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <!-- COLONNE DE DROITE : infos pratiques (reste visible en défilant sur grand écran) -->
                <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24" aria-label="Informations pratiques">

                    <!-- Horaires, avec l'état ouvert / fermé calculé à l'heure de Paris -->
                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                        <h2 class="m-0 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                            <?= icone('horloge') ?> Horaires
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
                    </div>

                    <!-- Contact -->
                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                        <h2 class="m-0 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-500">
                            <?= icone('conversation') ?> Nous contacter
                        </h2>
                        <div class="mt-3 grid gap-2">
                            <a href="tel:<?= str_replace(' ', '', $telephone) ?>"
                               class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-[#003b8e] no-underline ring-1 ring-slate-900/5 transition hover:bg-[#e6eef9]">
                                <?= icone('telephone') ?> <?= htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                               class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold break-all text-[#003b8e] no-underline ring-1 ring-slate-900/5 transition hover:bg-[#e6eef9]">
                                <?= icone('courrier') ?> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <a href="Contacts.php"
                           class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#003b8e] to-indigo-600 px-5 py-3 text-sm font-semibold text-white no-underline shadow-md transition hover:brightness-110">
                            <?= icone('envoyer') ?> Écrire à la mairie
                        </a>
                    </div>
                </aside>
            </div>
        </main>

    </div>

    <?= piedDePage() ?>

</body>
</html>
