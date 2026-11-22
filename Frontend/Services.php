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
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/synonymes_services.php'; // mots des citoyens (« mort » → État civil)

/* ---------------------------------------
   Services présentés (les mots-clés servent à la recherche en direct)
---------------------------------------- */
$services = [
    ['nom' => 'État civil',           'icone' => 'carte-identite',
     'description' => 'Actes de naissance, mariage, décès, livret de famille et documents officiels.',
     'mots' => ['naissance', 'mariage', 'décès', 'acte', 'livret de famille', 'pacs', 'reconnaissance']],
    ['nom' => 'Urbanisme',            'icone' => 'maison',
     'description' => 'Permis de construire, déclarations préalables, informations sur les travaux.',
     'mots' => ['permis de construire', 'déclaration préalable', 'travaux', 'chantier', 'clôture', 'piscine', 'cadastre']],
    ['nom' => 'Voirie et travaux',    'icone' => 'cone',
     'description' => 'Entretien des routes, éclairage public, signalisation et interventions techniques.',
     'mots' => ['route', 'trottoir', 'nid de poule', 'éclairage', 'lampadaire', 'panneau', 'stationnement']],
    ['nom' => 'Environnement',        'icone' => 'feuille',
     'description' => 'Déchets, espaces verts, nuisances sonores, propreté et qualité de vie.',
     'mots' => ['déchets', 'poubelle', 'tri', 'encombrants', 'bruit', 'espaces verts', 'dépôt sauvage']],
    ['nom' => 'CCAS / Social',        'icone' => 'coeur',
     'description' => 'Aides sociales, logement, soutien aux familles et aux personnes vulnérables.',
     'mots' => ['aide', 'logement', 'rsa', 'personnes âgées', 'handicap', 'colis', 'famille']],
    ['nom' => 'Scolaire et jeunesse', 'icone' => 'ecole',
     'description' => 'Inscriptions scolaires, cantine, garderie, activités périscolaires.',
     'mots' => ['école', 'inscription', 'cantine', 'garderie', 'périscolaire', 'centre de loisirs', 'enfant']],
    ['nom' => 'Communication',        'icone' => 'megaphone',
     'description' => 'Site internet, réseaux sociaux, affichage municipal et informations publiques.',
     'mots' => ['information', 'bulletin', 'réseaux sociaux', 'affichage', 'newsletter']],
    ['nom' => 'Culture et sport',     'icone' => 'musique',
     'description' => 'Équipements sportifs, activités culturelles, soutien aux associations.',
     'mots' => ['sport', 'salle', 'culture', 'association', 'bibliothèque', 'stade', 'subvention']],
    ['nom' => 'Finances',             'icone' => 'euro',
     'description' => 'Budget municipal, taxes locales, factures et paiements.',
     'mots' => ['facture', 'paiement', 'taxe', 'impôts locaux', 'budget', 'régie']],
    ['nom' => 'Ressources humaines',  'icone' => 'groupe',
     'description' => 'Recrutement, gestion du personnel, stages et candidatures.',
     'mots' => ['emploi', 'recrutement', 'candidature', 'cv', 'stage', 'job d’été']],
];

/* Identifiant de chaque service en base, pour pré-choisir le service dans « Nouvelle demande ». */
$idsServices = [];
try {
    foreach ($pdo->query("SELECT id, nom_service FROM services") as $ligne) {
        $idsServices[$ligne['nom_service']] = (int) $ligne['id'];
    }
} catch (PDOException $e) {
    error_log('Services : lecture de la table services impossible');
}

/** Lien « Faire une demande » d'un service (sans service pré-choisi s'il est inconnu en base). */
function lienDemande(string $nom, array $ids): string
{
    return isset($ids[$nom]) ? 'nouvelle_demande.php?service=' . $ids[$nom] : 'nouvelle_demande.php';
}

/** Texte cherché par le filtre : nom + description + mots-clés + synonymes (non affichés), en minuscules. */
function texteRecherche(array $service): string
{
    $synonymes = SYNONYMES_SERVICES[$service['nom']] ?? [];
    return mb_strtolower($service['nom'] . ' ' . $service['description'] . ' '
        . implode(' ', $service['mots']) . ' ' . implode(' ', $synonymes), 'UTF-8');
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
    <title>Services municipaux - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- Content Security Policy stricte -->
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

    <?= enTetePublic('services') ?>

    <main id="content_portail_citoyen" class="page-detail">

        <!-- En-tête (dégradé bleu nuit) avec la recherche -->
        <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
            <p class="m-0 text-sm font-medium text-blue-100">Votre commune</p>
            <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Services municipaux</h1>
            <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                Trouvez le bon service et faites votre demande en ligne.
            </p>

            <form class="relative mt-5 max-w-xl" role="search" action="#" id="form-recherche-services">
                <label for="recherche-services" class="sr-only">Rechercher un service</label>
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-lg text-slate-400"><?= icone('loupe') ?></span>
                <input type="search" id="recherche-services" autocomplete="off" maxlength="60"
                       placeholder="Que cherchez-vous ? Ex. : cantine, permis, lampadaire…"
                       class="w-full rounded-full border-0 bg-white py-3.5 pr-4 pl-12 text-sm text-slate-800 shadow-lg outline-none ring-4 ring-white/20 placeholder:text-slate-400 focus:ring-white/50 sm:text-base">
            </form>
            <p id="resultat-recherche" class="m-0 mt-3 min-h-5 text-sm text-blue-100" role="status" aria-live="polite"></p>
        </div>

        <!-- TUILES (filtrées par services.js) -->
        <ul id="liste-services" class="m-0 grid list-none gap-4 p-0 text-left sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($services as $service): ?>
                <li class="tuile-service min-w-0" data-recherche="<?= e(texteRecherche($service)) ?>">
                    <article class="flex h-full flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:shadow-md">
                        <span class="grid size-11 place-items-center rounded-2xl bg-[#e6eef9] text-xl text-[#003b8e]">
                            <?= icone($service['icone']) ?>
                        </span>
                        <h2 class="m-0 mt-3 text-base font-bold text-slate-900"><?= e($service['nom']) ?></h2>
                        <p class="m-0 mt-1 text-sm text-slate-600"><?= e($service['description']) ?></p>
                        <ul class="m-0 mt-3 flex list-none flex-wrap gap-1.5 p-0" aria-label="Mots-clés">
                            <?php foreach (array_slice($service['mots'], 0, 4) as $mot): ?>
                                <li class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600"><?= e($mot) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= e(lienDemande($service['nom'], $idsServices)) ?>"
                           class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-[#003b8e] no-underline hover:underline">
                            Faire une demande <?= icone('fleche-droite') ?>
                        </a>
                    </article>
                </li>
            <?php endforeach; ?>

            <!-- Toujours visible : l'assistant oriente vers le bon service -->
            <li class="min-w-0">
                <a href="accès_chatbot.php"
                   class="flex h-full flex-col items-center justify-center gap-2 rounded-3xl border-2 border-dashed border-[#003b8e]/25 bg-white/60 p-6 text-center no-underline transition hover:border-[#003b8e]/50 hover:bg-white">
                    <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-[#003b8e] to-indigo-600 text-xl text-white shadow-md">
                        <?= icone('assistant') ?>
                    </span>
                    <span class="text-base font-bold text-slate-900">Pas sûr du service ?</span>
                    <span class="text-sm text-slate-600">Décrivez votre besoin à l’assistant : il vous oriente vers le bon service.</span>
                    <span class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-[#003b8e]">
                        Demander à l’assistant <?= icone('fleche-droite') ?>
                    </span>
                </a>
            </li>
        </ul>

        <!-- Aucun résultat -->
        <div id="aucun-service" hidden class="mt-4 rounded-3xl bg-white px-6 py-8 text-center shadow-sm ring-1 ring-slate-900/5">
            <p class="m-0 font-bold text-slate-900">Aucun service ne correspond à cette recherche.</p>
            <p class="m-0 mt-1 text-sm text-slate-500">Essayez un autre mot, ou décrivez votre besoin à l’assistant.</p>
        </div>
    </main>

</div>

<?= piedDePage() ?>

<script src="assets/js/services.js?v=<?= @filemtime(__DIR__ . '/assets/js/services.js') ?>"></script>
</body>
</html>
