<?php require_once __DIR__ . '/../Backend/Fonctions/icones.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Intelligent de la Mairie</title>
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

            <h1>Bienvenue dans l’Assistant Intelligent de la Mairie</h1>

            <p>
                Notre assistant intelligent est là pour vous aider à naviguer à travers les services municipaux,
                répondre à vos questions fréquentes, et vous fournir des informations en temps réel sur les activités
                et événements de la commune.
            </p>

            <p>
                N’hésitez pas à poser vos questions ou à demander de l’aide concernant les démarches administratives,
                les horaires d’ouverture, ou tout autre sujet lié à la mairie.
            </p>

            <h2>Interface du Chatbot</h2>

            <div id="chatbot_interface">
                <!-- Interface du chatbot à intégrer ici -->
                <p>[Interface du Chatbot]</p>
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
