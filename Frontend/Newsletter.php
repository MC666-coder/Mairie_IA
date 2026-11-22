<?php
// Sécurisation cookies (cohérence globale portail)
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();

require_once __DIR__ . '/../Backend/Config/app.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

// Token CSRF pour le formulaire newsletter
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Messages d'erreur renvoyés par traitement_newletter.php (?newsletter_error=...)
   « Déjà inscrit » n'existe volontairement pas : on afficherait sinon à n'importe qui
   si une adresse est abonnée (le traitement répond « inscription enregistrée » dans les deux cas). */
const ERREURS_NEWSLETTER = [
    'csrf'      => 'La page a expiré. Vérifiez vos informations puis réessayez.',
    'too_fast'  => 'Vous venez de faire une demande. Patientez quelques secondes avant de réessayer.',
    'nom'       => 'Indiquez votre nom (50 caractères au maximum).',
    'email'     => 'L’adresse e-mail n’est pas valide. Exemple : prenom.nom@exemple.fr',
];
$erreur  = ERREURS_NEWSLETTER[$_GET['newsletter_error'] ?? ''] ?? (isset($_GET['newsletter_error']) ? 'Une erreur est survenue. Réessayez.' : '');
$inscrit = ($_GET['inscription'] ?? '') === 'ok';

// Saisie conservée après une erreur (déposée par le traitement, affichée une seule fois)
$saisie = array_merge(['nom' => '', 'email' => ''], (array) ($_SESSION['newsletter_saisie'] ?? []));
unset($_SESSION['newsletter_saisie']);

$commune = htmlspecialchars(NOM_COMMUNE, ENT_QUOTES, 'UTF-8');

$avantages = [
    ['icone' => 'megaphone',  'titre' => 'Les actualités',    'texte' => 'Décisions du conseil, projets et nouveautés de la commune.'],
    ['icone' => 'calendrier', 'titre' => 'Les événements',    'texte' => 'Marchés, fêtes, concerts et rendez-vous à ne pas manquer.'],
    ['icone' => 'alerte',     'titre' => 'Les infos pratiques', 'texte' => 'Travaux, coupures, fermetures exceptionnelles de la mairie.'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter municipale - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- CSP stricte -->
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

    <?= enTetePublic('newsletter') ?>

    <main id="content_portail_citoyen" class="page-detail">

        <!-- BANDEAU D'INSCRIPTION (dégradé bleu nuit) -->
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-left text-white shadow-lg sm:p-10"
                 aria-labelledby="titre-newsletter">
            <p class="m-0 inline-flex items-center gap-2 text-sm font-medium text-blue-100">
                <?= icone('courrier') ?> Newsletter municipale
            </p>
            <h1 id="titre-newsletter" class="m-0 mt-2 max-w-2xl text-2xl font-extrabold tracking-tight text-white sm:text-4xl">
                Ne manquez rien de la vie de <?= $commune ?>
            </h1>
            <p class="m-0 mt-3 max-w-2xl text-sm text-blue-100 sm:text-base">
                Un e-mail de temps en temps, uniquement sur la vie de la commune.
            </p>

            <?php if ($inscrit): ?>
                <!-- Inscription enregistrée -->
                <div class="mt-6 flex max-w-2xl items-start gap-3 rounded-2xl bg-white px-5 py-4 text-left text-slate-800 shadow-md" role="status">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-green-100 text-lg text-green-700"><?= icone('valide') ?></span>
                    <div>
                        <p class="m-0 font-bold text-green-800">Merci, votre inscription est enregistrée.</p>
                        <p class="m-0 mt-0.5 text-sm text-slate-600">
                            Vous recevrez les prochaines informations de la mairie à l’adresse indiquée.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($erreur !== ''): ?>
                    <div class="mt-6 flex max-w-2xl items-start gap-2 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-600/20" role="alert">
                        <?= icone('croix', 'mt-0.5') ?> <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form id="newsletter_form" action="traitement_newletter.php" method="post" autocomplete="on"
                      class="mt-6 flex max-w-3xl flex-col gap-2 sm:flex-row">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="nom_complet" class="sr-only">Votre nom</label>
                    <input type="text" id="nom_complet" name="nom_complet" required maxlength="50" autocomplete="name"
                           value="<?= htmlspecialchars((string) $saisie['nom'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Votre nom"
                           class="min-w-0 flex-1 rounded-full border-0 bg-white px-5 py-3.5 text-sm text-slate-800 shadow-md outline-none ring-4 ring-white/20 placeholder:text-slate-400 focus:ring-white/50 sm:text-base">

                    <label for="email" class="sr-only">Votre adresse e-mail</label>
                    <input type="email" id="email" name="email" required maxlength="150" autocomplete="email"
                           value="<?= htmlspecialchars((string) $saisie['email'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Votre adresse e-mail"
                           class="min-w-0 flex-[1.4] rounded-full border-0 bg-white px-5 py-3.5 text-sm text-slate-800 shadow-md outline-none ring-4 ring-white/20 placeholder:text-slate-400 focus:ring-white/50 sm:text-base">

                    <button type="submit"
                            class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full border-0 bg-white px-6 py-3.5 text-sm font-bold text-[#003b8e] shadow-md transition hover:bg-blue-50 sm:text-base">
                        <?= icone('envoyer') ?> S’abonner
                    </button>
                </form>
            <?php endif; ?>

            <p class="m-0 mt-4 flex max-w-2xl items-start gap-2 text-xs text-blue-100">
                <?= icone('cadenas', 'mt-0.5 shrink-0') ?>
                <span>
                    Votre nom et votre adresse servent uniquement à vous envoyer la newsletter de la mairie.
                    Ils ne sont jamais transmis à d’autres organismes. Pour vous désabonner à tout moment,
                    <a href="Contacts.php" class="font-semibold text-white underline">contactez la mairie</a>.
                </span>
            </p>
        </section>

        <!-- CE QUE VOUS RECEVREZ -->
        <h2 class="m-0 mt-8 mb-4 text-left text-lg font-bold text-slate-900">Ce que vous recevrez</h2>
        <ul class="m-0 grid list-none gap-4 p-0 text-left sm:grid-cols-3">
            <?php foreach ($avantages as $a): ?>
                <li class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                    <span class="grid size-11 place-items-center rounded-2xl bg-[#e6eef9] text-xl text-[#003b8e]"><?= icone($a['icone']) ?></span>
                    <p class="m-0 mt-3 text-base font-bold text-slate-900"><?= $a['titre'] ?></p>
                    <p class="m-0 mt-1 text-sm text-slate-600"><?= $a['texte'] ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>

</div>

<?= piedDePage() ?>

</body>
</html>
