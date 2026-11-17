<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès aux démarches - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <!-- Content Security Policy stricte pour une page statique -->
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   frame-ancestors 'none';">
</head>

<body>
<div class="page-wrapper">

    <!-- HEADER PREMIUM -->
    <header id="header-agent">

        <!-- Bandeau République Française -->
        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <!-- Navigation -->
        <div class="header-main">
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="agenda.php">Agenda</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>

                <a href="connexion_citoyen.php" class="nav-btn">Connexion</a>
                <a href="inscription_citoyen.php" class="nav-btn">Inscription</a>
            </nav>
        </div>

    </header>

    <!-- HERO -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Accès aux démarches</h1>
            <p>Pour continuer, veuillez vous connecter ou créer un compte citoyen.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Identification requise</h2>
            <p class="section-subtitle">
                L’accès aux démarches en ligne nécessite une authentification sécurisée.
            </p>

            <div class="assistant-card">

                <div class="assistant-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none"
                         stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v.01"/>
                        <path d="M12 8v4"/>
                    </svg>
                </div>

                <p class="assistant-text">
                    Connectez-vous si vous avez déjà un compte, ou créez-en un pour accéder à l’ensemble des services municipaux.
                </p>

                <div class="assistant-cta">
                    <a href="choix_connexion.php" class="assistant-btn primary">Se connecter</a>
                    <a href="choix_inscription.php" class="assistant-btn secondary">Créer un compte</a>
                </div>

            </div>

        </section>

    </main>

    <!-- BANDE TRICOLORE -->
    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

<!-- FOOTER PREMIUM -->
<footer id="footer-premium">

    <div class="footer-content">

        <div class="footer-col">
            <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
            <p class="footer-slogan">
                Vos idées, Notre action,<br>
                Partageons la réussite de L'Aiguillon-sur-Vie.
            </p>
        </div>

        <div class="footer-col">
            <h4>Informations légales</h4>
            <ul class="footer-links">
                <li><a href="mentions-legales.php">Mentions légales</a></li>
                <li><a href="politique-confidentialite.php">Politique de confidentialité</a></li>
                <li><a href="accessibilite.php">Accessibilité</a></li>
                <li><a href="rgpd.php">Données personnelles (RGPD)</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Coordonnées</h4>
            <p>Mairie de L’Aiguillon-sur-Vie</p>
            <p>Adresse : [Ton adresse ici]</p>
            <p>Téléphone : [Ton numéro]</p>
            <p>Email : [Ton email]</p>

            <h4 style="margin-top:15px;">Horaires</h4>
            <p>Lun–Ven : 9h00–12h00 / 14h00–17h00</p>
            <p>Samedi : 9h00–12h00</p>
        </div>

    </div>

    <div class="footer-bottom">
        © 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>

</footer>

</body>
</html>
