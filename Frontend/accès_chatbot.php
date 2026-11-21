<?php
session_start();
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

// Si l'utilisateur est déjà connecté → accès direct au chatbot
if (isset($_SESSION['citoyen_id'])) {
    header('Location: assistant_intelligent.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès au Chatbot</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>

<body>
    <div class="page-wrapper">

        <!-- HEADER IDENTIQUE -->
        <header id="header-agent">
            <div id="logo-agent">𝕸𝖆𝖎𝖗𝖎𝖊 𝖉𝖊 𝖑’𝕬𝖎𝖌𝖚𝖎𝖑𝖑𝖔𝖓‑𝖘𝖚𝖗‑𝖁𝖎𝖊</div>
           
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="mairie.php">La mairie</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>
                <?= boutonsConnexion() ?>
            </nav>   
        </header>

        <!-- CONTENU PRINCIPAL -->
        <main id="content_portail_citoyen">

            <h1>Accès au ChatBot Municipal</h1>

            <p>
                Pour utiliser notre Chatbot intelligent et obtenir des réponses personnalisées,
                veuillez d'abord vous connecter à votre compte citoyen.
            </p>

            <p>
                Si vous n'avez pas encore de compte, nous vous invitons à en créer un.
                L'inscription est gratuite et rapide.
            </p>

            <div id="actions_chatbot">
                <a href="choix_connexion.php" class="btn"><?= icone('connexion') ?> Se connecter</a>
                <a href="choix_inscription.php" class="btn"><?= icone('inscription') ?> S'inscrire</a>
            </div>

        </main>

        <!-- BANDE TRICOLORE -->
        <div class="bande-tricolore">
            <div class="bleu"></div>
            <div class="blanc"></div>
            <div class="rouge"></div>
        </div>

    </div>

    <!-- FOOTER IDENTIQUE -->
    <footer id="footer">
        <p>Vos idées, Notre action, Partageons la réussite de L'Aiguillon-sur-Vie</p>
        <p>&copy; 2026 - Commune de L’Aiguillon-sur-Vie</p>
        <a href="#">Mentions légales</a> |
        <a href="#">Politique de confidentialité</a>
    </footer>

</body>
</html>
