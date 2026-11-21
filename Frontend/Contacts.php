<?php
// Nom de la commune et adresse du site lus dans .env (Backend/Config/app.php)
require_once __DIR__ . '/../Backend/Config/app.php';
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
</head>
<body>
    <div class="page-wrapper">
        <header id="header-agent">
            <div id="logo-agent">Mairie de <?= $commune ?></div>
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="mairie.php">La mairie</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php" class="active">Contacts</a>
                <a href="Newsletter.php">Newsletter</a>
                <?= boutonsConnexion() ?>
            </nav>
        </header>

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
</body>
</html>
