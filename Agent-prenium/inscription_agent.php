<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php';

// Seul un manager ou un admin connecté peut créer un compte agent
$niveau_createur = exiger_niveau(['manager', 'admin']);

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
    <title>Créer un agent - Portail Agent</title>
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

                <a href="dashboard_agent.php" class="nav-btn">Espace agent</a>
            </nav>
        </div>

    </header>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Créer un nouvel agent</h1>
            <p>Ajoutez un agent à votre service. Il recevra un email pour activer son compte.</p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Informations de l’agent</h2>
            <p class="section-subtitle">Le nouvel agent recevra un email d’activation.</p>

            <div class="form-card">
                <form action="traitement_création_agent.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="nom_agent">Nom</label>
                    <input type="text" id="nom_agent" name="nom_agent" required>

                    <label for="prenom_agent">Prénom</label>
                    <input type="text" id="prenom_agent" name="prenom_agent" required>

                    <label for="email_agent">Adresse email professionnelle</label>
                    <input type="email" id="email_agent" name="email_agent" required>

                    <label for="service_id">Service</label>
                    <select name="service_id" id="service_id" required>
                        <?php
                        require_once __DIR__ . '/../Backend/Config/db.php';
                        $services = $pdo->query("SELECT id, nom_service FROM services ORDER BY nom_service")->fetchAll();
                        foreach ($services as $service) {
                            echo '<option value="' . (int) $service['id'] . '">'
                                . htmlspecialchars($service['nom_service'], ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                        ?>
                    </select>

                    <label for="role">Rôle</label>
                    <select name="role" id="role" required>
                        <option value="agent">Agent</option>
                        <option value="responsable">Responsable</option>
                    </select>

                    <label for="niveau_acces">Niveau d’accès</label>
                    <select name="niveau_acces" id="niveau_acces" required>
                        <option value="agent">Agent</option>
                        <option value="manager">Manager</option>
                        <?php if ($niveau_createur === 'admin'): ?>
                        <option value="admin">Administrateur</option>
                        <?php endif; ?>
                    </select>

                    <button type="submit" class="btn-submit">Créer l’agent</button>
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
