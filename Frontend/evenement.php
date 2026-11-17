<?php
/* =========================================================
   Détail d'un événement public de l'agenda
   ---------------------------------------------------------
   Seuls les événements publiés et visibles des citoyens sont affichés
   (sinon : page « introuvable », sans indiquer s'il existe).
   ========================================================= */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/evenements_publics.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$evenement = null;
if ($id) {
    try {
        $evenement = evenementPublic($pdo, $id);
    } catch (PDOException $e) {
        error_log("evenement : lecture impossible");
    }
}
if (!$evenement) {
    http_response_code(404);
}

$texte = $evenement ? ($evenement['version_longue'] ?: $evenement['description']) : '';
$memeJour = $evenement && date('Y-m-d', strtotime($evenement['date_debut'])) === date('Y-m-d', strtotime($evenement['date_fin']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $evenement ? htmlspecialchars($evenement['titre']) . ' - ' : 'Événement introuvable - ' ?>Agenda municipal</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
                <a href="agenda.php" class="active" aria-current="page">Agenda</a>
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

    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><?= $evenement ? htmlspecialchars($evenement['titre']) : 'Événement introuvable' ?></h1>
            <?php if ($evenement && $evenement['nom_service']): ?>
                <p><?= htmlspecialchars($evenement['nom_service']) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <main id="content_portail_citoyen">
        <section class="evenement-detail">
            <p><a href="agenda.php">&larr; Retour à l’agenda</a></p>

            <?php if (!$evenement): ?>
                <p class="no-data">Cet événement n’existe pas ou n’est plus publié.</p>
            <?php else: ?>
                <dl class="evenement-infos">
                    <dt>Date</dt>
                    <dd>
                        <?php if ($memeJour): ?>
                            <?= htmlspecialchars(dateEvenement($evenement['date_debut'])) ?>
                            jusqu’à <?= date('H\hi', strtotime($evenement['date_fin'])) ?>
                        <?php else: ?>
                            Du <?= htmlspecialchars(dateEvenement($evenement['date_debut'])) ?>
                            au <?= htmlspecialchars(dateEvenement($evenement['date_fin'])) ?>
                        <?php endif; ?>
                    </dd>
                    <?php if (!empty($evenement['lieu'])): ?>
                        <dt>Lieu</dt>
                        <dd><?= htmlspecialchars($evenement['lieu']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($evenement['nom_service'])): ?>
                        <dt>Organisé par</dt>
                        <dd><?= htmlspecialchars($evenement['nom_service']) ?></dd>
                    <?php endif; ?>
                </dl>

                <div class="evenement-texte" dir="auto">
                    <?= nl2br(htmlspecialchars($texte)) ?>
                </div>
            <?php endif; ?>
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

</body>
</html>
