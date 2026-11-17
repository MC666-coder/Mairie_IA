<?php

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

// NONCE POUR LA CSP
$nonce = bin2hex(random_bytes(16));

/* -----------------------------
   MÉTÉO ACTUELLE (Open-Meteo)
-------------------------------- */
$lat = 47.2184;  // Nantes
$lon = -1.6539;  // Nantes

$weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true";

$currentTemp = null;
$currentWind = null;
$weatherCode = null;

// Appel cURL sécurisé
$ch = curl_init($weatherUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_USERAGENT => 'Mairie-LAiguillon-sur-Vie/1.0'
]);
$weatherResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($weatherResponse !== false && $httpCode === 200) {
    $weather = json_decode($weatherResponse, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($weather['current_weather'])) {
        $currentTemp = $weather['current_weather']['temperature'] ?? null;
        $currentWind = $weather['current_weather']['windspeed'] ?? null;
        $weatherCode = $weather['current_weather']['weathercode'] ?? null;
    }
}

/* -----------------------------
   ICÔNES MÉTÉO
-------------------------------- */
$weatherIcons = [
    0 => "☀️",
    1 => "🌤️",
    2 => "⛅",
    3 => "☁️",
    45 => "🌫️",
    48 => "🌫️",
    51 => "🌦️",
    53 => "🌦️",
    55 => "🌧️",
    61 => "🌧️",
    63 => "🌧️",
    65 => "🌧️",
    71 => "❄️",
    73 => "❄️",
    75 => "❄️",
    95 => "⛈️",
    96 => "⛈️",
    99 => "⛈️",
];

$weatherIcon = $weatherIcons[$weatherCode] ?? "🌡️";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <!-- Content Security Policy stricte pour page publique -->
    <meta http-equiv="Content-Security-Policy"
      content="
        default-src 'self';
        img-src 'self' data:;
        style-src 'self' 'unsafe-inline';
        script-src 'self' 'nonce-<?= $nonce ?>';
        object-src 'none';
        base-uri 'self';
        connect-src 'self' https://api.open-meteo.com;
    ">


</head>

<body>
    <?php if (!empty($_SESSION['newsletter_success'])): ?>
        <?php unset($_SESSION['newsletter_success']); ?>

        <div id="popup-success" class="popup-overlay">
            <div class="popup-box">

                <!-- SVG VALIDATION -->
                <svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M16 12l-4 4-2-2"></path>
                </svg>

                <h3>Inscription réussie</h3>
                <p>
                    Merci de vous être abonné à notre newsletter.<br>
                    Vous recevrez prochainement les informations, alertes et actualités de la mairie.
                </p>

                <button id="popup-ok">OK</button>
            </div>
        </div>

        <!-- JS EXTERNE -->
        <script src="assets/js/newsletter.js?v=<?= @filemtime(__DIR__ . '/assets/js/newsletter.js') ?>"></script>


    <?php endif; ?>


    <div class="page-wrapper">

        <!-- HEADER -->
        <header id="header-agent">

            <!-- Bandeau République Française -->
            <div class="rf-banner">
                <span class="rf-title">République Française</span>
                <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
            </div>

            <!-- Logo + Navigation -->
            <div class="header-main">
                <nav id="nav-agent">
                    <a href="index.php" class="active">Accueil</a>
                    <a href="agenda.php">Agenda</a>
                    <a href="Actualités.php">Actualités</a>
                    <a href="Services.php">Services</a>
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

        <!-- ALERTES IMPORTANTES -->
        <div class="alertes-container">

            <!-- Alerte météo -->
            <div class="alerte alerte-meteo">
                <span class="alerte-label">Météo actuelle</span>

                <?php if ($currentTemp !== null && $currentWind !== null): ?>
                    <p style="font-size:1.2em;">
                        <?= htmlspecialchars($weatherIcon, ENT_QUOTES, 'UTF-8') ?>
                        <strong><?= htmlspecialchars((string)$currentTemp, ENT_QUOTES, 'UTF-8') ?>°C</strong><br>
                        <strong>Vent :</strong> <?= htmlspecialchars((string)$currentWind, ENT_QUOTES, 'UTF-8') ?> km/h
                    </p>
                <?php elseif (!empty($curlError)): ?>
                    <p>Météo momentanément indisponible.</p>
                <?php else: ?>
                    <p>Météo momentanément indisponible.</p>
                <?php endif; ?>
            </div>

            <!-- Exemple alerte travaux -->
            <div class="alerte alerte-travaux">
                <span class="alerte-label">Travaux</span>
                <p>Travaux de voirie rue du Centre du 12 au 18 février.</p>
            </div>

            <!-- Exemple alerte événement -->
            <div class="alerte alerte-evenement">
                <span class="alerte-label">Événement</span>
                <p>Fête du Printemps : circulation modifiée samedi 15 février.</p>
            </div>

        </div>

        <!-- BANDE D'ACTUALITÉS RAPIDES -->
        <div class="actu-flash">
            <div class="actu-items">
                <span>Quoi de neuf cette semaine ?</span>
                <span>Travaux de voirie en cours</span>
                <span>Événements du week-end</span>
                <span>Nouveaux horaires de la mairie</span>
            </div>
        </div>

        <!-- HERO BANNER -->
        <section class="hero-banner">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>Mairie de L’Aiguillon-sur-Vie</h1>
                <p>Un service public moderne, proche de vous.</p>
                <a href="Services.php" class="hero-btn">Découvrir les services</a>
            </div>
        </section>

        <!-- CONTENU PRINCIPAL -->
        <main id="content_portail_citoyen">

            <!-- INTRO -->
            <section class="intro-section">
                <h2>Bienvenue sur le site officiel de la commune</h2>
                <p>
                    Retrouvez les services municipaux, suivez les actualités locales et accédez à vos démarches en ligne.
                    Notre assistant intelligent est disponible pour vous accompagner à tout moment.
                </p>
            </section>

            <!-- CARTES SERVICES -->
            <section class="services-premium">
                <h2 class="section-title">Services municipaux</h2>
                <p class="section-subtitle">Accédez rapidement aux services essentiels de votre commune.</p>

                <div class="services-grid">

                    <!-- Démarches administratives -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                                 stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <h3>Démarches administratives</h3>
                        <p>Effectuez vos demandes en ligne facilement.</p>
                        <a href="choix_demarche.php" class="service-btn">Accéder</a>
                    </div>

                    <!-- Actualités -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                                 stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>
                                <line x1="8" y1="8" x2="16" y2="8"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                        </div>
                        <h3>Actualités</h3>
                        <p>Suivez les informations et annonces municipales.</p>
                        <a href="Actualités.php" class="service-btn">Voir les actualités</a>
                    </div>

                    <!-- Contact & horaires -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                                 stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <h3>Contact & horaires</h3>
                        <p>Retrouvez les coordonnées et horaires de la mairie.</p>
                        <a href="Contacts.php" class="service-btn">Nous contacter</a>
                    </div>

                </div>
            </section>

            <!-- CHATBOT -->
            <section class="assistant-premium">
                <h2 class="section-title">Assistant citoyen</h2>
                <p class="section-subtitle">
                    Connectez-vous pour accéder à l’assistant intelligent et obtenir une aide personnalisée.
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
                        Votre assistant citoyen est disponible 24h/24 pour vous guider dans vos démarches.
                    </p>

                    <div class="assistant-cta">
                        <a href="connexion_citoyen.php" class="assistant-btn primary">Se connecter</a>
                        <a href="inscription_citoyen.php" class="assistant-btn secondary">Créer un compte</a>
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

    <!-- FOOTER -->
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
            &copy; 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
        </div>

    </footer>

</body>
</html>
