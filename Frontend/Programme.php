<?php
session_start();
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

// Si le programme a déjà été affiché durant cette session, on redirige vers l'accueil
if (isset($_SESSION['programme_vu']) && $_SESSION['programme_vu'] === true) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
<div class="page-wrapper">

    <?= enTetePublic('') ?>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <h1><?= icone('mairie') ?> Bienvenue sur le site de L’Aiguillon-sur-Vie</h1>

        <p>
            Explorez les services municipaux, découvrez les projets de la commune et profitez de nos outils numériques pour faciliter vos démarches.
        </p>

        <h2>Ce que vous pouvez faire dès maintenant</h2>

        <ul class="liste-fonctionnalites">
            <li><?= icone('valide') ?> Accéder aux services municipaux</li>
            <li><?= icone('valide') ?> Contacter la mairie via le chatbot intelligent</li>
            <li><?= icone('valide') ?> Suivre les projets et travaux de la commune</li>
            <li><?= icone('valide') ?> Participer au jeu d’énigmes interactif</li>
            <li><?= icone('valide') ?> S’abonner à la newsletter</li>
            <li><?= icone('valide') ?> Créer votre espace citoyen</li>
            <li><?= icone('valide') ?> Consulter les actualités en temps réel</li>
        </ul>

        <div class="actions-accueil">
            <a href="valider_programme.php" class="btn-submit">Découvrir le site</a>
            <a href="accès_chatbot.php" class="btn-submit">Parler au ChatBot</a>
        </div>

    </main>


</div>

<?= piedDePage() ?>

</body>
</html>
