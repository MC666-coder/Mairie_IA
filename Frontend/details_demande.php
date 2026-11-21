<?php
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/carte_profil.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/tableau_citoyen.php'; // pastilles, frise, libellés
require_once __DIR__ . '/../Backend/Middleware/auth.php'; // jeton CSRF de la messagerie

/**
 * Sécurisation renforcée de la session
 */


// Vérifier que l'utilisateur est connecté ET bien un citoyen (et pas un agent)
// Pas connecté ou session expirée : retour à la connexion, puis retour ici après reconnexion
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';
exigerCitoyen();

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
    // Pas de page vierge : retour au tableau de bord avec un message
    header('Location: Portail_citoyen.php?erreur=demande-introuvable');
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
    // Demande inexistante ou appartenant à un autre citoyen : même message (anti-IDOR)
    header('Location: Portail_citoyen.php?erreur=demande-introuvable');
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
    <link rel="stylesheet" href="assets/css/carte_profil.css?v=<?= @filemtime(__DIR__ . '/assets/css/carte_profil.css') ?>">
    <?= feuillesGabarit() ?>
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

    <?= enTeteCitoyen('tableau', $citoyen, (int) $citoyenId) ?>

    <!-- CONTENU PRINCIPAL -->
    <main id="content_portail_citoyen" class="page-detail">

        <!-- Retour au tableau de bord -->
        <a href="Portail_citoyen.php"
           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-600 no-underline transition hover:bg-white hover:text-[#003b8e]">
            <?= icone('fleche-gauche') ?> Mes demandes
        </a>

        <!-- En-tête de la demande : bandeau en dégradé bleu nuit (titre, statut, priorité, avancement) -->
        <div class="relative mt-3 mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-xl shadow-indigo-900/20 sm:p-8">
            <!-- reflet lumineux décoratif -->
            <span class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="m-0 text-sm font-medium text-blue-200">Demande N° <?= (int) $id ?></p>
                    <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                        <?= htmlspecialchars($demande['type'] ?? 'Demande', ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= pastille(LIBELLES_STATUT[$demande['statut']] ?? (string) $demande['statut'],
                                 'bg-white/15 text-white ring-white/30 backdrop-blur') ?>
                    <?php if ($demande['priorite'] === 'urgente'): ?>
                        <?= pastille('Priorité urgente', 'bg-red-500 text-white ring-red-300') ?>
                    <?php elseif ($demande['priorite'] === 'haute'): ?>
                        <?= pastille('Priorité haute', 'bg-amber-400 text-amber-950 ring-amber-200') ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="relative mt-2">
                <?= friseAvancement((string) $demande['statut'], true) ?>
            </div>
        </div>

        <div class="grid items-start gap-6 text-left lg:grid-cols-[minmax(0,1fr)_340px]">

            <!-- COLONNE PRINCIPALE : échanges avec la mairie (messagerie_citoyen.js, Backend/citoyen/messagerie.php) -->
            <section id="messagerie-citoyen" data-demande-id="<?= (int) $id ?>" aria-labelledby="titre-messagerie"
                     class="flex min-w-0 flex-col gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                <div>
                    <h2 id="titre-messagerie" class="m-0 flex items-center gap-2 text-lg font-bold text-indigo-950">
                        <?= icone('conversation') ?> Échanges avec la mairie
                    </h2>
                    <p class="m-0 mt-1 text-sm text-slate-500">
                        L’agent qui traite votre demande vous écrit ici s’il a besoin d’un document
                        ou pour vous envoyer une attestation. Vous pourrez alors lui répondre.
                    </p>
                </div>

                <div id="fil-messages" class="fil-messages" role="log" aria-live="polite" aria-label="Conversation avec la mairie">
                    <p class="fil-vide">Chargement de la conversation…</p>
                </div>

                <!-- Affiché tant que le citoyen ne peut pas répondre -->
                <p id="reponse-indisponible" hidden class="note-attente"></p>

                <!-- Réponse du citoyen : document et/ou court message, après un message de l'agent -->
                <form id="form-reponse-citoyen" hidden class="grid gap-3 rounded-2xl bg-indigo-50/60 p-4 ring-1 ring-indigo-100">
                    <label for="texte-message" class="text-sm font-semibold text-slate-800">Votre réponse</label>
                    <textarea id="texte-message" name="message" rows="3" maxlength="500" dir="auto"
                              class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15"
                              placeholder="Ex. : Voici mon justificatif de domicile."></textarea>

                    <label for="fichier-citoyen" class="text-sm font-semibold text-slate-800">Document à joindre (facultatif)</label>
                    <input type="file" id="fichier-citoyen" name="fichier" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx"
                           aria-describedby="aide-fichier"
                           class="block w-full rounded-xl border border-dashed border-slate-300 bg-white p-2 text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700">
                    <p id="aide-fichier" class="m-0 text-xs text-slate-500">PDF, JPG, PNG, DOCX ou XLSX.</p>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span id="compteur-caracteres" class="text-xs text-slate-500">0 / 500</span>
                        <button type="submit" id="envoyer-message">
                            <?= icone('envoyer') ?> Envoyer à la mairie
                        </button>
                    </div>
                    <p id="messagerie-statut" class="messagerie-statut" role="status"></p>
                </form>
            </section>

            <!-- COLONNE DE DROITE : récapitulatif (reste visible en défilant sur grand écran) -->
            <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24" aria-label="Récapitulatif de la demande">

                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                    <h2 class="m-0 text-sm font-bold uppercase tracking-wider text-indigo-900/70">Votre demande</h2>
                    <p class="m-0 mt-3 text-sm whitespace-pre-line text-slate-700" dir="auto"><?= htmlspecialchars($demande['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <dl class="m-0 mt-4 grid gap-3 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-indigo-500"><?= icone('horloge') ?></span>
                            <div><dt class="text-xs text-slate-500">Déposée le</dt>
                                <dd class="m-0 font-medium text-slate-800"><?= $demande['created_at'] ? date('d/m/Y à H\hi', strtotime($demande['created_at'])) : 'Date inconnue' ?></dd></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-indigo-500"><?= icone('mairie') ?></span>
                            <div><dt class="text-xs text-slate-500">Service</dt>
                                <dd class="m-0 font-medium text-slate-800"><?= htmlspecialchars($demande['nom_service'] ?? 'À déterminer par la mairie', ENT_QUOTES, 'UTF-8') ?></dd>
                                <?php if (!empty($demande['description_service'])): ?>
                                    <dd class="m-0 mt-0.5 text-xs text-slate-500"><?= htmlspecialchars($demande['description_service'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <?php endif; ?></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-indigo-500"><?= icone('alerte') ?></span>
                            <div><dt class="text-xs text-slate-500">Priorité</dt>
                                <dd class="m-0 font-medium text-slate-800"><?= htmlspecialchars(LIBELLES_PRIORITE[$demande['priorite']] ?? ($demande['priorite'] ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                        </div>
                    </dl>
                </div>

                <!-- Documents (remplis par messagerie_citoyen.js) -->
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                    <h2 class="m-0 flex items-center justify-between text-sm font-bold uppercase tracking-wider text-indigo-900/70">
                        <span class="inline-flex items-center gap-2"><?= icone('boite-reception') ?> Documents de la mairie</span>
                        <span id="compteur-docs-mairie" class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">0</span>
                    </h2>
                    <ul id="liste-docs-mairie" class="liste-documents mt-3"></ul>

                    <h2 class="m-0 mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-sm font-bold uppercase tracking-wider text-indigo-900/70">
                        <span class="inline-flex items-center gap-2"><?= icone('envoyer') ?> Vos documents</span>
                        <span id="compteur-docs-citoyen" class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">0</span>
                    </h2>
                    <ul id="liste-docs-citoyen" class="liste-documents mt-3"></ul>
                </div>
            </aside>
        </div>
    </main>


    <?= piedDePage() ?>

</div>

<script src="assets/js/icones.js?v=<?= @filemtime(__DIR__ . '/assets/js/icones.js') ?>"></script>
<script src="assets/js/messagerie_citoyen.js?v=<?= @filemtime(__DIR__ . '/assets/js/messagerie_citoyen.js') ?>"></script>
</body>
</html>
