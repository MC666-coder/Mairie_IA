<?php
session_start();
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

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
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('') ?>

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


    </div>

    <?= piedDePage() ?>

</body>
</html>
