<?php
// Sécurisation cohérente avec tout le portail
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../Backend/Config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda municipal - Commune de L’Aiguillon-sur-Vie</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/fullcalendar/index.global.min.css">

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

    <!-- HEADER IDENTIQUE À LA PAGE SERVICES -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">
            <nav id="nav-agent">
                <a href="index.php">Accueil</a>
                <a href="agenda.php" class="active">Agenda</a>
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

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Agenda municipal</h1>
            <p>Découvrez tous les événements organisés par la commune.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="agenda-premium">
            <h2 class="section-title">Calendrier & Événements</h2>
            <p class="section-subtitle">Consultez les événements à venir dans votre commune.</p>

            <!-- FILTRES AVANCÉS -->
            <div class="agenda-filtres">
                <select id="filtre-categorie">
                    <option value="">Toutes les catégories</option>
                    <option value="culture">Culture</option>
                    <option value="sport">Sport</option>
                    <option value="jeunesse">Jeunesse</option>
                    <option value="environnement">Environnement</option>
                    <option value="ccas">CCAS</option>
                    <option value="urbanisme">Urbanisme</option>
                </select>

                <select id="filtre-lieu">
                    <option value="">Tous les lieux</option>
                    <?php
                    $lieux = $pdo->query("SELECT DISTINCT lieu FROM agenda WHERE lieu IS NOT NULL AND lieu != ''");
                    while ($l = $lieux->fetch()):
                    ?>
                        <option value="<?= htmlspecialchars($l['lieu']) ?>"><?= htmlspecialchars($l['lieu']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select id="filtre-public">
                    <option value="">Tous les publics</option>
                    <option value="tout-public">Tout public</option>
                    <option value="jeunesse">Jeunesse</option>
                    <option value="seniors">Seniors</option>
                    <option value="familles">Familles</option>
                </select>
            </div>

            <div class="agenda-grid">

                <!-- CALENDRIER -->
                <div class="agenda-calendar">
                    <div id="calendar"></div>

                    <!-- LÉGENDE -->
                    <div class="agenda-legende">
                        <span><span class="legende-couleur" style="background:#1b3a61;"></span> Culture</span>
                        <span><span class="legende-couleur" style="background:#2e7d32;"></span> Sport</span>
                        <span><span class="legende-couleur" style="background:#ef6c00;"></span> Jeunesse</span>
                        <span><span class="legende-couleur" style="background:#1b5e20;"></span> Environnement</span>
                        <span><span class="legende-couleur" style="background:#6a1b9a;"></span> CCAS</span>
                        <span><span class="legende-couleur" style="background:#455a64;"></span> Urbanisme</span>
                    </div>
                </div>

                <!-- LISTE D'ÉVÉNEMENTS -->
                <div class="agenda-list">
                    <h3 class="list-title">Événements à venir</h3>

                    <?php
                    $stmt = $pdo->query("SELECT * FROM agenda WHERE statut = 'publié' ORDER BY date_debut ASC");

                    while ($event = $stmt->fetch()):
                    ?>
                        <div class="event-card">
                            <?php if (!empty($event['image'])): ?>
                                <img src="uploads/agenda/<?= htmlspecialchars($event['image']) ?>" alt="">
                            <?php endif; ?>

                            <div class="event-info">
                                <h4><?= htmlspecialchars($event['titre']) ?></h4>

                                <p class="event-date">
                                    <?= date("d/m/Y H:i", strtotime($event['date_debut'])) ?>
                                </p>

                                <p class="event-lieu"><?= htmlspecialchars($event['lieu']) ?></p>

                                <a href="evenement.php?slug=<?= htmlspecialchars($event['slug']) ?>" class="btn">
                                    Voir l’événement
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>

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

<!-- FULLCALENDAR JS -->

<script src="assets/fullcalendar/index.global.min.js"></script>


<!-- SCRIPT D'INITIALISATION (AJAX) -->
<script src="assets/js/fullcalendar.js"></script>

</body>
</html>
