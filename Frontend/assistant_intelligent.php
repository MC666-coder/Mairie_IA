<?php require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php'; ?>
<?php require_once __DIR__ . '/../Backend/Fonctions/icones.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Intelligent de la Mairie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('') ?>

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


    </div>

    <?= piedDePage() ?>

</body>
</html>
