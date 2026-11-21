<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';


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
if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = 0;
}
if ($_SESSION['forgot_attempts'] > 10) {
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_forgot'])) {
    $_SESSION['csrf_forgot'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Portail Agent</title>
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
            <h1>Mot de passe oublié</h1>
            <p>Recevez un lien sécurisé pour réinitialiser votre mot de passe.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth">
                <?= retourAuth('connexion_agent.php', 'Retour à la connexion') ?>
                <?= enteteAuth('cadenas', 'Mot de passe oublié ?', 'Recevez un lien sécurisé pour en choisir un nouveau') ?>
                <?= messageAuth('error_forgot') ?>

                <form action="traitement_mot_de_passe_oublie.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_forgot"
                           value="<?= htmlspecialchars($_SESSION['csrf_forgot'], ENT_QUOTES, 'UTF-8') ?>">

                    <?= champAuth('email', 'Adresse e-mail professionnelle', 'email', 'courrier') ?>

                    <button type="submit" class="btn-submit">Envoyer le lien</button>
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
