<?php
require_once __DIR__ . '/../Backend/Config/db.php';

// Désactiver l'affichage brut des erreurs en production
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// Récupérer les actualités publiées
$stmt = $pdo->prepare("SELECT id, titre, resume, image FROM news WHERE statut = 'publie' ORDER BY date_publication DESC");
$stmt->execute();
$actualites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction de sécurisation des noms d’images
function secureImageName($name) {
    if (!$name) return 'default.jpg';
    return preg_match('/^[a-zA-Z0-9._-]+$/', $name) ? $name : 'default.jpg';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <!-- Content Security Policy -->
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
            <h1>Actualités</h1>
            <p>Suivez les informations et annonces de votre commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main class="actualites-container">

        <h2 class="section-title">Dernières actualités</h2>
        <p class="section-subtitle">Les informations les plus récentes publiées par la mairie.</p>

        <div class="actualites-grid">

            <?php if (empty($actualites)): ?>
                <p>Aucune actualité n’a encore été publiée.</p>

            <?php else: ?>
                <?php
                $compteur = 0;
                foreach ($actualites as $actu):
                    if ($compteur >= 6) break;
                    $compteur++;

                    $image = secureImageName($actu['image']);
                ?>
                    <div class="actu-card">

                        <div class="actu-image"
                             style="background-image:url('uploads/<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>');">
                        </div>

                        <div class="actu-content">

                            <h3 class="actu-title">
                                <?= htmlspecialchars($actu['titre'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <p class="actu-extrait">
                                <?= nl2br(htmlspecialchars($actu['resume'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>

                            <a href="actualite_details.php?id=<?= urlencode($actu['id']) ?>" class="actu-btn">
                                Lire la suite
                            </a>

                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </main>

    <!-- FOOTER -->
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

</div>
</body>
</html>
