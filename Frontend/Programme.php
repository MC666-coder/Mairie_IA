<?php
session_start();
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

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
</head>

<body>
<div class="page-wrapper">

    <!-- HEADER UNIFIÉ -->
    <header id="header-agent">
        <div id="logo-agent">𝕸𝖆𝖎𝖗𝖎𝖊 𝖉𝖊 𝖑’𝕬𝖎𝖌𝖚𝖎𝖑𝖑𝖔𝖓‑𝖘𝖚𝖗‑𝖁𝖎𝖊</div>

        <nav id="nav-agent">
            <a href="index.php">Accueil</a>
            <a href="mairie.php">La mairie</a>
            <a href="Actualités.php">Actualités</a>
            <a href="Services.php">Services</a>
            <a href="Contacts.php">Contacts</a>
            <a href="choix_connexion.php" class="nav-btn"><?= icone('connexion') ?> Connexion</a>
            <a href="choix_inscription.php" class="nav-btn"><?= icone('inscription') ?> Inscription</a>
        </nav>
    </header>

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

    <!-- BANDE TRICOLORE -->
    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

<!-- FOOTER UNIFIÉ -->
<footer id="footer">
    <p id="slogan">Vos idées, Notre action, Partageons la réussite de L'Aiguillon-sur-Vie</p>
    <p>&copy; 2026 - Commune de L’Aiguillon-sur-Vie</p>
    <a href="#">Mentions légales</a> |
    <a href="#">Politique de confidentialité</a>
</footer>

</body>
</html>
