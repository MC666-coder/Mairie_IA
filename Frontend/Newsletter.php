<?php
// Sécurisation cookies (cohérence globale portail)
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);

session_start();
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';


// Token CSRF pour le formulaire newsletter
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Municipale - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- CSP stricte -->
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

    <?= enTetePublic('newsletter') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Newsletter Municipale</h1>
            <p>Abonnez-vous pour recevoir les actualités et événements de votre commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth">
                <?= retourAuth('index.php', 'Accueil') ?>
                <?= enteteAuth('courrier', 'Restez informé', 'Recevez les actualités et les événements de L’Aiguillon-sur-Vie') ?>

                <?php
                // Message d'erreur renvoyé par traitement_newletter.php (?newsletter_error=...)
                $erreursNewsletter = [
                    'method'         => "Méthode non autorisée.",
                    'csrf'           => "La page a expiré. Rechargez-la puis réessayez.",
                    'too_fast'       => "Veuillez patienter avant une nouvelle inscription.",
                    'invalid_fields' => "Veuillez remplir tous les champs correctement.",
                    'email_exists'   => "Cette adresse e-mail est déjà inscrite à la newsletter.",
                ];
                if (isset($_GET['newsletter_error'])): ?>
                    <div class="error-message" role="alert">
                        <?= htmlspecialchars($erreursNewsletter[$_GET['newsletter_error']] ?? "Une erreur est survenue.", ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form id="newsletter_form" action="traitement_newletter.php" method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <?= champAuth('name', 'Nom complet', 'text', 'utilisateur', 'nom_complet') ?>
                    <?= champAuth('email', 'Adresse e-mail', 'email', 'courrier') ?>

                    <button type="submit" class="btn-submit">S’abonner</button>
                </form>

                <p class="carte-auth-pied">Un e-mail de temps en temps, uniquement sur la vie de la commune.</p>
            </div>
        </section>

    </main>



</div>

<?= piedDePage() ?>

</body>
</html>
