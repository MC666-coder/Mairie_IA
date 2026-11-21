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

require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';

// Page à rouvrir après la connexion (ex. : session expirée sur le détail d'une demande)
$retour = retourCitoyenValide($_GET['retour'] ?? null);
if ($retour !== null) {
    $_SESSION['retour_apres_connexion'] = $retour;
}

// Adresse saisie lors de l'essai précédent (réaffichée dans le champ)
$emailSaisi = (string) ($_SESSION['login_email_citoyen'] ?? '');
unset($_SESSION['login_email_citoyen']);



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Citoyen - Commune de L’Aiguillon-sur-Vie</title>
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

    <?= enTetePublic('connexion') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Connexion Citoyen</h1>
            <p>Accédez à votre espace citoyen sécurisé.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth">
                <?= retourAuth('choix_connexion.php', 'Retour') ?>
                <?= enteteAuth('utilisateur', 'Bon retour', 'Connectez-vous à votre espace citoyen') ?>

                <?= messageSessionExpiree() ?>
                <?= messageAuth('login_success', 'success') ?>
                <?= messageAuth('login_error') ?>

                <form action="traitement_connexion_citoyen.php" method="POST" autocomplete="off">

                    <!-- Protection CSRF -->
                    <?php
                    if (empty($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <?= champAuth('email_citoyen', 'Adresse e-mail', 'email', 'courrier', '', $emailSaisi) ?>
                    <?= champAuth('password', 'Mot de passe', 'password', 'cadenas') ?>
                    <a class="lien-auth-discret" href="mot_de_passe_oublie_citoyen.php">Mot de passe oublié ?</a>

                    <button type="submit" class="btn-submit">Se connecter</button>
                </form>

                <p class="carte-auth-pied">Pas encore de compte ? <a href="inscriptions_citoyen.php">Créer un compte</a></p>
            </div>

        </section>

    </main>


</div>

<?= piedDePage() ?>

<script src="assets/js/mot_de_passe_visible.js?v=<?= @filemtime(__DIR__ . '/assets/js/mot_de_passe_visible.js') ?>"></script>
</body>
</html>
