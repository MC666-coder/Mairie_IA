<?php
/* =========================================================
   JEU D'ÉNIGMES DU PATRIMOINE (espace citoyen)
   ---------------------------------------------------------
   La page accueille l'application React (Frontend/jeu/src, construite
   par « npm run jeu » dans assets/jeu/jeu.js). Tout le jeu passe par
   l'API Backend/jeu/api.php : les réponses restent sur le serveur.
   ========================================================= */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php';       // jeton_csrf()
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';

// Pas connecté ou session expirée : connexion, puis retour au jeu
$citoyenId = exigerCitoyen();

$stmt = $pdo->prepare("SELECT id, nom_citoyen, prenom_citoyen FROM citoyens WHERE id = ? LIMIT 1");
$stmt->execute([$citoyenId]);
$citoyen = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$citoyen) {
    header('Location: logout.php');
    exit;
}

$versionJeu = @filemtime(__DIR__ . '/assets/jeu/jeu.js');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Énigmes du patrimoine - Commune de L’Aiguillon-sur-Vie</title>
    <!-- Jeton anti-CSRF lu par l'application (en-tête X-CSRF-Token) -->
    <meta name="csrf-token" content="<?= htmlspecialchars(jeton_csrf(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   connect-src 'self';
                   object-src 'none';
                   base-uri 'self';">
</head>

<body>
<div class="page-wrapper">

    <?= enTeteCitoyen('jeu', $citoyen, $citoyenId) ?>

    <main id="content_portail_citoyen" class="page-detail">

        <!-- En-tête (dégradé bleu nuit) -->
        <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
            <p class="m-0 text-sm font-medium text-blue-100">Jeu du patrimoine</p>
            <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Les énigmes de la commune</h1>
            <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                Devinettes, photos à reconnaître et dates à retrouver : chaque énigme résolue débloque la suivante.
                Gagnez des points et grimpez au classement !
            </p>
        </div>

        <!-- Application React (assets/jeu/jeu.js) -->
        <div id="jeu-enigmes">
            <noscript>
                <p class="rounded-2xl bg-amber-50 p-4 text-amber-900">Le jeu a besoin de JavaScript : activez-le dans votre navigateur pour jouer.</p>
            </noscript>
        </div>
    </main>

</div>

<?= piedDePage() ?>

<script type="module" src="assets/jeu/jeu.js?v=<?= $versionJeu ?>"></script>
</body>
</html>
