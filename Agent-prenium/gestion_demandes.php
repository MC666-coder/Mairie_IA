<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_agent.php'; // barre latérale, pastilles, assistant IA, agent.css
require_once __DIR__ . '/../Backend/Config/db.php';

/* ---------------------------------------------------------
   1) Sécurité session
--------------------------------------------------------- */
if (!isset($_SESSION['agent_id'])) {
    header("Location: connexion_agent.php?session=expiree"); // session absente ou expirée : message sur la page de connexion
    exit;
}

$agent_id = (int) $_SESSION['agent_id'];

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
   3) Filtres & recherche (valeurs inconnues ignorées : liste blanche)
--------------------------------------------------------- */
$q               = trim((string) ($_GET['q'] ?? ''));
$filtre_statut   = (string) ($_GET['statut'] ?? '');
$filtre_priorite = (string) ($_GET['priorite'] ?? '');
$filtre_service  = (string) ($_GET['service_id'] ?? '');
$filtre_agent    = (string) ($_GET['agent_assigne'] ?? '');

if (!isset(STATUTS_AGENT[$filtre_statut]))     { $filtre_statut = ''; }
if (!isset(PRIORITES_AGENT[$filtre_priorite])) { $filtre_priorite = ''; }
if (!ctype_digit($filtre_service))             { $filtre_service = ''; }
// « aucun » = demandes que personne n'a encore prises en charge
if ($filtre_agent !== 'aucun' && !ctype_digit($filtre_agent)) { $filtre_agent = ''; }

$page    = max(1, (int) ($_GET['page'] ?? 1));
$parPage = 20;

/* ---------------------------------------------------------
   4) Services & agents pour les filtres
--------------------------------------------------------- */
$services = $pdo->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
$agents   = $pdo->query("SELECT id, prenom, nom FROM agents ORDER BY nom ASC, prenom ASC")->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   5) Conditions de la requête (sans le statut : il sert aux onglets)
--------------------------------------------------------- */
$where  = [];
$params = [];

if ($q !== '') {
    // « DEM-36 » ou « 36 » : recherche aussi par numéro
    $numero = preg_replace('/^DEM-?/i', '', $q);
    $where[] = "(d.id = ?
                 OR td.libelle LIKE ?
                 OR d.message LIKE ?
                 OR c.nom_citoyen LIKE ?
                 OR c.prenom_citoyen LIKE ?
                 OR c.email_citoyen LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, ctype_digit($numero) ? (int) $numero : 0, $like, $like, $like, $like, $like);
}
if ($filtre_priorite !== '') {
    $where[]  = "d.priorite = ?";
    $params[] = $filtre_priorite;
}
if ($filtre_service !== '') {
    $where[]  = "d.service_id = ?";
    $params[] = (int) $filtre_service;
}
if ($filtre_agent === 'aucun') {
    $where[] = "d.agent_id IS NULL";
} elseif ($filtre_agent !== '') {
    $where[]  = "d.agent_id = ?";
    $params[] = (int) $filtre_agent;
}

$jointures = "
    FROM demandes d
    LEFT JOIN types_demandes td ON d.type_id = td.id
    LEFT JOIN services s ON d.service_id = s.id
    LEFT JOIN citoyens c ON d.citoyen_id = c.id
    LEFT JOIN agents a ON d.agent_id = a.id
";

/* ---------------------------------------------------------
   6) Nombre de demandes par statut (onglets), avec les autres filtres
--------------------------------------------------------- */
$compteurs = array_fill_keys(array_keys(STATUTS_AGENT), 0);
$toutes = 0;
try {
    $stmt = $pdo->prepare("SELECT d.statut, COUNT(*) AS n $jointures"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " GROUP BY d.statut");
    $stmt->execute($params);
    foreach ($stmt as $ligne) {
        if (isset($compteurs[$ligne['statut']])) {
            $compteurs[$ligne['statut']] = (int) $ligne['n'];
        }
        $toutes += (int) $ligne['n'];
    }
} catch (PDOException $e) {
    error_log('gestion_demandes : comptage impossible');
}

/* ---------------------------------------------------------
   7) Liste de la page (statut compris)
--------------------------------------------------------- */
if ($filtre_statut !== '') {
    $where[]  = "d.statut = ?";
    $params[] = $filtre_statut;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = $filtre_statut !== '' ? $compteurs[$filtre_statut] : $toutes;
$pagesTotal = max(1, (int) ceil($total / $parPage));
$page       = min($page, $pagesTotal);
$offset     = ($page - 1) * $parPage;

$demandes = [];
try {
    $listStmt = $pdo->prepare("
        SELECT d.id, td.libelle AS type_libelle, d.message, d.priorite, d.statut, d.agent_id, d.created_at,
               s.nom_service, c.prenom_citoyen, c.nom_citoyen, a.prenom AS prenom_agent, a.nom AS nom_agent
        $jointures
        $whereSql
        ORDER BY d.created_at DESC, d.id DESC
        LIMIT $parPage OFFSET $offset
    ");
    $listStmt->execute($params);
    $demandes = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('gestion_demandes : lecture impossible');
}

/* ---------------------------------------------------------
   8) Adresses des onglets et des pages (en gardant les autres filtres)
--------------------------------------------------------- */
$filtresActifs = array_filter([
    'q' => $q, 'statut' => $filtre_statut, 'priorite' => $filtre_priorite,
    'service_id' => $filtre_service, 'agent_assigne' => $filtre_agent,
], fn ($v) => $v !== '');

function urlGestion(array $filtres, array $changements = []): string
{
    $tout = array_filter(array_merge($filtres, $changements), fn ($v) => $v !== '' && $v !== null);
    unset($tout['page']);
    if (isset($changements['page']) && $changements['page'] > 1) {
        $tout['page'] = $changements['page'];
    }
    return 'gestion_demandes.php' . ($tout ? '?' . http_build_query($tout) : '');
}

$autresFiltres = $q !== '' || $filtre_priorite !== '' || $filtre_service !== '' || $filtre_agent !== '';

/** Classes d'une liste déroulante de filtre (plus visible quand un filtre est choisi). */
function classeFiltre(bool $actif): string
{
    return 'cursor-pointer appearance-none rounded-xl border py-2.5 pr-9 pl-3 text-sm font-medium outline-none transition focus:ring-4 focus:ring-marque-500/15 '
        . ($actif ? 'border-marque-500 bg-marque-50 text-marque-800' : 'border-encre-200 bg-white text-encre-700 hover:border-encre-300');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des demandes - Espace agent</title>
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

<?= debutPageAgent('demandes', $agent) ?>

<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">

    <!-- EN-TÊTE -->
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-encre-900 sm:text-3xl">Gestion des demandes</h1>
        <p class="mt-1 text-sm text-encre-500">Recherchez, filtrez et ouvrez une demande pour la traiter.</p>
    </div>

    <!-- ONGLETS DE STATUT (un clic = filtre appliqué) -->
    <nav class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Filtrer par statut">
        <?php
        $onglets = ['' => ['Toutes', $toutes]];
        foreach (STATUTS_AGENT as $cle => [$libelle]) {
            $onglets[$cle] = [$libelle, $compteurs[$cle]];
        }
        foreach ($onglets as $cle => [$libelle, $nombre]):
            $actif = $filtre_statut === (string) $cle; ?>
            <a href="<?= eAgent(urlGestion($filtresActifs, ['statut' => $cle])) ?>"
               <?= $actif ? 'aria-current="page"' : '' ?>
               class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition <?= $actif
                   ? 'bg-marque-600 text-white shadow-sm shadow-marque-900/20'
                   : 'bg-white text-encre-600 ring-1 ring-encre-900/5 hover:bg-encre-50 hover:text-encre-900' ?>">
                <?= eAgent($libelle) ?>
                <span class="rounded-full px-2 py-0.5 text-xs font-bold <?= $actif ? 'bg-white/20' : 'bg-encre-100 text-encre-500' ?>"><?= (int) $nombre ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- RECHERCHE ET FILTRES (appliqués dès qu'on change une liste : portail_agent.js) -->
    <form method="GET" action="gestion_demandes.php" data-filtres-auto
          class="mt-4 flex flex-col gap-2 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-encre-900/5 xl:flex-row xl:items-center">
        <?php if ($filtre_statut !== ''): ?>
            <input type="hidden" name="statut" value="<?= eAgent($filtre_statut) ?>">
        <?php endif; ?>

        <div class="relative min-w-0 flex-1 xl:min-w-64">
            <label for="q" class="sr-only">Rechercher une demande</label>
            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-encre-400"><?= icone('loupe') ?></span>
            <input type="search" id="q" name="q" value="<?= eAgent($q) ?>" maxlength="100"
                   placeholder="N° (DEM-36), citoyen, e-mail, type, message…"
                   class="w-full rounded-xl border border-encre-200 bg-encre-50 py-2.5 pr-3 pl-9 text-sm outline-none transition focus:border-marque-500 focus:bg-white focus:ring-4 focus:ring-marque-500/15">
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 xl:flex">
            <label class="relative">
                <span class="sr-only">Priorité</span>
                <select name="priorite" class="w-full <?= classeFiltre($filtre_priorite !== '') ?>">
                    <option value="">Toutes priorités</option>
                    <?php foreach (PRIORITES_AGENT as $cle => [$libelle]): ?>
                        <option value="<?= $cle ?>" <?= $filtre_priorite === $cle ? 'selected' : '' ?>>Priorité <?= mb_strtolower($libelle, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-encre-400"><?= icone('chevron-bas') ?></span>
            </label>

            <label class="relative">
                <span class="sr-only">Service</span>
                <select name="service_id" class="w-full <?= classeFiltre($filtre_service !== '') ?>">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= $filtre_service === (string) $s['id'] ? 'selected' : '' ?>><?= eAgent($s['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-encre-400"><?= icone('chevron-bas') ?></span>
            </label>

            <label class="relative">
                <span class="sr-only">Agent assigné</span>
                <select name="agent_assigne" class="w-full <?= classeFiltre($filtre_agent !== '') ?>">
                    <option value="">Tous les agents</option>
                    <option value="aucun" <?= $filtre_agent === 'aucun' ? 'selected' : '' ?>>Non assignées</option>
                    <option value="<?= $agent_id ?>" <?= $filtre_agent === (string) $agent_id ? 'selected' : '' ?>>Mes demandes</option>
                    <?php foreach ($agents as $ag): if ((int) $ag['id'] === $agent_id) continue; ?>
                        <option value="<?= (int) $ag['id'] ?>" <?= $filtre_agent === (string) $ag['id'] ? 'selected' : '' ?>><?= eAgent($ag['prenom'] . ' ' . $ag['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-encre-400"><?= icone('chevron-bas') ?></span>
            </label>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 cursor-pointer rounded-xl bg-encre-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-encre-800 xl:flex-none">Rechercher</button>
            <?php if ($autresFiltres || $filtre_statut !== ''): ?>
                <a href="gestion_demandes.php" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-encre-500 transition hover:bg-encre-100 hover:text-encre-800">
                    <?= icone('fermer') ?> Effacer
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- RÉSUMÉ -->
    <p class="mt-5 text-sm text-encre-500" role="status">
        <strong class="text-encre-900"><?= $total ?></strong> demande<?= $total > 1 ? 's' : '' ?>
        <?= $total > $parPage ? '· page ' . $page . ' sur ' . $pagesTotal : '' ?>
    </p>

    <!-- LISTE -->
    <section class="mt-3 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-encre-900/5" aria-label="Liste des demandes">
        <?php if (!$demandes): ?>
            <div class="px-5 py-14 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-encre-100 text-2xl text-encre-400"><?= icone('loupe') ?></span>
                <p class="mt-3 font-semibold text-encre-900">Aucune demande ne correspond</p>
                <p class="mt-1 text-sm text-encre-500">Modifiez la recherche ou les filtres.</p>
                <?php if ($autresFiltres || $filtre_statut !== ''): ?>
                    <a href="gestion_demandes.php" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-marque-600 px-4 py-2 text-sm font-semibold text-white hover:bg-marque-700">Voir toutes les demandes</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- En-têtes (grand écran) -->
            <div class="hidden grid-cols-[84px_minmax(0,1fr)_120px_150px_140px_20px] gap-4 border-b border-encre-100 bg-encre-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-encre-500 xl:grid">
                <span>N°</span><span>Demande</span><span>Statut</span><span>Assignée à</span><span>Reçue le</span><span></span>
            </div>
            <ul class="divide-y divide-encre-100">
                <?php foreach ($demandes as $d):
                    $citoyen = trim(($d['prenom_citoyen'] ?? '') . ' ' . ($d['nom_citoyen'] ?? '')) ?: 'Citoyen inconnu';
                    $assigne = $d['agent_id'] ? trim(($d['prenom_agent'] ?? '') . ' ' . ($d['nom_agent'] ?? '')) : ''; ?>
                    <li>
                        <a href="gestion_demande.php?id=<?= (int) $d['id'] ?>"
                           class="group grid gap-2 px-5 py-4 transition hover:bg-encre-50 xl:grid-cols-[84px_minmax(0,1fr)_120px_150px_140px_20px] xl:items-center xl:gap-4">
                            <div class="flex items-center justify-between gap-2 xl:block">
                                <span class="font-mono text-sm font-bold text-encre-900">DEM-<?= (int) $d['id'] ?></span>
                                <span class="xl:hidden"><?= pastilleStatut($d['statut']) ?></span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-encre-900">
                                    <?= eAgent($d['type_libelle'] ?? 'Type non renseigné') ?>
                                    <span class="font-normal text-encre-400">·</span>
                                    <span class="font-medium text-encre-600"><?= eAgent($d['nom_service'] ?? 'Service non attribué') ?></span>
                                </p>
                                <p class="mt-0.5 line-clamp-1 text-sm text-encre-500"><?= eAgent($d['message']) ?></p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-encre-500">
                                    <span class="inline-flex items-center gap-1"><?= icone('utilisateur') ?> <?= eAgent($citoyen) ?></span>
                                    <?= textePriorite($d['priorite']) ?>
                                </p>
                            </div>
                            <div class="hidden xl:block"><?= pastilleStatut($d['statut']) ?></div>
                            <div class="text-xs text-encre-600">
                                <?php if ($assigne !== ''): ?>
                                    <span class="inline-flex items-center gap-1.5"><span class="grid size-6 place-items-center rounded-full bg-marque-100 text-[10px] font-bold text-marque-800"><?= eAgent(initialesAgent(['prenom_agent' => $d['prenom_agent'], 'nom_agent' => $d['nom_agent']])) ?></span> <?= eAgent($assigne) ?></span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-encre-400"><?= icone('utilisateur') ?> Non assignée</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-encre-500"><?= icone('horloge') ?> <?= eAgent(dateAgent($d['created_at'])) ?></div>
                            <span class="hidden text-encre-300 transition group-hover:translate-x-0.5 group-hover:text-marque-600 xl:block"><?= icone('chevron-droite') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- PAGINATION -->
    <?php if ($pagesTotal > 1): ?>
        <nav class="mt-6 flex flex-wrap items-center justify-center gap-1.5" aria-label="Pages">
            <?php if ($page > 1): ?>
                <a href="<?= eAgent(urlGestion($filtresActifs, ['page' => $page - 1])) ?>" class="inline-flex items-center gap-1 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-encre-700 ring-1 ring-encre-900/5 hover:bg-encre-50">
                    <?= icone('fleche-gauche') ?> Précédente
                </a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $pagesTotal; $p++): ?>
                <?php if ($p === $page): ?>
                    <span aria-current="page" class="grid size-10 place-items-center rounded-xl bg-marque-600 text-sm font-bold text-white"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= eAgent(urlGestion($filtresActifs, ['page' => $p])) ?>" class="grid size-10 place-items-center rounded-xl bg-white text-sm font-semibold text-encre-700 ring-1 ring-encre-900/5 hover:bg-encre-50"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pagesTotal): ?>
                <a href="<?= eAgent(urlGestion($filtresActifs, ['page' => $page + 1])) ?>" class="inline-flex items-center gap-1 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-encre-700 ring-1 ring-encre-900/5 hover:bg-encre-50">
                    Suivante <?= icone('fleche-droite') ?>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>

<?= finPageAgent() ?>

</body>
</html>
