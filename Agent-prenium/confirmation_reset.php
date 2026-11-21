<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';

/* ---------------------------------------------------------
   Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   Nettoyage des tokens de session liés au reset
--------------------------------------------------------- */
unset($_SESSION['csrf_reset']);
unset($_SESSION['reset_attempts']);
unset($_SESSION['reset_submit_attempts']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe réinitialisé - Portail Agent</title>
    <link rel="stylesheet" href="../Frontend/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../Frontend/assets/css/style.css') ?>"/>

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

    <!-- HERO -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Mot de passe réinitialisé</h1>
            <p>Vous pouvez maintenant vous connecter à votre espace agent.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Réinitialisation réussie</h2>

            <div class="form-card" style="text-align:center; padding:30px;">
                <p style="font-size:18px; margin-bottom:20px;">
                    Votre nouveau mot de passe a été enregistré avec succès.<br>
                    Vous pouvez maintenant vous connecter à votre espace agent.
                </p>

                <a href="connexion_agent.php" class="btn-submit" style="display:inline-block;">
                    Accéder à la connexion
                </a>
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

</body>
</html>
