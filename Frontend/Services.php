<?php require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php'; ?>
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

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

    <?= enTetePublic('services') ?>

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


</div>

<?= piedDePage() ?>

</body>
</html>
