<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
}
session_start();
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

// Sécurisation renforcée des cookies de session

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>"/>
    <?= feuillesGabarit() ?>

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

    <?= enTetePublic('inscription') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Inscription</h1>
            <p>Choisissez votre type de compte pour créer votre espace sécurisé.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth carte-auth-large">
                <?= retourAuth('index.php', 'Accueil') ?>
                <?= enteteAuth('inscription', 'Choisissez votre profil', 'Sélectionnez le type de compte que vous souhaitez créer') ?>

                <nav class="choix-profils" aria-label="Choix du type de compte">
                    <?= choixProfil('inscriptions_citoyen.php', 'utilisateur', 'Créer un compte citoyen',
                                    'Faites vos démarches en ligne, 24h/24', 'citoyen') ?>
                    <?= choixProfil('../Agent-prenium/connexion_agent.php', 'mallette', 'Espace agent',
                                    'Votre compte est créé par la mairie : connectez-vous directement', 'agent') ?>
                </nav>

                <p class="carte-auth-pied">Déjà inscrit ? <a href="connexion_citoyen.php">Se connecter</a></p>
            </div>
        </section>

    </main>


</div>

<?= piedDePage() ?>

</body>
</html>
