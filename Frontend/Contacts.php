<?php
// Nom de la commune et adresse du site lus dans .env (Backend/Config/app.php)
require_once __DIR__ . '/../Backend/Config/app.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
$commune = htmlspecialchars(NOM_COMMUNE, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - Mairie de <?= $commune ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>
<body>
    <div class="page-wrapper">
        <?= enTetePublic('contacts') ?>

        <main id="content_portail_citoyen">
            <h1>Contacter la mairie</h1>

            <!-- Page en construction : coordonnées à compléter -->
            <p>Cette page est en cours de construction.</p>
            <p>
                Pour toute démarche, vous pouvez dès maintenant
                <a href="choix_connexion.php">vous connecter à votre espace citoyen</a>
                et écrire à la mairie, ou utiliser l'assistant en ligne disponible 24h/24.
            </p>
        </main>
    </div>
<?= piedDePage() ?>
</body>
</html>
