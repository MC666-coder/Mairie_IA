<?php
/* ---------------------------------------
   Sécurisation session
---------------------------------------- */

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';



// Vérification stricte du rôle agent
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'agent') {
    http_response_code(403);
    die("Accès refusé. Vous devez être connecté en tant qu'agent.");
}

$agent_id = (int) $_SESSION['id'];

/* ---------------------------------------
   Récupération sécurisée de l’agent
---------------------------------------- */

$getAgent = $pdo->prepare("
    SELECT id, nom_agent, prenom_agent, role 
    FROM agents 
    WHERE id = ?
    LIMIT 1
");
$getAgent->execute([$agent_id]);
$agent = $getAgent->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    http_response_code(404);
    die("Agent introuvable.");
}

/* ---------------------------------------
   Récupération des statistiques
---------------------------------------- */

$statsRequests = $pdo->query("
    SELECT 
        SUM(statut='nouvelle') AS nouvelle_demandes,
        SUM(statut='analyse_ia') AS analyse_ia,
        SUM(statut='en_cours') AS en_cours,
        SUM(statut='resolue') AS resolue
    FROM requette
");
$stats = $statsRequests->fetch(PDO::FETCH_ASSOC);

/* Valeurs par défaut si null */
$stats = array_map(fn($v) => $v ?? 0, $stats);

/* ---------------------------------------
   Récupération des dernières demandes
---------------------------------------- */

$lastRequests = $pdo->query("
    SELECT 
        requette.id, requette.type, requette.message, requette.priorite, 
        requette.statut, requette.created_at,
        services.nom_service, services.description_service,
        citoyens.nom_citoyen, citoyens.prenom_citoyen
    FROM requette
    LEFT JOIN citoyens ON citoyens.id = requette.citoyens_id
    LEFT JOIN services ON services.id = requette.services_id
    ORDER BY requette.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agent - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css">

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


    <?php if (!empty($_SESSION['inscription_success'])): ?>
        <div class="popup-success" id="popupSuccess">
            <div class="popup-box">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 12l2 2l4-4"></path>
                </svg>

                <h2>Compte créé avec succès</h2>
                <p>Votre compte vient d’être créé. Bienvenue dans l’espace agent.</p>

                <button id="popupOk">OK</button>
            </div>
        </div>

        <!-- SCRIPT QUI FONCTIONNE À TOUS LES COUPS -->
        <script src="assets/js/inscription_popup.js"></script>

    <?php unset($_SESSION['inscription_success']); ?>
    <?php endif; ?>


<div class="page-wrapper">

    <!-- HEADER PREMIUM -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">

            <nav id="nav-agent">
                <a href="portail_agent.php" class="active">Tableau de Bord</a>
                <a href="gestion_demandes.php">Gestion des demandes</a>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Déconnexion
                </a>
            </nav>

            <div id="bulle-connexion-agent">
                ID : <?= htmlspecialchars((string)$agent_id) ?><br>
                Prénom : <?= htmlspecialchars($agent['prenom_agent']) ?><br>
                Nom : <?= htmlspecialchars($agent['nom_agent']) ?><br>
                Rôle : <?= htmlspecialchars($agent['role']) ?>
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
                <p><?= htmlspecialchars((string)$stats['nouvelle_demandes']) ?></p>
            </div>

            <div class="stat-card">
                <h3>Analysées par l'IA</h3>
                <p><?= htmlspecialchars((string)$stats['analyse_ia']) ?></p>
            </div>

            <div class="stat-card">
                <h3>En cours</h3>
                <p><?= htmlspecialchars((string)$stats['en_cours']) ?></p>
            </div>

            <div class="stat-card">
                <h3>Résolues</h3>
                <p><?= htmlspecialchars((string)$stats['resolue']) ?></p>
            </div>
        </section>

        <!-- DERNIÈRES DEMANDES -->
        <section class="last-requette">
            <h2 class="section-title">Dernières demandes citoyennes</h2>

            <div class="demandes-container">
                <?php if ($lastRequests->rowCount() === 0): ?>
                    <p class="no-data">Aucune demande pour le moment.</p>
                <?php else: ?>
                    <?php while ($request = $lastRequests->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="demande-card">
                            <p><strong>ID :</strong> <?= htmlspecialchars((string)$request['id']?? 'non renseigné') ?></p>
                            <p><strong>Type :</strong> <?= htmlspecialchars($request['type']?? 'non renseigné') ?></p>
                            <p><strong>Message :</strong> <?= nl2br(htmlspecialchars($request['message'])?? 'non renseigné') ?></p>
                            <p><strong>Priorité :</strong> <?= htmlspecialchars($request['priorite']?? 'non renseigné') ?></p>

                            <p>
                                <strong>Statut :</strong>
                                <span class="statut statut-<?= htmlspecialchars($request['statut']?? 'non renseigné') ?>">
                                    <?= htmlspecialchars($request['statut']?? 'non renseigné') ?>
                                </span>
                            </p>

                            <p><strong>Service :</strong> <?= htmlspecialchars($request['nom_service']?? 'non renseigné') ?></p>
                            <p><strong>Description :</strong> <?= htmlspecialchars($request['description_service']?? 'non renseigné') ?></p>
                            <p><strong>Citoyen :</strong> <?= htmlspecialchars($request['prenom_citoyen'] . ' ' . $request['nom_citoyen']?? 'non renseigné') ?></p>
                            <p><strong>Soumis le :</strong> <?= htmlspecialchars($request['created_at']?? 'non renseigné') ?></p>
                        </div>
                    <?php endwhile; ?>
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

<footer id="footer-premium">
    <div class="footer-content">
        <div class="footer-col">
            <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
            <p class="footer-slogan">Vos idées, Notre action,<br>Partageons la réussite de L'Aiguillon-sur-Vie.</p>
        </div>

        <div class="footer-col">
            <h4>Informations légales</h4>
            <ul class="footer-links">
                <li><a href="mentions-legales.php">Mentions légales</a></li>
                <li><a href="politique-confidentialite.php">Politique de confidentialité</a></li>
                <li><a href="accessibilite.php">Accessibilité</a></li>
                <li><a href="rgpd.php">Données personnelles (RGPD)</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Coordonnées</h4>
            <p>Mairie de L’Aiguillon-sur-Vie</p>
            <p>Adresse : [Ton adresse ici]</p>
            <p>Téléphone : [Ton numéro]</p>
            <p>Email : [Ton email]</p>

            <h4 style="margin-top:15px;">Horaires</h4>
            <p>Lun–Ven : 9h00–12h00 / 14h00–17h00</p>
            <p>Samedi : 9h00–12h00</p>
        </div>
    </div>

    <div class="footer-bottom">
         © 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>
</footer>

</body>
</html>
