<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/regles_mot_de_passe.php';

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Après une erreur : on réaffiche ce que le citoyen avait saisi (jamais le mot de passe)
$saisie = $_SESSION['inscription_valeurs'] ?? [];
unset($_SESSION['inscription_valeurs']);
$valeur = fn(string $cle): string => (string) ($saisie[$cle] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Citoyen - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>"/>

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

    <!-- HEADER PREMIUM -->
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
                <a href="Services.php">Services</a>
                <a href="Contacts.php">Contacts</a>
                <a href="Newsletter.php">Newsletter</a>

                <?= boutonsConnexion('inscription') ?>
            </nav>
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Inscription Citoyen</h1>
            <p>Créez votre espace personnel pour accéder aux services municipaux.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <div class="carte-auth carte-auth-large">
                <?= retourAuth('choix_inscription.php', 'Retour') ?>
                <?= enteteAuth('inscription', 'Créer un compte', 'Quelques informations pour accéder aux services de la mairie') ?>
                <?= messageAuth('inscription_error') ?>
                <form action="traitement_inscription_citoyen.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="champs-auth-duo">
                        <?= champAuth('nom_citoyen', 'Nom', 'text', 'utilisateur', '', $valeur('nom')) ?>
                        <?= champAuth('prenom_citoyen', 'Prénom', 'text', 'utilisateur', '', $valeur('prenom')) ?>
                    </div>
                    <?= champAuth('email_citoyen', 'Adresse e-mail', 'email', 'courrier', '', $valeur('email'),
                                  'Un lien de confirmation vous sera envoyé à cette adresse.') ?>
                    <?= champAuth('telephone_citoyen', 'Téléphone', 'tel', 'telephone', '', $valeur('telephone'),
                                  '10 chiffres, sans espace (ex. 0612345678)') ?>
                    <?= champAuth('password', 'Mot de passe', 'password', 'cadenas', '', '',
                                  'Votre mot de passe doit contenir :', listeReglesMotDePasse('password')) ?>
                    <?= champAuth('password_confirm', 'Confirmer le mot de passe', 'password', 'cadenas', '', '', '',
                                  '<p class="champ-auth-erreur" id="message-confirmation" hidden>Les deux mots de passe ne correspondent pas.</p>') ?>

                    <button type="submit" class="btn-submit">Créer le compte</button>
                </form>

                <p class="carte-auth-pied">Déjà inscrit ? <a href="connexion_citoyen.php">Se connecter</a></p>
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
        &copy; 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>

</footer>

<script src="assets/js/mot_de_passe_visible.js?v=<?= @filemtime(__DIR__ . '/assets/js/mot_de_passe_visible.js') ?>"></script>
<script src="assets/js/regles_mot_de_passe.js?v=<?= @filemtime(__DIR__ . '/assets/js/regles_mot_de_passe.js') ?>"></script>
</body>
</html>
