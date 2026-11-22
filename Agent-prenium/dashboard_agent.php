<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_agent.php'; // barre latérale, assistant IA, feuille agent.css


require_once __DIR__ . '/../Backend/Config/db.php';



/* ---------------------------------------------------------
   0) Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: frame-ancestors 'none';");

/* ---------------------------------------------------------
   1) Vérification de la session agent
--------------------------------------------------------- */
if (!isset($_SESSION['agent_id'])) {
    header("Location: connexion_agent.php?session=expiree"); // session absente ou expirée : message sur la page de connexion
    exit;
}

$agent_id = $_SESSION['agent_id'];

/* ---------------------------------------------------------
   2) Récupération des infos agent
--------------------------------------------------------- */
$sql = $pdo->prepare("
    SELECT 
        a.nom AS nom_agent,
        a.prenom AS prenom_agent,
        a.role,
        a.niveau_acces,
        a.identifiant_agent,
        s.nom_service AS Nom_service
    FROM agents a
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.id = ?
");
$sql->execute([$agent_id]);
$agent = $sql->fetch();

if (!$agent) {
    session_destroy();
    header("Location: connexion_agent.php");
    exit;
}

/* ---------------------------------------------------------
   3) Récupération des statistiques
--------------------------------------------------------- */
$statQuery = $pdo->query("
    SELECT 
    COALESCE(SUM(CASE WHEN statut = 'nouvelle' THEN 1 ELSE 0 END), 0) AS nouvelle_demandes,
    COALESCE(SUM(CASE WHEN statut = 'analyse_ia' THEN 1 ELSE 0 END), 0) AS analyse_ia,
    COALESCE(SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END), 0) AS en_cours,
    COALESCE(SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END), 0) AS resolue
FROM demandes");
$stats = $statQuery->fetch(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   4) Récupération des dernières demandes (premium)
--------------------------------------------------------- */
$lastRequests = $pdo->prepare("
    SELECT 
    d.id,
    d.type_id,
    td.code AS type_code,
    td.libelle AS type_libelle,
    td.couleur AS type_couleur,
    td.icone AS type_icone,

    d.message,
    d.priorite,
    d.statut,
    d.created_at,

    s.id AS service_id,
    s.nom_service AS nom_service,
    s.description_service AS description_service,

    c.id AS citoyen_id,
    c.prenom_citoyen AS prenom_citoyen,
    c.nom_citoyen AS nom_citoyen

    FROM demandes d
    LEFT JOIN types_demandes td ON d.type_id = td.id
    LEFT JOIN services s ON d.service_id = s.id
    LEFT JOIN citoyens c ON d.citoyen_id = c.id

    ORDER BY d.created_at DESC
    LIMIT 10;

");
$lastRequests->execute();
$demandes = $lastRequests->fetchAll(PDO::FETCH_ASSOC);

/* Cartes de chiffres : un clic ouvre « Gestion des demandes » avec le filtre de statut déjà réglé */
$cartesStats = [
    ['Nouvelles demandes', $stats['nouvelle_demandes'], 'boite-reception', 'bg-sky-50 text-sky-600',       'nouvelle'],
    ['Analysées par l’IA', $stats['analyse_ia'],       'assistant',       'bg-violet-50 text-violet-600', 'analyse_ia'],
    ['En cours',           $stats['en_cours'],         'horloge',         'bg-amber-50 text-amber-600',   'en_cours'],
    ['Résolues',           $stats['resolue'],          'valide',          'bg-marque-50 text-marque-600', 'resolue'],
];
$nbNouvelles = (int) $stats['nouvelle_demandes'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Espace agent</title>
    <?= feuillesAgent() ?>

    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   connect-src 'self' https://generativelanguage.googleapis.com;">
</head>

<body>

<?= debutPageAgent('tableau', $agent) ?>

<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">

    <!-- EN-TÊTE -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-encre-500"><?= eAgent(dateDuJourAgent()) ?></p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-encre-900 sm:text-3xl">
                Bonjour, <?= eAgent($agent['prenom_agent']) ?>
            </h1>
            <p class="mt-1 text-sm text-encre-500">Voici l’activité des demandes citoyennes.</p>
        </div>
        <!-- Raccourci vers les demandes que personne n'a encore prises en charge -->
        <?php if ($nbNouvelles > 0): ?>
            <a href="gestion_demandes.php?statut=nouvelle"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-marque-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-marque-900/20 transition hover:bg-marque-700">
                <?= icone('boite-reception') ?> Traiter les nouvelles demandes
                <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold"><?= $nbNouvelles ?></span>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center gap-2 rounded-xl bg-encre-200 px-4 py-2.5 text-sm font-semibold text-encre-500">
                <?= icone('valide') ?> Aucune nouvelle demande
            </span>
        <?php endif; ?>
    </div>

    <!-- STATISTIQUES -->
    <section class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" aria-label="Statistiques des demandes">
        <?php foreach ($cartesStats as [$libelle, $nombre, $icone, $couleurs, $statut]): ?>
            <a href="gestion_demandes.php?statut=<?= $statut ?>"
               title="Voir les demandes « <?= eAgent($libelle) ?> »"
               class="group rounded-2xl bg-white p-4 shadow-sm ring-1 ring-encre-900/5 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-marque-500/30 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-encre-500"><?= eAgent($libelle) ?></p>
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl text-lg <?= $couleurs ?>"><?= icone($icone) ?></span>
                </div>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-encre-900"><?= (int) $nombre ?></p>
                <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-encre-400 transition group-hover:text-marque-700">
                    Voir <?= icone('fleche-droite', 'transition group-hover:translate-x-0.5') ?>
                </p>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- DERNIÈRES DEMANDES -->
    <section class="mt-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-encre-900/5" aria-labelledby="titre-dernieres">
        <div class="flex items-center justify-between gap-3 border-b border-encre-100 px-5 py-4">
            <h2 id="titre-dernieres" class="text-base font-bold text-encre-900">Dernières demandes citoyennes</h2>
            <a href="gestion_demandes.php" class="inline-flex items-center gap-1 text-sm font-semibold text-marque-700 hover:text-marque-800">
                Tout voir <?= icone('fleche-droite') ?>
            </a>
        </div>

        <?php if (empty($demandes)): ?>
            <div class="px-5 py-12 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-marque-50 text-2xl text-marque-600"><?= icone('boite-reception') ?></span>
                <p class="mt-3 font-semibold text-encre-900">Aucune demande pour le moment</p>
                <p class="mt-1 text-sm text-encre-500">Les nouvelles demandes des citoyens apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-encre-100">
                <?php foreach ($demandes as $d): ?>
                    <?php [$prioTexte, $prioCouleur] = PRIORITES_AGENT[$d['priorite']] ?? [ucfirst((string) $d['priorite']), 'text-encre-700']; ?>
                    <li>
                        <a href="gestion_demande.php?id=<?= (int) $d['id'] ?>"
                           class="group grid gap-2 px-5 py-4 transition hover:bg-encre-50 md:grid-cols-[76px_minmax(0,1fr)_160px_116px_16px] md:items-center md:gap-4">
                            <div class="flex items-center justify-between gap-2 md:block">
                                <span class="font-mono text-sm font-bold text-encre-900">DEM-<?= (int) $d['id'] ?></span>
                                <span class="md:hidden"><?= pastilleStatut($d['statut']) ?></span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-encre-900">
                                    <?= eAgent($d['type_libelle'] ?? 'Type non renseigné') ?>
                                    <span class="font-normal text-encre-400">·</span>
                                    <span class="font-medium text-encre-600"><?= eAgent($d['nom_service'] ?? 'Service à déterminer') ?></span>
                                </p>
                                <p class="mt-0.5 line-clamp-1 text-sm text-encre-500"><?= eAgent($d['message']) ?></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-encre-500 md:block md:space-y-0.5">
                                <p class="flex items-center gap-1.5"><?= icone('utilisateur') ?> <?= eAgent(trim(($d['prenom_citoyen'] ?? '') . ' ' . ($d['nom_citoyen'] ?? '')) ?: 'Citoyen inconnu') ?></p>
                                <p class="flex items-center gap-1.5"><?= icone('horloge') ?> <?= eAgent(dateAgent($d['created_at'])) ?></p>
                            </div>
                            <div class="hidden md:block">
                                <?= pastilleStatut($d['statut']) ?>
                                <p class="mt-1 text-xs <?= $prioCouleur ?>">Priorité <?= eAgent(mb_strtolower($prioTexte, 'UTF-8')) ?></p>
                            </div>
                            <p class="text-xs md:hidden <?= $prioCouleur ?>">Priorité <?= eAgent(mb_strtolower($prioTexte, 'UTF-8')) ?></p>
                            <span class="hidden text-encre-300 transition group-hover:translate-x-0.5 group-hover:text-marque-600 md:block"><?= icone('chevron-droite') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<?= finPageAgent() ?>

</body>
</html>
