<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';

require_once __DIR__ . '/../Backend/Config/db.php';

/* ---------------------------------------------------------
   1) Sécurité session
--------------------------------------------------------- */
if (!isset($_SESSION['agent_id'])) {
    header("Location: connexion_agent.php");
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

$agent_id = $_SESSION['agent_id'];

/* ---------------------------------------------------------
   3) Récupération des filtres & recherche
--------------------------------------------------------- */
$q              = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtre_statut  = isset($_GET['statut']) ? trim($_GET['statut']) : '';
$filtre_priorite= isset($_GET['priorite']) ? trim($_GET['priorite']) : '';
$filtre_service = isset($_GET['service_id']) ? trim($_GET['service_id']) : '';
$filtre_agent   = isset($_GET['agent_assigne']) ? trim($_GET['agent_assigne']) : '';

$page       = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$parPage    = 20;
$offset     = ($page - 1) * $parPage;

/* ---------------------------------------------------------
   4) Récupération des services & agents pour filtres
--------------------------------------------------------- */
$servicesStmt = $pdo->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC");
$services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

$agentsStmt = $pdo->query("SELECT id, prenom, nom FROM agents ORDER BY nom ASC, prenom ASC");
$agents = $agentsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   5) Construction dynamique de la requête
--------------------------------------------------------- */
$where = [];
$params = [];

// Recherche globale
if ($q !== '') {
    $where[] = "(d.id LIKE ? 
                 OR d.type LIKE ? 
                 OR d.message LIKE ? 
                 OR c.nom_citoyen LIKE ? 
                 OR c.prenom_citoyen LIKE ? 
                 OR c.email_citoyen LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

// Filtres
if ($filtre_statut !== '') {
    $where[] = "d.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_priorite !== '') {
    $where[] = "d.priorite = ?";
    $params[] = $filtre_priorite;
}

if ($filtre_service !== '') {
    $where[] = "d.service_id = ?";
    $params[] = $filtre_service;
}

if ($filtre_agent !== '') {
    $where[] = "d.agent_id = ?";
    $params[] = $filtre_agent;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/* ---------------------------------------------------------
   6) Requête de comptage pour pagination
--------------------------------------------------------- */
$countSql = "
    SELECT COUNT(*) AS total
    FROM demandes d
    LEFT JOIN citoyens c ON d.citoyen_id = c.id
    $whereSql
";
try{
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagesTotal = max(1, ceil($total / $parPage));
} catch (PDOException $e) {
    $total = 0;
    $pagesTotal = 1;
}

/* ---------------------------------------------------------
   7) Requête principale
--------------------------------------------------------- */
$listSql = "
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
    d.service_id,
    d.citoyen_id,
    d.agent_id,
    d.created_at,

    s.nom_service,

    c.prenom_citoyen,
    c.nom_citoyen,

    a.prenom AS prenom_agent,
    a.nom AS nom_agent

    FROM demandes d
    LEFT JOIN types_demandes td ON d.type_id = td.id
    LEFT JOIN services s ON d.service_id = s.id
    LEFT JOIN citoyens c ON d.citoyen_id = c.id
    LEFT JOIN agents a ON d.agent_id = a.id

    $whereSql

    ORDER BY d.created_at DESC
    LIMIT $parPage OFFSET $offset;

";
try{
    $listStmt = $pdo->prepare($listSql);
    $listStmt->execute($params);
    $demandes = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $demandes = [];
    $total = 0;
    $pagesTotal = 1;
}

/* ---------------------------------------------------------
   8) Fonctions utilitaires d'affichage
--------------------------------------------------------- */
function badgeStatut($statut) {
    $class = 'badge-default';
    switch ($statut) {
        case 'nouvelle':    $class = 'badge-nouvelle'; break;
        case 'analyse_ia':  $class = 'badge-analyse'; break;
        case 'en_cours':    $class = 'badge-encours'; break;
        case 'resolue':     $class = 'badge-resolue'; break;
        case 'cloturee':    $class = 'badge-cloturee'; break;
    }
    return '<span class="badge ' . $class . '">' . htmlspecialchars($statut) . '</span>';
}

function badgePriorite($priorite) {
    $class = 'badge-prio-default';
    switch ($priorite) {
        case 'basse':   $class = 'badge-prio-basse'; break;
        case 'normale': $class = 'badge-prio-normale'; break;
        case 'haute':   $class = 'badge-prio-haute'; break;
        case 'urgente': $class = 'badge-prio-urgente'; break;
    }
    return '<span class="badge ' . $class . '">' . htmlspecialchars($priorite) . '</span>';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des demandes</title>
    <link rel="stylesheet" href="../Frontend/assets/css/gestion_demandes.css">
</head>
<body>

<div class="page-wrapper">
    <!-- HEADER PREMIUM -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">

            <nav id="nav-agent">
                <a href="dashboard_agent.php" >Tableau de Bord</a>
                <a href="gestion_demandes.php" class="active">Gestion des demandes</a>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#eef0f2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Déconnexion
                </a>
            </nav>

            <div id="bulle-connexion-agent">
                Identifiant Agent : <?= htmlspecialchars($agent['identifiant_agent']) ?><br>
                Prénom : <?= htmlspecialchars($agent['prenom_agent']) ?><br>
                Nom : <?= htmlspecialchars($agent['nom_agent']) ?><br>
                Rôle : <?= htmlspecialchars($agent['role']) ?><br>
                Service : <?= htmlspecialchars($agent['Nom_service']) ?><br>
                Niveau d'accès : <?= htmlspecialchars($agent['niveau_acces']) ?>
            </div>

        </div>

    <header class="header-gestion">
        <h1>Gestion des demandes citoyennes</h1>
        <p>Vue complète des demandes avec recherche, filtres et accès à la fiche détaillée.</p>
    </header>

    <!-- Barre de recherche & filtres -->
    <section class="filtres-section">
        <form method="GET" class="filtres-form">

            <div class="filtres-row">
                <div class="champ">
                    <label for="q">Recherche globale</label>
                    <input type="text" id="q" name="q" placeholder="ID, citoyen, email, type, message..."
                           value="<?= htmlspecialchars($q) ?>">
                </div>

                <div class="champ">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut">
                        <option value="">Tous</option>
                        <option value="nouvelle"   <?= $filtre_statut === 'nouvelle' ? 'selected' : '' ?>>Nouvelle</option>
                        <option value="analyse_ia" <?= $filtre_statut === 'analyse_ia' ? 'selected' : '' ?>>Analyse IA</option>
                        <option value="en_cours"   <?= $filtre_statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="resolue"    <?= $filtre_statut === 'resolue' ? 'selected' : '' ?>>Résolue</option>
                        <option value="cloturee"   <?= $filtre_statut === 'cloturee' ? 'selected' : '' ?>>Clôturée</option>
                    </select>
                </div>

                <div class="champ">
                    <label for="priorite">Priorité</label>
                    <select name="priorite" id="priorite">
                        <option value="">Toutes</option>
                        <option value="basse"   <?= $filtre_priorite === 'basse' ? 'selected' : '' ?>>Basse</option>
                        <option value="normale" <?= $filtre_priorite === 'normale' ? 'selected' : '' ?>>Normale</option>
                        <option value="haute"   <?= $filtre_priorite === 'haute' ? 'selected' : '' ?>>Haute</option>
                        <option value="urgente" <?= $filtre_priorite === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
            </div>

            <div class="filtres-row">
                <div class="champ">
                    <label for="service_id">Service</label>
                    <select name="service_id" id="service_id">
                        <option value="">Tous</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $filtre_service == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nom_service']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ">
                    <label for="agent_assigne">Agent assigné</label>
                    <select name="agent_assigne" id="agent_assigne">
                        <option value="">Tous</option>
                        <?php foreach ($agents as $ag): ?>
                            <option value="<?= $ag['id'] ?>" <?= $filtre_agent == $ag['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ag['prenom'] . ' ' . $ag['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ actions-filtres">
                    <button type="submit" class="btn-primary">Appliquer</button>
                    <a href="gestion_demandes.php" class="btn-secondary">Réinitialiser</a>
                </div>
            </div>

        </form>
    </section>

    <!-- Résumé -->
    <section class="resume-section">
        <p>
            <strong><?= $total ?></strong> demande(s) trouvée(s)
            <?php if ($total > 0): ?>
                — page <?= $page ?> / <?= $pagesTotal ?>
            <?php endif; ?>
        </p>
    </section>

    <!-- Tableau principal (desktop) -->
    <section class="table-section">
        <?php if (empty($demandes)): ?>
            <p class="no-data">Aucune demande ne correspond à vos critères.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-demandes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Service</th>
                            <th>Citoyen</th>
                            <th>Assigné à</th>
                            <th>Soumise le</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($demandes as $d): ?>
                        <tr>
                            <td>DEM-<?= htmlspecialchars($d['id']) ?></td>
                            <td><?= htmlspecialchars($d['type_libelle']?? 'non reseigné') ?></td>
                            <td class="col-message">
                                <?= htmlspecialchars(mb_strimwidth($d['message'], 0, 80, '…')) ?>
                            </td>
                            <td><?= badgePriorite($d['priorite']?? 'non renseigné') ?></td>
                            <td><?= badgeStatut($d['statut']) ?></td>
                            <td><?= htmlspecialchars($d['nom_service']) ?></td>
                            <td>
                                <?= htmlspecialchars(trim($d['prenom_citoyen'] . ' ' . $d['nom_citoyen'])) ?>
                            </td>
                            <td>
                                <?php if ($d['agent_id']): ?>
                                    <?= htmlspecialchars(trim($d['prenom_agent'] . ' ' . $d['nom_agent'])) ?>
                                <?php else: ?>
                                    <em>Non assignée</em>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($d['created_at']) ?></td>
                            <td>
                                <a href="gestion_demande.php?id=<?= $d['id'] ?>" class="btn-gestion">
                                    Gérer →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Version cartes (mobile / responsive) -->
    <section class="cards-section">
        <?php foreach ($demandes as $d): ?>
            <article class="demande-card">
                <header class="demande-card-header">
                    <span class="demande-id">DEM-<?= htmlspecialchars($d['id']) ?></span>
                    <span class="demande-statut"><?= badgeStatut($d['statut']) ?></span>
                </header>

                <p><strong>Type :</strong> <?= htmlspecialchars($d['type_libelle']?? 'non renseigné') ?></p>
                <p><strong>Message :</strong><br>
                    <?= nl2br(htmlspecialchars(mb_strimwidth($d['message'], 0, 160, '…'))) ?>
                </p>
                <p><strong>Priorité :</strong> <?= badgePriorite($d['priorite']) ?></p>
                <p><strong>Service :</strong> <?= htmlspecialchars($d['nom_service']) ?></p>
                <p><strong>Citoyen :</strong>
                    <?= htmlspecialchars(trim($d['prenom_citoyen'] . ' ' . $d['nom_citoyen'])) ?>
                </p>
                <p><strong>Assigné à :</strong>
                    <?php if ($d['agent_id']): ?>
                        <?= htmlspecialchars(trim($d['prenom_agent'] . ' ' . $d['nom_agent'])) ?>
                    <?php else: ?>
                        <em>Non assignée</em>
                    <?php endif; ?>
                </p>
                <p><strong>Soumise le :</strong> <?= htmlspecialchars($d['created_at']) ?></p>

                <footer class="demande-card-footer">
                    <a href="gestion_demande.php?id=<?= $d['id'] ?>" class="btn-gestion">
                        Gérer la demande →
                    </a>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- Pagination -->
    <?php if ($pagesTotal > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">← Précédent</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pagesTotal; $p++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                   class="page-link <?= $p === $page ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $pagesTotal): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Suivant →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

</body>
</html>
