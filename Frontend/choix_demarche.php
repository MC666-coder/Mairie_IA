<?php require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès aux démarches - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- Content Security Policy stricte pour une page statique -->
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

    <?= enTetePublic('') ?>

    <!-- HERO -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Accès aux démarches</h1>
            <p>Pour continuer, veuillez vous connecter ou créer un compte citoyen.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Identification requise</h2>
            <p class="section-subtitle">
                L’accès aux démarches en ligne nécessite une authentification sécurisée.
            </p>

            <div class="assistant-card">

                <div class="assistant-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none"
                         stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v.01"/>
                        <path d="M12 8v4"/>
                    </svg>
                </div>

                <p class="assistant-text">
                    Connectez-vous si vous avez déjà un compte, ou créez-en un pour accéder à l’ensemble des services municipaux.
                </p>

                <div class="assistant-cta">
                    <a href="choix_connexion.php" class="assistant-btn primary">Se connecter</a>
                    <a href="choix_inscription.php" class="assistant-btn secondary">Créer un compte</a>
                </div>

            </div>

        </section>

    </main>


</div>

<?= piedDePage() ?>

</body>
</html>
