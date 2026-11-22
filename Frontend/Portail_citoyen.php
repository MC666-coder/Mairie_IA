<?php
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/carte_profil.php';
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
require_once __DIR__ . '/../Backend/Middleware/auth.php'; // jeton CSRF du chatbot



// Vérification stricte du rôle citoyen
// Pas connecté ou session expirée : retour à la connexion avec un message
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';
exigerCitoyen();


$citoyenId = (int) $_SESSION['citoyen_id'];

/* ---------------------------------------
   Chatbot : nouvelle page = nouvelle conversation.
   La fenêtre du chat est vide à l'affichage : l'historique gardé côté
   serveur doit l'être aussi, sinon l'IA « se souviendrait » d'échanges
   que le citoyen ne voit plus (et reproduirait d'anciennes réponses).
---------------------------------------- */
unset($_SESSION['chat_ia_historique'], $_SESSION['brouillon_demande']);

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
   Statistiques et demandes : même gabarit que la mise à jour
   automatique (Backend/citoyen/tableau_de_bord.php)
---------------------------------------- */
require_once __DIR__ . '/../Backend/Fonctions/tableau_citoyen.php';
$tableau = donneesTableauCitoyen($pdo, $citoyenId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Citoyen - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/carte_profil.css?v=<?= @filemtime(__DIR__ . '/assets/css/carte_profil.css') ?>">
    <?= feuillesGabarit() ?>
    <!-- Jeton anti-CSRF lu par chatbot.js (en-tête X-CSRF-Token) -->
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
        <script src="assets/js/inscription_popup_citoyen.js?v=<?= @filemtime(__DIR__ . '/assets/js/inscription_popup_citoyen.js') ?>"></script>

    <?php unset($_SESSION['inscription_success']); ?>
    <?php endif; ?>
<div class="page-wrapper">

    <?= enTeteCitoyen('tableau', $citoyen, (int) $citoyenId) ?>

    <!-- CONTENU -->
    <main id="content_portail_citoyen">

        <!-- EN-TÊTE DU TABLEAU DE BORD -->
        <div class="mb-6 flex flex-col gap-4 text-left sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="m-0 text-sm font-medium text-slate-500">Portail citoyen</p>
                <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    Bonjour, <?= htmlspecialchars($citoyen['prenom_citoyen']) ?>
                </h1>
                <p class="m-0 mt-1 text-sm text-slate-500">Suivez vos demandes et échangez avec la mairie.</p>
            </div>
            <a href="nouvelle_demande.php"
               class="inline-flex w-fit items-center gap-2 rounded-full bg-[#003b8e] px-5 py-2.5 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-[#002a66]">
                <?= icone('plus') ?> Nouvelle demande
            </a>
        </div>

        <?php if (($_GET['demande'] ?? '') === 'success'): ?>
            <div class="mb-6 flex items-start gap-2 rounded-2xl bg-green-50 px-4 py-3 text-left text-sm font-medium text-green-800 ring-1 ring-green-600/20" role="status">
                <?= icone('valide', 'mt-0.5') ?> Votre demande a bien été envoyée à la mairie. Vous pouvez suivre son avancement ci-dessous.
            </div>
        <?php endif; ?>

        <?php if (($_GET['erreur'] ?? '') === 'demande-introuvable'): ?>
            <div class="mb-6 flex items-start gap-2 rounded-2xl bg-red-50 px-4 py-3 text-left text-sm font-medium text-red-800 ring-1 ring-red-600/20" role="alert">
                <?= icone('croix') ?> Cette demande est introuvable. Voici la liste de vos demandes.
            </div>
        <?php endif; ?>

        <!-- STATISTIQUES (mises à jour automatiquement par tableau_citoyen.js) -->
        <section id="stats-citoyen" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Vos demandes en chiffres">
            <?= htmlStatsCitoyen($tableau['stats']) ?>
        </section>

        <!-- DEMANDES : une carte par demande avec sa frise d'avancement -->
        <section class="mt-10 text-left" aria-labelledby="titre-demandes">
            <h2 id="titre-demandes" class="m-0 mb-4 text-lg font-bold text-slate-900">Vos demandes</h2>

            <div class="flex flex-col gap-3" id="liste-demandes"
                 data-signature="<?= signatureTableauCitoyen($tableau) ?>">
                <?= htmlDemandesCitoyen($tableau['demandes']) ?>
            </div>
            <!-- Annonce les mises à jour aux lecteurs d'écran -->
            <p id="annonce-tableau" class="visually-hidden" aria-live="polite"></p>
        </section>


    </main>


</div>

<script src="assets/js/chatbot.js?v=<?= @filemtime(__DIR__ . '/assets/js/chatbot.js') ?>"></script>
<script src="assets/js/tableau_citoyen.js?v=<?= @filemtime(__DIR__ . '/assets/js/tableau_citoyen.js') ?>"></script>

<!-- ASSISTANT IA : fenêtre flottante au-dessus du bouton.
     Hors de <main> : l'animation d'apparition de <main> fausserait sa position fixe. (08-chatbot.css, chatbot.js, click_chatbot.js) -->
<section id="chatbot" class="chatbot-hidden" role="dialog" aria-labelledby="chatbot-titre" aria-hidden="true"
         data-user="<?= htmlspecialchars((string)$citoyenId) ?>">
    <header id="chatbot-header">
        <span class="chatbot-avatar"><?= icone('assistant') ?></span>
        <div class="chatbot-identite">
            <h2 id="chatbot-titre">Assistant de la mairie</h2>
            <p><span class="chatbot-pastille"></span> En ligne · répond en quelques secondes</p>
        </div>
        <button type="button" id="chatbot-fermer" aria-label="Fermer l’assistant"><?= icone('fermer') ?></button>
    </header>

    <div id="chat-window" role="log" aria-live="polite" aria-label="Conversation avec l’assistant">
        <!-- Accueil : retiré dès le premier message (chatbot.js) -->
        <div class="message bot-message">
            Bonjour <?= htmlspecialchars($citoyen['prenom_citoyen'] ?? '', ENT_QUOTES, 'UTF-8') ?> ! Je peux répondre à vos questions sur la mairie
            et préparer une demande pour vous. Que puis-je faire ?
        </div>
        <div id="chat-suggestions" class="chat-suggestions">
            <button type="button" data-suggestion="Quels sont les horaires de la mairie ?">Horaires de la mairie</button>
            <button type="button" data-suggestion="Comment inscrire mon enfant à la cantine ?">Inscription à la cantine</button>
            <button type="button" data-suggestion="Je veux signaler un lampadaire en panne.">Signaler un problème</button>
            <button type="button" data-suggestion="Comment obtenir un acte de naissance ?">Acte de naissance</button>
        </div>
    </div>

    <div id="ia-loading" style="display:none;" aria-live="polite">
        <span class="points-attente"><span></span><span></span><span></span></span>
        L’assistant rédige sa réponse…
    </div>

    <div id="chat-input">
        <label for="message" class="sr-only">Votre message</label>
        <input type="text" id="message" maxlength="500" autocomplete="off"
               placeholder="Écrivez dans votre langue… / Write in any language…" dir="auto">
        <button id="send" type="button" aria-label="Envoyer le message"><?= icone('envoyer') ?></button>
    </div>
</section>

<!-- BOUTON FLOTTANT IA : bulle pour ouvrir, croix quand la fenêtre est ouverte -->
<button id="ia-toggle-btn" class="ia-floating-btn" type="button" aria-controls="chatbot" aria-expanded="false">
    <!-- Tête de robot animée (08-chatbot.css) : on comprend tout de suite que c'est l'IA -->
    <span class="icone-ouvrir">
        <svg class="robot" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <g class="robot-antenne"><path d="M12 8V4"/><circle cx="12" cy="3" r="1" fill="currentColor"/></g>
            <rect x="4" y="8" width="16" height="12" rx="3"/>
            <path d="M2 14h2"/><path d="M20 14h2"/>
            <g class="robot-yeux"><path d="M9 13v2"/><path d="M15 13v2"/></g>
        </svg>
    </span>
    <span class="icone-fermer"><?= icone('fermer') ?></span>
</button>

<script src="assets/js/click_chatbot.js?v=<?= @filemtime(__DIR__ . '/assets/js/click_chatbot.js') ?>"></script>


<?= piedDePage() ?>

</body>
</html>
