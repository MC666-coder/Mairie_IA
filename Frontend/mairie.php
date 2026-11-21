<?php require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php'; ?>
<?php require_once __DIR__ . '/../Backend/Fonctions/icones.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mairie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('mairie') ?>

        <main id="content_portail_citoyen">
            <h1>À propos de la Mairie de L’Aiguillon-sur-Vie</h1>

            <p>
                La mairie de L’Aiguillon-sur-Vie est le centre administratif de notre commune.
                Elle est dédiée à servir les citoyens en offrant une gamme complète de services municipaux,
                y compris l'état civil, l'urbanisme, les services sociaux, et bien plus encore.
            </p>

            <h2>Nos Services</h2>
            <ul>
                <li><strong>État civil</strong> : Gestion des actes de naissance, mariage, décès.</li>
                <li><strong>Urbanisme</strong> : Traitement des permis de construire, déclarations préalables.</li>
                <li><strong>Voirie et travaux</strong> : Entretien de la voirie, signalisation, éclairage public.</li>
                <li><strong>Environnement</strong> : Gestion des déchets, espaces verts, nuisances sonores.</li>
                <li><strong>CCAS / Social</strong> : Accompagnement social, aides financières, logement.</li>
                <li><strong>Scolaire et jeunesse</strong> : Inscriptions scolaires, cantine, garderie.</li>
                <li><strong>Communication</strong> : Diffusion des informations municipales.</li>
                <li><strong>Culture et sport</strong> : Activités culturelles, associations, équipements sportifs.</li>
                <li><strong>Finances</strong> : Paiements, factures, taxes locales, gestion budgétaire.</li>
                <li><strong>Ressources humaines</strong> : Recrutement, gestion du personnel municipal.</li>
            </ul>


            <h2>Horaires d'ouverture</h2>
            <p>
                La mairie est ouverte du lundi au vendredi, de 8h30 à 12h00 et de 13h30 à 17h00.
            </p>

            <h2>Contactez-nous</h2>
            <p>
                Pour toute question ou demande, contactez-nous par téléphone au xxxxxxxx
                ou par email à xxxx@gmail.com
            </p>
        </main>

        <!-- Bande tricolore -->

    </div>

    <?= piedDePage() ?>

</body>
</html>
