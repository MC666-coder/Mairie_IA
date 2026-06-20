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



// Vérification connexion citoyen
if (!isset($_SESSION['id']) || !isset($_SESSION['email_citoyen'])) {
    http_response_code(403);
    die("Vous devez être connecté pour accéder à cette page.");
}

$citoyenId = (int) $_SESSION['id'];

/* ---------------------------------------
   Récupération sécurisée du citoyen
---------------------------------------- */

$getCitoyen = $pdo->prepare("
    SELECT id, nom_citoyen, prenom_citoyen 
    FROM citoyens 
    WHERE id = ?
    LIMIT 1
");
$getCitoyen->execute([$citoyenId]);
$citoyen = $getCitoyen->fetch(PDO::FETCH_ASSOC);

if (!$citoyen) {
    http_response_code(404);
    die("Citoyen non trouvé.");
}

/* ---------------------------------------
   Récupération sécurisée des services
---------------------------------------- */

$services = $pdo->prepare("SELECT id, nom_service FROM services ORDER BY nom_service ASC");
$services->execute();

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
    <title>Créer une demande - Commune de L’Aiguillon-sur-Vie</title>
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
                <a href="portail_citoyen.php">Tableau de bord</a>
                <a href="nouvelle_demande.php" class="active">Créer une demande</a>

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
                Prénom : <?= htmlspecialchars($citoyen['prenom_citoyen']) ?><br>
                Nom : <?= htmlspecialchars($citoyen['nom_citoyen']) ?><br>
                ID citoyen : <?= htmlspecialchars((string)$citoyenId) ?>
            </div>

        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Créer une demande</h1>
            <p>Soumettez une requête aux services municipaux en quelques clics.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Nouvelle demande</h2>
            <p class="section-subtitle">Veuillez remplir les informations ci-dessous.</p>

            <div class="form-card">
                <form action="traitement_nouvelle_demande.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="services_id">Service concerné</label>
                    <select id="services_id" name="services_id" required>
                        <option value="">-- Sélectionnez un service --</option>
                        <?php while ($s = $services->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= htmlspecialchars((string)$s['id']) ?>">
                                <?= htmlspecialchars($s['nom_service']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="type">Type de demande</label>
                    <select id="type" name="type" required>
                        <option value="plainte">Plainte</option>
                        <option value="question">Question</option>
                        <option value="suggestion">Suggestion</option>
                    </select>

                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>

                    <label for="priorite">Priorité</label>
                    <select id="priorite" name="priorite" required>
                        <option value="basse">Basse</option>
                        <option value="moyenne">Moyenne</option>
                        <option value="haute">Haute</option>
                    </select>

                    <button type="submit" class="btn-submit">Envoyer la demande</button>
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
