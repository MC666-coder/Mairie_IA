<?php
/**
 * Sécurisation renforcée de la session
 */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';



// Vérification connexion + rôle agent
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'agent') {
    http_response_code(403);
    echo "Vous devez être connecté en tant qu'agent pour accéder à cette page.";
    exit();
}

$agent_id = (int) $_SESSION['id'];

/**
 * Récupération sécurisée de l’agent
 */
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
    echo "Agent non trouvé.";
    exit();
}

/**
 * Récupération sécurisée des demandes
 */
$allrequest = $pdo->prepare("
    SELECT 
        requette.id, 
        requette.type, 
        requette.message, 
        requette.priorite, 
        requette.statut, 
        requette.created_at,
        services.nom_service, 
        services.description_service,
        citoyens.nom_citoyen, 
        citoyens.prenom_citoyen
    FROM citoyens
    LEFT JOIN requette ON citoyens.id = requette.citoyens_id
    LEFT JOIN services ON requette.services_id = services.id
    ORDER BY requette.created_at DESC
    LIMIT 10
");
$allrequest->execute();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des demandes - Espace Agent</title>
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
<div class="page-wrapper">

    <!-- HEADER UNIFIÉ -->
    <header id="header-agent">

        <!-- Bandeau République Française -->
        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <!-- Navigation + bulle agent -->
        <div class="header-main">

            <nav id="nav-agent">
                <a href="portail_agent.php">Tableau de Bord</a>
                <a href="gestion_demandes.php" class="active">Gestion des demandes</a>

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

            <!-- Bulle de connexion agent -->
            <div id="bulle-connexion-agent">
                Prénom: <?= htmlspecialchars($agent['prenom_agent']) ?><br>
                Nom: <?= htmlspecialchars($agent['nom_agent']) ?><br>
                Rôle: <?= htmlspecialchars($agent['role']) ?>
            </div>

        </div>
    </header>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <h1>Gestion des demandes citoyennes</h1>

        <section class="demandes-list">
            <h2>Liste des demandes récentes</h2>

            <div class="demandes-container">
                <?php
                if ($allrequest->rowCount() === 0) {
                    echo "<p>Aucune demande trouvée.</p>";
                } else {
                    while ($request = $allrequest->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div class="demande-card">
                            <h3>Demande ID : <?= htmlspecialchars((string)($request['id'] ?? '')) ?></h3>

                            <p><strong>Type :</strong> <?= htmlspecialchars($request['type'] ?? 'non renseigné') ?></p>
                            <p><strong>Message :</strong> <?= nl2br(htmlspecialchars($request['message'] ?? 'non renseigné')) ?></p>
                            <p><strong>Priorité :</strong> <?= htmlspecialchars($request['priorite'] ?? 'non renseigné') ?></p>

                            <p>
                                <strong>Statut :</strong>
                                <span class="statut statut-<?= htmlspecialchars($request['statut'] ?? 'inconnu') ?>">
                                    <?= htmlspecialchars($request['statut'] ?? 'non renseigné') ?>
                                </span>
                            </p>

                            <p><strong>Date :</strong> <?= htmlspecialchars($request['created_at'] ?? 'non renseigné') ?></p>

                            <p><strong>Service :</strong>
                                <?= htmlspecialchars($request['nom_service'] ?? 'non renseigné') ?> –
                                <?= htmlspecialchars($request['description_service'] ?? 'non renseigné') ?>
                            </p>

                            <p><strong>Citoyen :</strong>
                                <?= htmlspecialchars($request['prenom_citoyen'] ?? 'non renseigné') ?>
                                <?= htmlspecialchars($request['nom_citoyen'] ?? '') ?>
                            </p>

                            <?php if (!empty($request['id'])): ?>
                                <div class="actions">
                                    <a href="modifier_demande.php?id=<?= urlencode((string)$request['id']) ?>" class="btn-accept">
                                        Modifier
                                    </a>

                                    <a href="supprimer_demande.php?id=<?= urlencode((string)$request['id']) ?>"
                                       class="btn-supprimer"
                                       onclick="return confirm('Voulez-vous vraiment supprimer cette requête ?');">
                                        Supprimer
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </section>

    </main>

    <!-- BANDE TRICOLORE -->
    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

<!-- FOOTER PREMIUM -->
<footer id="footer-premium">

    <div class="footer-content">

        <div class="footer-col">
            <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
            <p class="footer-slogan">
                Vos idées, Notre action,<br>
                Partageons la réussite de L'Aiguillon-sur-Vie.
            </p>
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
        &copy; 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>

</footer>

</body>
</html>
