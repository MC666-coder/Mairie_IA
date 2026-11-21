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
   Nettoyage des tentatives de création
--------------------------------------------------------- */
unset($_SESSION['creation_attempts']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent créé - Portail Agent</title>
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
            <h1>Agent créé avec succès</h1>
            <p>Le nouvel agent a reçu un email pour activer son compte.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Création réussie</h2>

            <div class="form-card" style="text-align:center; padding:30px;">
                <p style="font-size:18px; margin-bottom:20px;">
                    Le compte de l’agent a été créé.<br>
                    Il doit maintenant consulter son email pour activer son compte<br>
                    et définir son mot de passe.
                </p>

                <a href="dashboard_agent.php" class="btn-submit" style="display:inline-block;">
                    Retour au tableau de bord
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
