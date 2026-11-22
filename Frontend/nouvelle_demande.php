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
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/carte_profil.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/demande_citoyen.php'; // types, priorités, icônes des services



// Vérification connexion citoyen
// Pas connecté ou session expirée : retour à la connexion avec un message
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';
exigerCitoyen();

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
   Récupération sécurisée des services
---------------------------------------- */

$services = $pdo->prepare("SELECT id, nom_service FROM services ORDER BY nom_service ASC");
$services->execute();
$listeServices = $services->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------
   Erreur du traitement : message rouge et saisie conservée
   (déposés en session par traitement_nouvelle_demande.php, affichés une seule fois)
---------------------------------------- */

$erreurDemande = (string) ($_SESSION['erreur_demande'] ?? '');
$saisie = array_merge(
    ['services_id' => '', 'type' => '', 'message' => '', 'priorite' => 'normale'],
    array_map('strval', (array) ($_SESSION['saisie_demande'] ?? []))
);
unset($_SESSION['erreur_demande'], $_SESSION['saisie_demande']);

// Arrivée depuis la page Services (« Faire une demande ») : service déjà choisi,
// seulement s'il existe dans la liste (sinon on l'ignore)
$serviceDemande = (string) ($_GET['service'] ?? '');
if ($saisie['services_id'] === '' && ctype_digit($serviceDemande)
    && in_array($serviceDemande, array_map('strval', array_column($listeServices, 'id')), true)) {
    $saisie['services_id'] = $serviceDemande;
}

/* ---------------------------------------
   Génération du token CSRF
---------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une demande - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/carte_profil.css?v=<?= @filemtime(__DIR__ . '/assets/css/carte_profil.css') ?>">
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

    <?= enTeteCitoyen('demande', $citoyen, (int) $citoyenId) ?>

    <main id="content_portail_citoyen" class="page-detail page-nouvelle-demande">

        <!-- Retour au tableau de bord -->
        <a href="Portail_citoyen.php"
           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-600 no-underline transition hover:bg-white hover:text-[#003b8e]">
            <?= icone('fleche-gauche') ?> Mes demandes
        </a>

        <!-- En-tête (dégradé bleu nuit) -->
        <div class="mt-3 mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-8">
            <p class="m-0 text-sm font-medium text-blue-100">Espace citoyen</p>
            <h1 class="m-0 mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Nouvelle demande</h1>
            <p class="m-0 mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">
                Choisissez le service, le type de demande, puis décrivez votre besoin.
                Un agent de la mairie vous répondra dans votre espace.
            </p>
        </div>

        <?php if ($erreurDemande !== ''): ?>
            <div id="erreur-demande" class="mb-6 flex items-start gap-2 rounded-2xl bg-red-50 px-4 py-3 text-left text-sm font-medium text-red-800 ring-1 ring-red-600/20" role="alert">
                <?= icone('croix', 'mt-0.5') ?> <?= htmlspecialchars($erreurDemande, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form id="form-nouvelle-demande" action="traitement_nouvelle_demande.php" method="POST" autocomplete="off"
              class="grid items-start gap-6 text-left lg:grid-cols-[minmax(0,1fr)_340px]">

            <!-- CSRF -->
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <!-- COLONNE PRINCIPALE : les choix -->
            <div class="flex min-w-0 flex-col gap-8 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-6">

                <!-- 1. Service -->
                <fieldset class="m-0 min-w-0 border-0 p-0">
                    <legend class="mb-3 flex items-center gap-2 p-0 text-lg font-bold text-slate-900">
                        <span class="grid size-7 place-items-center rounded-full bg-[#e6eef9] text-sm text-[#003b8e]">1</span>
                        Service concerné
                    </legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                        <?php foreach ($listeServices as $s): ?>
                            <label class="tuile-choix flex cursor-pointer flex-col items-start gap-2 rounded-2xl bg-slate-50 p-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-900/5 transition hover:bg-white hover:ring-[#003b8e]/30 has-[:checked]:bg-[#e6eef9] has-[:checked]:text-[#003b8e] has-[:checked]:ring-2 has-[:checked]:ring-[#003b8e] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#003b8e]">
                                <input type="radio" name="services_id" value="<?= (int) $s['id'] ?>" class="sr-only" required
                                       data-libelle="<?= htmlspecialchars($s['nom_service'], ENT_QUOTES, 'UTF-8') ?>"
                                       <?= $saisie['services_id'] === (string) $s['id'] ? 'checked' : '' ?>>
                                <span class="tuile-icone grid size-9 place-items-center rounded-xl bg-white text-lg text-[#003b8e] shadow-sm ring-1 ring-slate-900/5">
                                    <?= icone(iconeService($s['nom_service'])) ?>
                                </span>
                                <span class="leading-tight"><?= htmlspecialchars($s['nom_service'], ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- 2. Type de demande -->
                <fieldset class="m-0 min-w-0 border-0 p-0">
                    <legend class="mb-3 flex items-center gap-2 p-0 text-lg font-bold text-slate-900">
                        <span class="grid size-7 place-items-center rounded-full bg-[#e6eef9] text-sm text-[#003b8e]">2</span>
                        Type de demande
                    </legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <?php foreach (TYPES_DEMANDE as $code => $t): ?>
                            <label class="tuile-choix flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-900/5 transition hover:bg-white hover:ring-[#003b8e]/30 has-[:checked]:bg-[#e6eef9] has-[:checked]:ring-2 has-[:checked]:ring-[#003b8e] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#003b8e]">
                                <input type="radio" name="type" value="<?= $code ?>" class="sr-only" required
                                       data-libelle="<?= $t['libelle'] ?>"
                                       <?= $saisie['type'] === $code ? 'checked' : '' ?>>
                                <span class="tuile-icone grid size-9 shrink-0 place-items-center rounded-xl bg-white text-lg text-[#003b8e] shadow-sm ring-1 ring-slate-900/5">
                                    <?= icone($t['icone']) ?>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-800"><?= $t['libelle'] ?></span>
                                    <span class="block text-xs text-slate-500"><?= $t['aide'] ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- 3. Message -->
                <div>
                    <label for="message" class="mb-3 flex items-center gap-2 text-lg font-bold text-slate-900">
                        <span class="grid size-7 place-items-center rounded-full bg-[#e6eef9] text-sm text-[#003b8e]">3</span>
                        Votre message
                    </label>
                    <textarea id="message" name="message" rows="6" required minlength="<?= MESSAGE_MIN ?>" maxlength="<?= MESSAGE_MAX ?>" dir="auto"
                              aria-describedby="compteur-message"
                              class="w-full resize-y rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#003b8e] focus:ring-4 focus:ring-[#003b8e]/15"
                              placeholder="Ex. : Un lampadaire ne fonctionne plus devant le 12 rue des Lilas depuis lundi."><?= htmlspecialchars($saisie['message'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="mt-1 flex flex-wrap justify-between gap-2 text-xs text-slate-500">
                        <span>Au moins <?= MESSAGE_MIN ?> caractères. Précisez le lieu et la date si besoin.</span>
                        <span id="compteur-message" data-max="<?= MESSAGE_MAX ?>">0 / <?= MESSAGE_MAX ?></span>
                    </div>
                </div>

                <!-- 4. Priorité -->
                <fieldset class="m-0 min-w-0 border-0 p-0">
                    <legend class="mb-3 flex items-center gap-2 p-0 text-lg font-bold text-slate-900">
                        <span class="grid size-7 place-items-center rounded-full bg-[#e6eef9] text-sm text-[#003b8e]">4</span>
                        Priorité
                    </legend>
                    <!-- Une couleur par priorité : gris, bleu, orange (le rouge reste pour l'urgence côté agent) -->
                    <div class="grid gap-2 sm:grid-cols-3">
                        <?php foreach (PRIORITES_DEMANDE as $code => $p): ?>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-3 text-slate-700 ring-1 ring-slate-900/5 transition hover:bg-white has-[:checked]:ring-2 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#003b8e] <?= $p['choisi'] ?>">
                                <input type="radio" name="priorite" value="<?= $code ?>" class="sr-only" required
                                       data-libelle="<?= $p['libelle'] ?>"
                                       <?= $saisie['priorite'] === $code ? 'checked' : '' ?>>
                                <span class="tuile-icone mt-0.5 grid size-5 shrink-0 place-items-center">
                                    <?= icone('cercle', 'fill-current ' . $p['point']) ?>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold"><?= $p['libelle'] ?></span>
                                    <span class="block text-xs text-slate-500"><?= $p['aide'] ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>

            <!-- COLONNE DE DROITE : récapitulatif en direct (nouvelle_demande.js) -->
            <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24" aria-label="Récapitulatif de la demande">
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                    <h2 class="m-0 text-sm font-bold uppercase tracking-wider text-slate-500">Récapitulatif</h2>
                    <dl class="m-0 mt-4 grid gap-3 text-sm">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-slate-400" data-recap-icone="services_id"><?= icone('mairie') ?></span>
                            <div><dt class="text-xs text-slate-500">Service</dt>
                                <dd class="m-0 font-medium text-slate-800" data-recap="services_id" data-vide="À choisir">À choisir</dd></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-slate-400" data-recap-icone="type"><?= icone('conversation') ?></span>
                            <div><dt class="text-xs text-slate-500">Type</dt>
                                <dd class="m-0 font-medium text-slate-800" data-recap="type" data-vide="À choisir">À choisir</dd></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-slate-400" data-recap-icone="priorite"><?= icone('alerte') ?></span>
                            <div><dt class="text-xs text-slate-500">Priorité</dt>
                                <dd class="m-0 font-medium text-slate-800" data-recap="priorite" data-vide="À choisir">À choisir</dd></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-slate-400"><?= icone('stylo') ?></span>
                            <div class="min-w-0"><dt class="text-xs text-slate-500">Message</dt>
                                <dd class="m-0 line-clamp-4 font-medium break-words text-slate-800" data-recap="message" data-vide="Pas encore écrit">Pas encore écrit</dd></div>
                        </div>
                    </dl>

                    <!-- Champs restants (rempli par le JS) -->
                    <p id="recap-manquant" class="m-0 mt-4 rounded-2xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-600/20" hidden></p>

                    <button type="submit" id="envoyer-demande"
                            class="mt-5 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border-0 bg-gradient-to-r from-[#003b8e] to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60">
                        <?= icone('envoyer') ?> Envoyer la demande
                    </button>
                    <p class="m-0 mt-3 text-center text-xs text-slate-500">
                        Vous suivrez son avancement dans « Mes demandes ».
                    </p>
                </div>
            </aside>
        </form>
    </main>

    <script src="assets/js/nouvelle_demande.js?v=<?= @filemtime(__DIR__ . '/assets/js/nouvelle_demande.js') ?>"></script>


</div>

<?= piedDePage() ?>

</body>
</html>
