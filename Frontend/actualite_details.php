<?php
require_once __DIR__ . '/../Backend/Config/db.php';

// Désactiver l'affichage brut des erreurs en production (à gérer dans la config globale idéalement)
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// Vérification stricte de l'ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: Actualités.php?error=notfound');
    exit;
}

$id = (int) $_GET['id'];

// Récupérer l’actualité publiée
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND statut = 'publie'");
$stmt->execute([$id]);
$actu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actu) {
    header('Location: Actualités.php?error=notfound');
    exit;
}

// Sécurisation du nom de fichier image
$image = $actu['image'] ?: 'default.jpg';

// On n'autorise que des noms simples (lettres, chiffres, ., _, -)
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $image)) {
    $image = 'default.jpg';
}

// On force le chemin dans le dossier prévu
$imagePath = 'uploads/' . $image;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($actu['titre'], ENT_QUOTES, 'UTF-8') ?> - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <!-- Content Security Policy (adaptable selon tes besoins) -->
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

    <!-- HEADER -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="agenda.php">Agenda</a>
                <a href="Actualités.php" class="active">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>
                <a href="Newsletter.php">Newsletter</a>

                <a href="choix_connexion.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Connexion
                </a>

                <a href="choix_inscription.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#0a7c2f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M6 20v-1a6 6 0 0 1 12 0v1"/>
                            <line x1="20" y1="12" x2="20" y2="18"/>
                            <line x1="23" y1="15" x2="17" y2="15"/>
                        </svg>
                    </span>
                    Inscription
                </a>
            </nav>
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><?= htmlspecialchars($actu['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Publié le <?= htmlspecialchars(date("d/m/Y", strtotime($actu['date_publication'])), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main class="article-container">

        <!-- Bouton retour -->
        <a href="Actualités.php" class="retour-btn">← Retour aux actualités</a>

        <!-- Image principale -->
        <div class="article-image"
             style="background-image:url('<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>');">
        </div>

        <!-- Contenu -->
        <div class="article-content">
            <?= nl2br(htmlspecialchars($actu['contenu'], ENT_QUOTES, 'UTF-8')) ?>
        </div>

        <!-- Bouton retour bas de page -->
        <a href="Actualités.php" class="retour-btn bottom">← Retour aux actualités</a>

    </main>

    <!-- FOOTER -->
    <footer id="footer-premium">

        <div class="footer-content">

            <!-- Colonne 1 : Identité + slogan -->
            <div class="footer-col">
                <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
                <p class="footer-slogan">
                    Vos idées, Notre action,<br>
                    Partageons la réussite de L'Aiguillon-sur-Vie.
                </p>
            </div>

            <!-- Colonne 2 : Mentions légales -->
            <div class="footer-col">
                <h4>Informations légales</h4>
                <ul class="footer-links">
                    <li><a href="mentions-legales.php">Mentions légales</a></li>
                    <li><a href="politique-confidentialite.php">Politique de confidentialité</a></li>
                    <li><a href="accessibilite.php">Accessibilité</a></li>
                    <li><a href="rgpd.php">Données personnelles (RGPD)</a></li>
                </ul>
            </div>

            <!-- Colonne 3 : Coordonnées -->
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

</div>
</body>
</html>
