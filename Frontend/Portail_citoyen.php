<?php
/* ---------------------------------------
   Sécurisation session
---------------------------------------- */

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Config/api_mairie.php';



// Vérification stricte du rôle citoyen
if (!isset($_SESSION['citoyen_id'])) {
    http_response_code(403);
    die("Accès refusé.");
}


$citoyenId = (int) $_SESSION['citoyen_id'];

/* ---------------------------------------
   Récupération sécurisée du citoyen
---------------------------------------- */

$getCitoyen = $pdo->prepare("
    SELECT id, nom_citoyen, prenom_citoyen 
    FROM citoyens 
    WHERE id = ?
    LIMIT 1
");
$getCitoyen->execute([$citoyenId]);
$citoyen = $getCitoyen->fetch(PDO::FETCH_ASSOC);

if (!$citoyen) {
    http_response_code(404);
    die("Citoyen non trouvé.");
}

/* ---------------------------------------
   Statistiques sécurisées
---------------------------------------- */

$demandecount = $pdo->prepare("
    SELECT 
        SUM(statut='nouvelle') AS NEW_demande,
        SUM(statut='analyse_ia') AS analyse_IA,
        SUM(statut='en_cours') AS en_cours,
        SUM(statut='resolue') AS Resolue
    FROM demandes
    WHERE citoyen_id = ?
");
$demandecount->execute([$citoyenId]);

$stats = $demandecount->fetch(PDO::FETCH_ASSOC);

// Valeurs par défaut si NULL
$stats = array_map(fn($v) => $v ?? 0, $stats);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Citoyen - Commune de L’Aiguillon-sur-Vie</title>
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
    <?php if (!empty($_SESSION['inscription_success'])): ?>
        <div class="popup-success" id="popupSuccess">
            <div class="popup-box">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 12l2 2l4-4"></path>
                </svg>

                <h2>Compte créé avec succès</h2>
                <p>Votre compte vient d’être créé. Bienvenue dans l’espace Citoyen.</p>

                <button id="popupOk">OK</button>
            </div>
        </div>

        <!-- SCRIPT QUI FONCTIONNE À TOUS LES COUPS -->
        <script src="assets/js/inscription_popup_citoyen.js"></script>

    <?php unset($_SESSION['inscription_success']); ?>
    <?php endif; ?>
<div class="page-wrapper">

    <!-- HEADER PREMIUM -->
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">

            <nav id="nav-agent">
                <a href="portail_citoyen.php" class="active">Tableau de bord</a>
                <a href="nouvelle_demande.php">Créer une demande</a>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003b8e" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Déconnexion
                </a>
            </nav>

            <!-- Bulle de connexion -->
            <div id="bulle-connexion-agent">
                Prénom : <?= htmlspecialchars($citoyen['prenom_citoyen']) ?><br>
                Nom : <?= htmlspecialchars($citoyen['nom_citoyen']) ?><br>
                ID citoyen : <?= htmlspecialchars((string)$citoyenId) ?>
            </div>

        </div>
    </header>

    <!-- HERO -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Portail Citoyen</h1>
            <p>Suivez vos demandes et échangez avec la mairie.</p>
        </div>
    </section>

    <!-- CONTENU -->
    <main id="content_portail_citoyen">

        <h2 class="section-title">
            Bienvenue, <?= htmlspecialchars($citoyen['prenom_citoyen'] . ' ' . $citoyen['nom_citoyen']) ?> !
        </h2>

        <!-- STATISTIQUES -->
        <section class="stats-agent">
            <div class="stat-card"><h3>Nouvelles demandes</h3><p><?= htmlspecialchars((string)$stats['NEW_demande']) ?></p></div>
            <div class="stat-card"><h3>Analysées par l'IA</h3><p><?= htmlspecialchars((string)$stats['analyse_IA']) ?></p></div>
            <div class="stat-card"><h3>En cours</h3><p><?= htmlspecialchars((string)$stats['en_cours']) ?></p></div>
            <div class="stat-card"><h3>Résolues</h3><p><?= htmlspecialchars((string)$stats['Resolue']) ?></p></div>
        </section>

        <!-- DERNIÈRES DEMANDES -->
        <section class="last-requette">
            <h2 class="section-title">Vos dernières demandes</h2>

            <div class="last-requests-container">
                <?php
                $allrequets = $pdo->prepare("
                    SELECT d.id, t.libelle AS type, d.message, d.priorite, d.statut, d.created_at,
                           services.nom_service, services.description_service
                    FROM demandes d
                    LEFT JOIN services ON d.service_id = services.id
                    LEFT JOIN types_demandes t ON d.type_id = t.id
                    WHERE d.citoyen_id = ?
                    ORDER BY d.created_at DESC
                ");
                $allrequets->execute([$citoyenId]);

                if ($allrequets->rowCount() === 0): ?>
                    <p class='no-data'>Aucune demande trouvée.</p>
                <?php else:
                    while ($request = $allrequets->fetch()): ?>
                        <div class="request-card">
                            <p><strong>Type :</strong> <?= htmlspecialchars($request['type']?? 'non renseigné') ?></p>
                            <p><strong>Message :</strong> <?= nl2br(htmlspecialchars($request['message'])?? 'non renseigné') ?></p>
                            <p><strong>Priorité :</strong> <?= htmlspecialchars($request['priorite']?? 'non renseigné') ?></p>
                            <p><strong>Statut :</strong> <?= htmlspecialchars($request['statut']?? 'non reseigné') ?></p>
                            <p><strong>Date :</strong> <?= htmlspecialchars($request['created_at']?? 'non renseigné') ?></p>
                            <p><strong>Service :</strong>
                                <?= htmlspecialchars($request['nom_service']?? 'non renseigné') ?> -
                                <?= htmlspecialchars($request['description_service']?? 'non renseigné') ?>
                            </p>

                            <a class="btn-details"
                               href="details_demande.php?id=<?= urlencode($request['id']) ?>">
                                Voir les détails
                            </a>
                        </div>
                <?php endwhile; endif; ?>
            </div>
        </section>

        <!-- CHATBOT PREMIUM -->
        <div id="chatbot" class="chatbot-hidden" data-user="<?= htmlspecialchars((string)$citoyenId) ?>">
            <div id="chatbot-header">🇫🇷 Assistant IA de la Mairie</div>
            <div id="chat-window"></div>

            <div id="ia-loading" style="display:none;">
                <div class="loader"></div>
                <span>L’assistant rédige sa réponse…</span>
            </div>

            <div id="chat-input">
                <input type="text" id="message" maxlength="500" placeholder="Écrivez votre message...">
                <button id="send" type="button">Envoyer</button>
            </div>
        </div>

    </main>

    <!-- BANDE TRICOLORE -->
    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

<script src="assets/js/chatbot.js"></script>

<!-- BOUTON FLOTTANT IA -->
<button id="ia-toggle-btn" class="ia-floating-btn">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 16v.01"/>
        <path d="M12 8v4"/>
    </svg>
</button>

<script src="assets/js/click_chatbot.js"></script>


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
                <li><a href="#">Mentions légales</a></li>
                <li><a href="#">Politique de confidentialité</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Coordonnées</h4>
            <p>Mairie de L’Aiguillon-sur-Vie</p>
            <p>Adresse : [Ton adresse ici]</p>
            <p>Téléphone : [Ton numéro]</p>
            <p>Email : [Ton email]</p>
        </div>

    </div>

    <div class="footer-bottom">
        © 2026 - Commune de L’Aiguillon-sur-Vie — Tous droits réservés
    </div>

</footer>

</body>
</html>
