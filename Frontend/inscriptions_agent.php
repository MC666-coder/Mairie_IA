<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();



// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Agent - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css"/>

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

                <a href="choix_connexion.php" class="nav-btn">Connexion</a>
                <a href="choix_inscription.php" class="nav-btn active">Inscription</a>
            </nav>
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Inscription Agent</h1>
            <p>Créez votre compte professionnel pour accéder à l’espace agent.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Créer un compte agent</h2>
            <p class="section-subtitle">Veuillez remplir les informations ci-dessous.</p>

            <div class="form-card">
                <form action="traitement_inscription_agent.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="nom_agent">Nom</label>
                    <input type="text" id="nom_agent" name="nom_agent" required>

                    <label for="prenom_agent">Prénom</label>
                    <input type="text" id="prenom_agent" name="prenom_agent" required>

                    <label for="email_agent">Adresse email</label>
                    <input type="email" id="email_agent" name="email_agent" required>

                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>

                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>

                    <label for="role">Rôle</label>
                    <select name="role" id="role" required>
                        <option value="agent">Agent</option>
                        <option value="admin">Administrateur</option>
                    </select>

                    <button type="submit" class="btn-submit">Créer le compte</button>
                </form>
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

</body>
</html>
