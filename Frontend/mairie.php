<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mairie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>

<body>
    <div class="page-wrapper">

        <header id="header-agent">
            <div id="logo-agent">𝕸𝖆𝖎𝖗𝖎𝖊 𝖉𝖊 𝖑’𝕬𝖎𝖌𝖚𝖎𝖑𝖑𝖔𝖓‑𝖘𝖚𝖗‑𝖁𝖎𝖊</div>
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="mairie.php">La mairie</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>
                <a href="choix_connexion.php" class="nav-btn">🔒Connexion</a>
                <a href="choix_inscription.php" class="nav-btn">🆕Inscription</a>
            </nav>   
        </header>

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
        <div class="bande-tricolore">
            <div class="bleu"></div>
            <div class="blanc"></div>
            <div class="rouge"></div>
        </div>

    </div>

    <footer id="footer">
        <p>Vos idées, Notre action, Partageons la réussite de L'Aiguillon-sur-Vie</p>
        <p>&copy; 2026 - Commune de L’Aiguillon-sur-Vie</p>
        <a href="#">Mentions légales</a> |
        <a href="#">Politique de confidentialité</a>
    </footer>

</body>
</html>
