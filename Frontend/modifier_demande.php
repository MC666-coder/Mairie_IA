<?php
/* ---------------------------------------
   Sécurisation session + rôle agent
---------------------------------------- */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';





if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'agent') {
    http_response_code(403);
    die("Accès refusé.");
}

/* ---------------------------------------
   Vérification ID demande
---------------------------------------- */

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    die("ID de la demande invalide.");
}

$demande_Id = (int) $_GET['id'];

/* ---------------------------------------
   Récupération sécurisée de la demande
---------------------------------------- */

$getDemande = $pdo->prepare("
    SELECT 
        requette.*, 
        citoyens.nom_citoyen, 
        citoyens.prenom_citoyen, 
        services.nom_service 
    FROM requette 
    LEFT JOIN citoyens ON requette.citoyens_id = citoyens.id 
    LEFT JOIN services ON requette.services_id = services.id 
    WHERE requette.id = ?
    LIMIT 1
");
$getDemande->execute([$demande_Id]);
$demande = $getDemande->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    http_response_code(404);
    die("Demande non trouvée.");
}

/* ---------------------------------------
   Traitement du formulaire
---------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification CSRF
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        session_destroy();
        die("Requête invalide.");
    }

    // Validation des valeurs
    $statuts_valides = ['nouvelle', 'analyse_ia', 'en_cours', 'resolue'];
    $priorites_valides = ['basse', 'moyenne', 'haute'];

    $new_statut = $_POST['statut'] ?? '';
    $new_priorite = $_POST['priorite'] ?? '';

    if (!in_array($new_statut, $statuts_valides, true)) {
        die("Statut invalide.");
    }

    if (!in_array($new_priorite, $priorites_valides, true)) {
        die("Priorité invalide.");
    }

    // Mise à jour sécurisée
    $update = $pdo->prepare("
        UPDATE requette 
        SET statut = ?, priorite = ? 
        WHERE id = ?
    ");
    $update->execute([$new_statut, $new_priorite, $demande_Id]);

    header("Location: gestion_demandes.php?update=success");
    exit();
}

/* ---------------------------------------
   Génération du token CSRF
---------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une demande - Commune de L’Aiguillon-sur-Vie</title>
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

    <!-- HEADER PREMIUM -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">
            <nav id="nav-agent">
                <a href="portail_agent.php">Dashboard</a>
                <a href="gestion_demandes.php">Gestion des demandes</a>
                <a href="agent_citoyens.php">Citoyens</a>

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
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Modifier une demande</h1>
            <p>Gérez et mettez à jour les requêtes citoyennes.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Demande ID : <?= htmlspecialchars($demande['id']) ?></h2>

            <div class="form-card">

                <p><strong>Citoyen :</strong> 
                    <?= htmlspecialchars($demande['prenom_citoyen'] . ' ' . $demande['nom_citoyen']) ?>
                </p>

                <p><strong>Service :</strong> 
                    <?= htmlspecialchars($demande['nom_service'] ?? 'non renseigné') ?>
                </p>

                <p><strong>Type :</strong> 
                    <?= htmlspecialchars($demande['type'] ?? 'non renseigné') ?>
                </p>

                <p><strong>Message :</strong> 
                    <?= nl2br(htmlspecialchars($demande['message'] ?? 'non renseigné')) ?>
                </p>

                <form method="POST">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="statut">Nouveau statut</label>
                    <select name="statut" id="statut" required>
                        <option value="nouvelle" <?= $demande['statut'] === 'nouvelle' ? 'selected' : '' ?>>Nouvelle</option>
                        <option value="analyse_ia" <?= $demande['statut'] === 'analyse_ia' ? 'selected' : '' ?>>Analyse IA</option>
                        <option value="en_cours" <?= $demande['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="resolue" <?= $demande['statut'] === 'resolue' ? 'selected' : '' ?>>Résolue</option>
                    </select>

                    <label for="priorite">Nouvelle priorité</label>
                    <select name="priorite" id="priorite" required>
                        <option value="basse" <?= $demande['priorite'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                        <option value="moyenne" <?= $demande['priorite'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="haute" <?= $demande['priorite'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                    </select>

                    <button type="submit" class="btn-submit">Mettre à jour</button>
                    <a href="gestion_demandes.php" class="btn-retour">Retour</a>

                </form>
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
