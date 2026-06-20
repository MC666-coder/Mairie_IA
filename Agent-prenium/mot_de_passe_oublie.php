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
            <h1>Mot de passe oublié</h1>
            <p>Recevez un lien sécurisé pour réinitialiser votre mot de passe.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Réinitialisation</h2>
            <?php if (!empty($_SESSION['error_forgot'])): ?>
                <div style="
                    background:#ffdddd;
                    color:#a30000;
                    padding:12px;
                    border:1px solid #a30000;
                    border-radius:6px;
                    margin-bottom:15px;
                    font-weight:bold;
                ">
                    <?= $_SESSION['error_forgot']; ?>
                </div>
                <?php unset($_SESSION['error_forgot']); ?>
            <?php endif; ?>


            <div class="form-card">
                <form action="traitement_mot_de_passe_oublie.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_forgot"
                           value="<?= htmlspecialchars($_SESSION['csrf_forgot'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="email">Adresse email professionnelle</label>
                    <input type="email" id="email" name="email" required>

                    <button type="submit" class="btn-submit">Envoyer le lien</button>

                    <p style="margin-top:15px; text-align:center;">
                        <a href="connexion_agent.php">Retour à la connexion</a>
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
