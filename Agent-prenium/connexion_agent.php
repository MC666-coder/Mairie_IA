<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
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
// 10 essais, puis 15 minutes d'attente (compteur géré par traitement_connexion_agent.php)
if (!empty($_SESSION['login_debut']) && time() - $_SESSION['login_debut'] > 900) {
    $_SESSION['login_attempts'] = 0;
}
if (($_SESSION['login_attempts'] ?? 0) > 10 && empty($_SESSION['login_error_agent'])) {
    $_SESSION['login_error_agent'] = "Trop de tentatives. Réessayez dans quelques minutes.";
}

// Adresse saisie lors de l'essai précédent (réaffichée dans le champ)
$emailSaisi = (string) ($_SESSION['login_email_agent'] ?? '');
unset($_SESSION['login_email_agent']);


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Agent - Portail Agent</title>
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
            <h1>Connexion Agent</h1>
            <p>Accédez à votre espace professionnel sécurisé.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth">
                <?= retourAuth('../Frontend/choix_connexion.php', 'Retour') ?>
                <?= enteteAuth('mallette', 'Espace agent', 'Connectez-vous avec vos identifiants professionnels') ?>
                <?= messageAuth('login_error_agent') ?>
                <form action="traitement_connexion_agent.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_login"
                           value="<?= htmlspecialchars($_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8') ?>">

                    <?= champAuth('email', 'Adresse e-mail', 'email', 'courrier', '', $emailSaisi) ?>
                    <?= champAuth('password', 'Mot de passe', 'password', 'cadenas') ?>
                    <a class="lien-auth-discret" href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>

                    <button type="submit" class="btn-submit">Se connecter</button>
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

<script src="../Frontend/assets/js/mot_de_passe_visible.js?v=<?= @filemtime(__DIR__ . '/../Frontend/assets/js/mot_de_passe_visible.js') ?>"></script>
</body>
</html>
