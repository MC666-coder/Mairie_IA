<?php
/* =========================================================
   Gabarit des petites pages du parcours citoyen
   (vérification de l'e-mail, mot de passe oublié, nouveau mot de passe)
   Même en-tête, bandeau et pied de page que Frontend/connexion_citoyen.php.
   Usage :
       debutPageAuth('Titre onglet', 'Titre du bandeau', 'Sous-titre');
       ... contenu (carte .carte-auth) ...
       finPageAuth(['mot_de_passe_visible.js']);
   ========================================================= */
require_once __DIR__ . '/formulaire_auth.php';

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
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';
                   script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';">
</head>
<body>
<div class="page-wrapper">

    <header id="header-agent">
        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>
        <div class="header-main">
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="agenda.php">Agenda</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>
                <?= boutonsConnexion('connexion') ?>
            </nav>
        </div>
    </header>

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

    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>
</div>

<footer id="footer-premium">
    <div class="footer-content">
        <div class="footer-col">
            <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
            <p class="footer-slogan">Vos idées, Notre action,<br>Partageons la réussite de L'Aiguillon-sur-Vie.</p>
        </div>
        <div class="footer-col">
            <h4>Informations légales</h4>
            <ul class="footer-links">
                <li><a href="mentions-legales.php">Mentions légales</a></li>
                <li><a href="politique-confidentialite.php">Politique de confidentialité</a></li>
                <li><a href="accessibilite.php">Accessibilité</a></li>
                <li><a href="rgpd.php">Données personnelles (RGPD)</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">&copy; 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés</div>
</footer>
<?php foreach ($scripts as $script):
    $fichier = basename($script); ?>
<script src="assets/js/<?= htmlspecialchars($fichier, ENT_QUOTES, 'UTF-8') ?>?v=<?= @filemtime(__DIR__ . '/../../Frontend/assets/js/' . $fichier) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
