<?php
/* =========================================================
   Gabarit des petites pages du parcours citoyen
   (vérification de l'e-mail, mot de passe oublié, nouveau mot de passe)
   En-tête et pied de page partagés : Backend/Fonctions/gabarit_public.php.
   Usage :
       debutPageAuth('Titre onglet', 'Titre du bandeau', 'Sous-titre');
       ... contenu (carte .carte-auth) ...
       finPageAuth(['mot_de_passe_visible.js']);
   ========================================================= */
require_once __DIR__ . '/formulaire_auth.php';
require_once __DIR__ . '/gabarit_public.php';

function debutPageAuth(string $titreOnglet, string $titreBandeau, string $sousTitre): void
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $css = 'assets/css/style.css?v=' . @filemtime(__DIR__ . '/../../Frontend/assets/css/style.css');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($titreOnglet) ?> - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="<?= $e($css) ?>"/>
    <?= feuillesGabarit() ?>
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';
                   script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';">
</head>
<body>
<div class="page-wrapper">

    <?= enTetePublic('connexion') ?>

    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><?= $e($titreBandeau) ?></h1>
            <p><?= $e($sousTitre) ?></p>
        </div>
    </section>

    <main id="content_portail_citoyen">
        <section class="connexion-section">
    <?php
}

function finPageAuth(array $scripts = []): void
{
    ?>
        </section>
    </main>
</div>

<?= piedDePage() ?>
<?php foreach ($scripts as $script):
    $fichier = basename($script); ?>
<script src="assets/js/<?= htmlspecialchars($fichier, ENT_QUOTES, 'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/../../Frontend/assets/js/' . $fichier) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
