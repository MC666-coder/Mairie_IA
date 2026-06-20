<?php
// Sécurisation cohérente avec tout le portail
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services municipaux - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Content Security Policy stricte -->
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

    <!-- HEADER IDENTIQUE À LA PAGE D’ACCUEIL -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="agenda.php">Agenda</a>
                <a href="Actualités.php">Actualités</a>
                <a href="Services.php" class="active">Services</a>
                <a href="Contacts.php">Contacts</a>
                <a href="Newsletter.php">Newsletter</a>

                <!-- Connexion -->
                <a href="choix_connexion.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Connexion
                </a>

                <!-- Inscription -->
                <a href="choix_inscription.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="#0a7c2f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M6 20v-1a6 6 0 0 1 12 0v1"/>
                            <line x1="20" y1="12" x2="20" y2="18"/>
                            <line x1="23" y1="15" x2="17" y2="15"/>
                        </svg>
                    </span>
                    Inscription
                </a>
            </nav>
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Services municipaux</h1>
            <p>Accédez facilement aux services essentiels de votre commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="services-premium">
            <h2 class="section-title">Tous les services</h2>
            <p class="section-subtitle">Retrouvez l’ensemble des services proposés par la mairie.</p>

            <div class="services-grid">

                <div class="service-card">
                    <h3>État civil</h3>
                    <p>Actes de naissance, mariage, décès, livrets de famille et documents officiels.</p>
                    <p class="keywords">Mots-clés : naissance, mariage, acte, livret…</p>
                </div>

                <div class="service-card">
                    <h3>Urbanisme</h3>
                    <p>Permis de construire, déclarations préalables, informations sur les travaux.</p>
                    <p class="keywords">Mots-clés : permis, travaux, chantier…</p>
                </div>

                <div class="service-card">
                    <h3>Voirie et travaux</h3>
                    <p>Entretien des routes, éclairage public, signalisation et interventions techniques.</p>
                    <p class="keywords">Mots-clés : route, trottoir, éclairage…</p>
                </div>

                <div class="service-card">
                    <h3>Environnement</h3>
                    <p>Déchets, espaces verts, nuisances sonores, propreté et qualité de vie.</p>
                    <p class="keywords">Mots-clés : déchets, tri, pollution…</p>
                </div>

                <div class="service-card">
                    <h3>CCAS / Social</h3>
                    <p>Aides sociales, logement, soutien aux familles et personnes vulnérables.</p>
                    <p class="keywords">Mots-clés : aide, logement, RSA…</p>
                </div>

                <div class="service-card">
                    <h3>Scolaire et jeunesse</h3>
                    <p>Inscriptions scolaires, cantine, garderie, activités périscolaires.</p>
                    <p class="keywords">Mots-clés : école, cantine, garderie…</p>
                </div>

                <div class="service-card">
                    <h3>Communication</h3>
                    <p>Site internet, réseaux sociaux, affichage municipal et informations publiques.</p>
                    <p class="keywords">Mots-clés : communication, réseaux…</p>
                </div>

                <div class="service-card">
                    <h3>Culture et sport</h3>
                    <p>Équipements sportifs, activités culturelles, soutien aux associations.</p>
                    <p class="keywords">Mots-clés : sport, culture, association…</p>
                </div>

                <div class="service-card">
                    <h3>Finances</h3>
                    <p>Budget municipal, taxes locales, factures et comptabilité.</p>
                    <p class="keywords">Mots-clés : taxe, facture, budget…</p>
                </div>

                <div class="service-card">
                    <h3>Ressources humaines</h3>
                    <p>Recrutement, gestion du personnel, stages et candidatures.</p>
                    <p class="keywords">Mots-clés : emploi, recrutement, CV…</p>
                </div>

            </div>
        </section>

    </main>

    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

<footer id="footer-premium">
    <div class="footer-content">

        <div class="footer-col">
            <h3 class="footer-title">Commune de L’Aiguillon-sur-Vie</h3>
            <p class="footer-slogan">Vos idées, Notre action,<br>Partageons la réussite de L'Aiguillon-sur-Vie.</p>
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
        &copy; 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>
</footer>

</body>
</html>
