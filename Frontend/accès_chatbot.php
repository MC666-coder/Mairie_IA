<?php
session_start();
require_once __DIR__ . '/../Backend/Fonctions/icones.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';

// L'assistant est dans l'espace citoyen : ?assistant=ouvert ouvre le chat
// automatiquement (assets/js/click_chatbot.js)
const PAGE_ASSISTANT = 'Portail_citoyen.php?assistant=ouvert';

// Déjà connecté → directement dans son espace, chat ouvert
if (isset($_SESSION['citoyen_id'])) {
    header('Location: ' . PAGE_ASSISTANT);
    exit();
}

// Après la connexion, même destination
// (page de retour vérifiée par liste blanche dans connexion_citoyen.php)
$lienConnexion = 'connexion_citoyen.php?retour=' . rawurlencode(PAGE_ASSISTANT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant municipal - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>
</head>

<body>
    <div class="page-wrapper">

        <?= enTetePublic('') ?>

        <main id="content_portail_citoyen" class="page-detail">

            <a href="index.php"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold text-slate-600 no-underline transition hover:bg-white hover:text-[#003b8e]">
                <?= icone('fleche-gauche') ?> Accueil
            </a>

            <div class="mt-3 grid overflow-hidden rounded-3xl bg-white text-left shadow-lg ring-1 ring-slate-900/5 lg:grid-cols-2">

                <!-- APERÇU : exemple d'échange avec l'assistant (purement illustratif) -->
                <div class="flex flex-col justify-center gap-3 bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 sm:p-10" aria-hidden="true">
                    <p class="m-0 mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-blue-100">
                        <?= icone('conversation') ?> Exemple d’échange
                    </p>
                    <div class="max-w-[85%] self-end rounded-2xl rounded-tr-md bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm">
                        Comment inscrire mon fils à la cantine ?
                    </div>
                    <div class="flex max-w-[90%] items-end gap-2 self-start">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white/15 text-white"><?= icone('assistant') ?></span>
                        <div class="rounded-2xl rounded-tl-md bg-white/15 px-4 py-2.5 text-sm text-white ring-1 ring-white/20">
                            C’est le service <strong>Scolaire et jeunesse</strong>. Je peux préparer la demande pour vous : il vous suffira de la vérifier avant l’envoi.
                        </div>
                    </div>
                    <div class="max-w-[85%] self-end rounded-2xl rounded-tr-md bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm">
                        Oui, merci !
                    </div>
                    <div class="flex items-center gap-2 self-start text-xs text-blue-100">
                        <span class="flex gap-1">
                            <span class="size-1.5 animate-bounce rounded-full bg-blue-100"></span>
                            <span class="size-1.5 animate-bounce rounded-full bg-blue-100 [animation-delay:150ms]"></span>
                            <span class="size-1.5 animate-bounce rounded-full bg-blue-100 [animation-delay:300ms]"></span>
                        </span>
                        L’assistant écrit…
                    </div>
                </div>

                <!-- ACCÈS : connexion ou inscription (en premier sur mobile, pour voir les boutons tout de suite) -->
                <div class="order-first flex flex-col justify-center gap-4 p-6 sm:p-10 lg:order-none">
                    <span class="grid size-12 place-items-center rounded-2xl bg-[#e6eef9] text-2xl text-[#003b8e]">
                        <?= icone('assistant') ?>
                    </span>
                    <div>
                        <h1 class="m-0 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Votre assistant municipal</h1>
                        <p class="m-0 mt-2 text-slate-600">
                            Posez vos questions à tout moment : l’assistant vous oriente vers le bon service
                            et vous aide à préparer vos demandes.
                        </p>
                    </div>

                    <ul class="m-0 grid list-none gap-2 p-0 text-sm text-slate-700">
                        <li class="flex items-center gap-2"><span class="text-[#003b8e]"><?= icone('horloge') ?></span> Disponible 24h/24, même quand la mairie est fermée</li>
                        <li class="flex items-center gap-2"><span class="text-[#003b8e]"><?= icone('valide') ?></span> Vous vérifiez chaque demande avant son envoi</li>
                        <li class="flex items-center gap-2"><span class="text-[#003b8e]"><?= icone('bouclier') ?></span> Aucune conversation n’est conservée</li>
                    </ul>

                    <p class="m-0 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-900/5">
                        <?= icone('cadenas', 'mr-1 text-slate-400') ?>
                        L’assistant est réservé aux citoyens connectés : il peut ainsi créer vos demandes dans votre espace.
                    </p>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="<?= htmlspecialchars($lienConnexion, ENT_QUOTES, 'UTF-8') ?>"
                           class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#003b8e] to-indigo-600 px-5 py-3 text-sm font-semibold text-white no-underline shadow-md transition hover:brightness-110">
                            <?= icone('connexion') ?> Se connecter
                        </a>
                        <a href="inscriptions_citoyen.php"
                           class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-[#003b8e] no-underline ring-1 ring-[#003b8e]/25 transition hover:bg-[#e6eef9]">
                            <?= icone('inscription') ?> Créer un compte
                        </a>
                    </div>
                    <p class="m-0 text-xs text-slate-500">L’inscription est gratuite et prend moins d’une minute.</p>
                </div>
            </div>
        </main>

    </div>

    <?= piedDePage() ?>

</body>
</html>
