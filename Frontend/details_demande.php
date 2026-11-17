<?php
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php'; // jeton CSRF de la messagerie

/**
 * Sécurisation renforcée de la session
 */


// Vérifier que l'utilisateur est connecté ET bien un citoyen (et pas un agent)
if (!isset($_SESSION['citoyen_id']) || !isset($_SESSION['email_citoyen'])) {
    http_response_code(403);
    echo "Vous devez être connecté en tant que citoyen pour accéder à cette page.";
    exit();
}

$citoyenId = (int) $_SESSION['citoyen_id'];

/**
 * Récupération sécurisée du citoyen
 */
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
    echo "Citoyen non trouvé.";
    exit();
}

/**
 * Validation de l'ID de la demande
 */
$id = $_GET['id'] ?? null;
if ($id === null || !ctype_digit($id)) {
    http_response_code(400);
    echo "ID de la demande invalide.";
    exit();
}
$id = (int) $id;

/**
 * Récupération sécurisée de la demande appartenant à ce citoyen
 */
$req = $pdo->prepare("
    SELECT
        t.libelle AS type,
        d.message,
        d.priorite,
        d.statut,
        d.created_at,
        services.nom_service,
        services.description_service,
        services.mots_cles
    FROM demandes d
    LEFT JOIN services ON d.service_id = services.id
    LEFT JOIN types_demandes t ON d.type_id = t.id
    WHERE d.id = ?
      AND d.citoyen_id = ?   -- le citoyen ne voit QUE ses propres demandes (anti-IDOR)
    LIMIT 1
");
$req->execute([$id, $citoyenId]);
$demande = $req->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    http_response_code(404);
    echo "Demande non trouvée.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails d'une demande - Espace Citoyen</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <!-- Jeton anti-CSRF lu par messagerie_citoyen.js (en-tête X-CSRF-Token) -->
    <meta name="csrf-token" content="<?= htmlspecialchars(jeton_csrf(), ENT_QUOTES, 'UTF-8') ?>">

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

        <!-- Bandeau République Française -->
        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <!-- Navigation + bulle citoyen -->
        <div class="header-main">

            <nav id="nav-agent">
                <a href="portail_citoyen.php">Tableau de bord</a>
                <a href="nouvelle_demande.php">Créer une demande</a>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#003b8e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Déconnexion
                </a>
            </nav>

            <!-- Bulle de connexion citoyen -->
            <div id="bulle-connexion-agent">
                Prénom: <?= htmlspecialchars($citoyen['prenom_citoyen']) ?><br>
                Nom: <?= htmlspecialchars($citoyen['nom_citoyen']) ?><br>
                id_citoyen: <?= htmlspecialchars((string)$citoyenId) ?>
            </div>

        </div>
    </header>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen">

        <h1>Voici les détails de cette demande</h1>

        <div class="demande-details">
            <p><strong>Type:</strong> <?= htmlspecialchars($demande['type'] ?? 'non renseigné') ?></p>
            <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($demande['message'] ?? 'non renseigné')) ?></p>
            <p><strong>Priorité:</strong> <?= htmlspecialchars($demande['priorite'] ?? 'non renseigné') ?></p>

            <div class="statut-badge statut-<?= htmlspecialchars($demande['statut'] ?? 'inconnu') ?>">
                <p><strong>Statut:</strong> <?= htmlspecialchars($demande['statut'] ?? 'non renseigné') ?></p>
            </div>

            <p><strong>Date de création:</strong> <?= htmlspecialchars($demande['created_at'] ?? 'non renseigné') ?></p>

            <h2>Service concerné</h2>
            <p><strong>Nom du service:</strong> <?= htmlspecialchars($demande['nom_service'] ?? 'non renseigné') ?></p>
            <p><strong>Description du service:</strong> <?= nl2br(htmlspecialchars($demande['description_service'] ?? 'non renseigné')) ?></p>
            <p><strong>Mots-clés du service:</strong> <?= htmlspecialchars($demande['mots_cles'] ?? 'non renseigné') ?></p>
        </div>

        <!-- ÉCHANGES AVEC LA MAIRIE (messagerie_citoyen.js, Backend/citoyen/messagerie.php) -->
        <section class="messagerie-citoyen" id="messagerie-citoyen" data-demande-id="<?= (int) $id ?>"
                 aria-labelledby="titre-messagerie">
            <h2 id="titre-messagerie">Échanges avec la mairie</h2>
            <p class="messagerie-aide">
                L’agent qui traite votre demande vous écrit ici s’il a besoin d’un document
                ou pour vous envoyer une attestation. Vous pourrez alors lui répondre.
            </p>

            <!-- Case 1 : la conversation -->
            <div class="case-messagerie">
                <h3 class="case-titre">💬 Conversation</h3>
                <div class="fil-messages" id="fil-messages" role="log" aria-live="polite" aria-label="Conversation avec la mairie">
                    <p class="fil-vide">Chargement de la conversation…</p>
                </div>
            </div>

            <!-- Case 2 : la réponse du citoyen (document et/ou court message, après un message de l'agent) -->
            <div class="case-messagerie">
                <h3 class="case-titre">✍️ Votre réponse</h3>

                <!-- Affiché tant que le citoyen ne peut pas répondre -->
                <p class="reponse-indisponible" id="reponse-indisponible" hidden></p>

                <form class="saisie-message" id="form-reponse-citoyen" hidden>
                    <label for="fichier-citoyen" class="etiquette-champ">Document à envoyer (facultatif)</label>
                    <input type="file" id="fichier-citoyen" name="fichier"
                           accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" aria-describedby="aide-fichier">
                    <p class="aide-champ" id="aide-fichier">PDF, JPG, PNG, DOCX ou XLSX.</p>

                    <label for="texte-message" class="etiquette-champ">Votre message (facultatif)</label>
                    <textarea id="texte-message" name="message" rows="2" maxlength="500" dir="auto"
                              placeholder="Ex. : Voici mon justificatif de domicile."></textarea>
                    <div class="saisie-actions">
                        <span class="compteur-caracteres" id="compteur-caracteres">0 / 500</span>
                        <button type="submit" id="envoyer-message">Envoyer à la mairie</button>
                    </div>
                    <p class="messagerie-statut" id="messagerie-statut" role="status"></p>
                </form>
            </div>

            <!-- Case 3 : les documents, en deux cases côte à côte -->
            <div class="documents-citoyen">
                <div class="case-messagerie">
                    <h3 class="case-titre">📥 Documents de la mairie
                        <span class="compteur-documents" id="compteur-docs-mairie">0</span></h3>
                    <ul id="liste-docs-mairie" class="liste-documents"></ul>
                </div>
                <div class="case-messagerie">
                    <h3 class="case-titre">📤 Vos documents
                        <span class="compteur-documents" id="compteur-docs-citoyen">0</span></h3>
                    <ul id="liste-docs-citoyen" class="liste-documents"></ul>
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

</div>

<script src="assets/js/messagerie_citoyen.js?v=<?= @filemtime(__DIR__ . '/assets/js/messagerie_citoyen.js') ?>"></script>
</body>
</html>
