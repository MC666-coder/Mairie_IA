<?php
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

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
    <?= feuillesGabarit() ?>

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

    <?= enTetePublic('actualites') ?>

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

    <?= piedDePage() ?>

</div>
</body>
</html>
