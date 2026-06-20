<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
/* ---------------------------------------------------------
   Génération du token CSRF
--------------------------------------------------------- */
$_SESSION['csrf_login'] = bin2hex(random_bytes(32));



/* ---------------------------------------------------------
   Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if ($_SESSION['login_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Agent - Portail Agent</title>
    <link rel="stylesheet" href="assets/css/style.css"/>

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
            <h1>Connexion Agent</h1>
            <p>Accédez à votre espace professionnel sécurisé.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Se connecter</h2>

            <div class="form-card">
                <form action="traitement_connexion_agent.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_login"
                           value="<?= htmlspecialchars($_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" class="btn-submit">Connexion</button>

                    <p style="margin-top:15px; text-align:center;">
                        <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
                    </p>

                </form>
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
