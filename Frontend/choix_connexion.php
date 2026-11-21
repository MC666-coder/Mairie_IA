<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    // Cookies de session plus sûrs (si ton PHP est assez récent et que tu es en HTTPS, active secure => true)
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
}
session_start();
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

// Renforcer la sécurité de la session (à mettre idéalement dans un fichier commun)

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>"/>
    <?= feuillesGabarit() ?>

    <!-- Content Security Policy adaptée à une page sans JS externe -->
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

    <?= enTetePublic('connexion') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Connexion</h1>
            <p>Choisissez votre espace d’accès sécurisé.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth carte-auth-large">
                <?= retourAuth('index.php', 'Accueil') ?>
                <?= enteteAuth('connexion', 'Choisissez votre profil', 'Accédez à votre espace personnel en fonction de votre rôle') ?>

                <nav class="choix-profils" aria-label="Choix du profil de connexion">
                    <?= choixProfil('connexion_citoyen.php', 'utilisateur', 'Je suis un citoyen',
                                    'Suivez vos démarches et échangez avec la mairie', 'citoyen') ?>
                    <?= choixProfil('../Agent-prenium/connexion_agent.php', 'mallette', 'Je suis agent de la mairie',
                                    'Traitez les demandes des citoyens', 'agent') ?>
                </nav>

                <p class="carte-auth-pied">Pas encore de compte ? <a href="inscriptions_citoyen.php">Créer un compte citoyen</a></p>
            </div>
        </section>

    </main>


</div>

<?= piedDePage() ?>

</body>
</html>
