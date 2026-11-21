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
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
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
    <?= feuillesGabarit() ?>
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

    <?= enTetePublic('agenda') ?>

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


</div>

<?= piedDePage() ?>

</body>
</html>
