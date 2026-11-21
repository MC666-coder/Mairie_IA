<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';


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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Agent</title>
    <link rel="stylesheet" href="../Frontend/assets/css/dashboard.css?v=<?= @filemtime(__DIR__ . '/../Frontend/assets/css/dashboard.css') ?>"/>

    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   connect-src 'self' https://generativelanguage.googleapis.com;

                   ">
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
                <a href="dashboard_agent.php" class="active">Tableau de Bord</a>
                <a href="gestion_demandes.php">Gestion des demandes</a>
                <?php if (in_array($_SESSION['niveau_acces'] ?? '', ['manager', 'admin'], true)): ?>
                <!-- Visible uniquement par les managers et admins (contrôle aussi fait côté serveur) -->
                <a href="inscription_agent.php">Créer un agent</a>
                <?php endif; ?>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#eef1f5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Espace Agent</h1>
            <p>Gérez les demandes citoyennes et suivez l’activité de la commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <h2 class="section-title">Bienvenue, <?= htmlspecialchars($agent['prenom_agent'] . ' ' . $agent['nom_agent']) ?></h2>

        <!-- STATISTIQUES -->
        <section class="stats-agent">
            <div class="stat-card">
                <h3>Nouvelles demandes</h3>
                <p><?= $stats['nouvelle_demandes'] ?></p>
            </div>

            <div class="stat-card">
                <h3>Analysées par l'IA</h3>
                <p><?= $stats['analyse_ia'] ?></p>
            </div>

            <div class="stat-card">
                <h3>En cours</h3>
                <p><?= $stats['en_cours'] ?></p>
            </div>

            <div class="stat-card">
                <h3>Résolues</h3>
                <p><?= $stats['resolue'] ?></p>
            </div>
        </section>

        <!-- DEMANDES PREMIUM -->
        <section class="last-requette">
            <h2 class="section-title">Dernières demandes citoyennes</h2>

            <div class="demandes-container">
                <?php if (empty($demandes)): ?>
                    <p class="no-data">Aucune demande pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($demandes as $request): ?>
                        <div class="demande-card">
                            <div class="demande-header">
                                <span class="demande-id">
                                    <svg class="demande-icon" fill="none" stroke="#003b8e" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 7h18M3 12h18M3 17h18"/>
                                    </svg>
                                    DEM-<?= htmlspecialchars($request['id']) ?>
                                </span>

                                <span class="statut statut-<?= htmlspecialchars($request['statut']) ?>">
                                    <?= htmlspecialchars($request['statut']) ?>
                                </span>
                            </div>

                            <p><strong>Type :</strong> <?= htmlspecialchars($request['type_libelle']?? 'non reseigné') ?></p>

                            <p><strong>Message :</strong><br>
                                <?= nl2br(htmlspecialchars($request['message'])) ?>
                            </p>

                            <p><strong>Priorité :</strong> <?= htmlspecialchars($request['priorite']) ?></p>

                            <p><strong>Service :</strong> <?= htmlspecialchars($request['nom_service']) ?></p>

                            <p><strong>Description :</strong><br>
                                <?= htmlspecialchars($request['description_service']) ?>
                            </p>

                            <p><strong>Citoyen :</strong>
                                <?= htmlspecialchars($request['prenom_citoyen'] . ' ' . $request['nom_citoyen']) ?>
                            </p>

                            <p><strong>Soumis le :</strong> <?= htmlspecialchars($request['created_at']) ?></p>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </section>

    </main>

    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>
    <!-- ============================================================
        ASSISTANT IA AGENT — BOUTON FLOTTANT + FENÊTRE PREMIUM
    ============================================================ -->

    <link rel="stylesheet" href="../Frontend/IA/assistant.css">
    <script src="../Frontend/assets/js/icones.js" defer></script>
    <script src="../Frontend/IA/assistant.js" defer></script>

    <!-- BOUTON FLOTTANT IA -->
    <div id="ia-agent-button">
        <div class="ia-glow"></div>
        <svg viewBox="0 0 24 24" class="ia-icon">
            <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/>
        </svg>
    </div>

    <!-- FENÊTRE DE CHAT IA -->
    <div id="ia-agent-window" class="hidden">

        <div class="ia-header">
            <span>Assistant IA — Espace Agent</span>
            <button id="ia-close-btn">&times;</button>
        </div>

        <div id="ia-messages"></div>

        <div id="ia-loader" class="hidden">
            <div class="loader-spinner"></div>
            <span class="loader-text">Analyse en cours…</span>
        </div>


        <div class="ia-input-zone">
            <input type="text" id="ia-input" placeholder="Décrivez la demande, sans nom ni coordonnées du citoyen…"
                   title="Les noms, téléphones, adresses précises et données de santé sont masqués automatiquement, mais évitez de les saisir. Rien n'est conservé.">
            <button id="ia-send-btn">Envoyer</button>
        </div>

    </div>

</body>
</html>
