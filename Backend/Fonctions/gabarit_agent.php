<?php
/* =========================================================
   GABARIT DU PORTAIL AGENT (style « ardoise et émeraude »)
   ---------------------------------------------------------
   Indépendant du site citoyen : feuille Agent-prenium/assets/css/agent.css
   (Tailwind, compilée par « npm run css » dans Agent-prenium).

   Usage dans une page agent :
       <?= feuillesAgent() ?>                         dans <head>
       <?= debutPageAgent('tableau', $agent) ?>       juste après <body>
           … contenu de la page …
       <?= finPageAgent() ?>                          avant </body>
   ========================================================= */

require_once __DIR__ . '/icones.php';

/** Liens de la barre latérale : clé => [adresse, texte, icône, réservé manager/admin]. */
const MENU_AGENT = [
    'tableau'   => ['dashboard_agent.php',   'Tableau de bord',       'tableau',         false],
    'demandes'  => ['gestion_demandes.php',  'Gestion des demandes',  'boite-reception', false],
    'agents'    => ['inscription_agent.php', 'Créer un agent',        'inscription',     true],
];

const NIVEAUX_AGENT = ['agent' => 'Agent', 'manager' => 'Manager', 'admin' => 'Administrateur'];

/** Feuille de style du portail agent (version = date du fichier, pour éviter l'ancien cache). */
function feuillesAgent(): string
{
    $fichier = __DIR__ . '/../../Agent-prenium/assets/css/agent.css';
    return '<link rel="stylesheet" href="assets/css/agent.css?v=' . @filemtime($fichier) . '">';
}

function eAgent(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/** « Dimanche 22 novembre 2026 » (heure de Paris, sans extension intl). */
function dateDuJourAgent(): string
{
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet',
              'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $d = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
    return ucfirst($jours[(int) $d->format('w')]) . ' ' . $d->format('j') . ' ' . $mois[(int) $d->format('n')] . ' ' . $d->format('Y');
}

/* ---------------------------------------------------------
   Statuts et priorités des demandes : libellés et couleurs des pastilles
   (partagés par le tableau de bord et la gestion des demandes)
--------------------------------------------------------- */
const STATUTS_AGENT = [
    'nouvelle'   => ['Nouvelle',   'bg-sky-50 text-sky-700 ring-sky-600/20'],
    'analyse_ia' => ['Analysée IA', 'bg-violet-50 text-violet-700 ring-violet-600/20'],
    'en_cours'   => ['En cours',   'bg-amber-50 text-amber-700 ring-amber-600/20'],
    'en_attente' => ['En attente', 'bg-orange-50 text-orange-700 ring-orange-600/20'],
    'resolue'    => ['Résolue',    'bg-marque-50 text-marque-700 ring-marque-600/20'],
    'cloturee'   => ['Clôturée',   'bg-encre-100 text-encre-600 ring-encre-500/20'],
];
const PRIORITES_AGENT = [
    'basse'   => ['Basse',   'text-encre-500'],
    'normale' => ['Normale', 'text-encre-700'],
    'haute'   => ['Haute',   'text-orange-600 font-semibold'],
    'urgente' => ['Urgente', 'text-red-600 font-bold'],
];

/** Pastille de statut */
function pastilleStatut(?string $statut): string
{
    [$texte, $couleurs] = STATUTS_AGENT[$statut] ?? [ucfirst((string) $statut), STATUTS_AGENT['cloturee'][1]];
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ' . $couleurs . '">' . eAgent($texte) . '</span>';
}

/** « 21/11/2026 à 14h05 » */
function dateAgent(?string $date): string
{
    return $date ? date('d/m/Y à H\hi', strtotime($date)) : '—';
}

/** Texte coloré de la priorité : « Priorité haute » */
function textePriorite(?string $priorite): string
{
    [$texte, $couleur] = PRIORITES_AGENT[$priorite] ?? [ucfirst((string) $priorite), 'text-encre-700'];
    return '<span class="' . $couleur . '">Priorité ' . eAgent(mb_strtolower($texte, 'UTF-8')) . '</span>';
}

/** Initiales de l'agent pour l'avatar : « Marie Durand » → « MD ». */
function initialesAgent(array $agent): string
{
    $p = mb_substr((string) ($agent['prenom_agent'] ?? ''), 0, 1, 'UTF-8');
    $n = mb_substr((string) ($agent['nom_agent'] ?? ''), 0, 1, 'UTF-8');
    return mb_strtoupper($p . $n, 'UTF-8') ?: 'AG';
}

/**
 * Barre latérale (fixe sur grand écran, tiroir sur mobile) + barre du haut mobile.
 * Ouvre <div> de contenu : à fermer avec finPageAgent().
 */
function debutPageAgent(string $actif, array $agent): string
{
    $estResponsable = in_array($_SESSION['niveau_acces'] ?? '', ['manager', 'admin'], true);

    $liens = '';
    foreach (MENU_AGENT as $cle => [$url, $texte, $icone, $reserve]) {
        if ($reserve && !$estResponsable) {
            continue; // visible seulement par les managers et admins (contrôle aussi fait côté serveur)
        }
        $courant = $cle === $actif;
        $liens .= '<a href="' . $url . '"' . ($courant ? ' aria-current="page"' : '')
            . ' class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition '
            . ($courant
                ? 'bg-marque-600 text-white shadow-sm shadow-marque-900/30'
                : 'text-encre-300 hover:bg-white/5 hover:text-white')
            . '">' . icone($icone, 'text-lg') . eAgent($texte) . '</a>';
    }

    $service = (string) ($agent['Nom_service'] ?? $agent['nom_service'] ?? '');
    $niveau  = NIVEAUX_AGENT[(string) ($agent['niveau_acces'] ?? '')] ?? 'Agent';
    $nom     = trim(($agent['prenom_agent'] ?? '') . ' ' . ($agent['nom_agent'] ?? ''));

    // Fonctions appelables dans le bloc de texte ci-dessous : {$icone('menu')}, {$e($texte)}
    $icone = 'icone';
    $e = 'eAgent';

    return <<<HTML
<!-- Fond sombre derrière le menu ouvert sur mobile -->
<div id="voile-menu-agent" class="fixed inset-0 z-30 bg-encre-950/50 backdrop-blur-sm lg:hidden" hidden></div>

<!-- BARRE LATÉRALE -->
<aside id="menu-agent"
       class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-encre-900 text-encre-200 transition-transform duration-300 lg:w-64 lg:translate-x-0"
       aria-label="Menu du portail agent">
    <div class="flex items-center gap-3 px-5 py-5">
        <span class="grid size-10 place-items-center rounded-xl bg-marque-500/15 text-xl text-marque-400">{$icone('mairie')}</span>
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wider text-encre-400">Mairie</p>
            <p class="truncate text-sm font-bold text-white">Espace agent</p>
        </div>
        <button type="button" id="fermer-menu-agent" class="ml-auto grid size-9 place-items-center rounded-lg text-encre-300 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Fermer le menu">{$icone('fermer', 'text-lg')}</button>
    </div>

    <nav class="flex flex-1 flex-col gap-1 px-3" aria-label="Navigation">
        <p class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-encre-500">Menu</p>
        {$liens}
    </nav>

    <!-- Profil connecté et déconnexion -->
    <div class="m-3 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
        <div class="flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-marque-600 text-sm font-bold text-white">{$e(initialesAgent($agent))}</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{$e($nom)}</p>
                <p class="truncate text-xs text-encre-400">{$e($niveau)}{$e($service !== '' ? ' · ' . $service : '')}</p>
            </div>
        </div>
        <a href="logout.php" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-white/5 px-3 py-2 text-sm font-medium text-encre-200 transition hover:bg-red-500/15 hover:text-red-300">
            {$icone('deconnexion')} Déconnexion
        </a>
    </div>
</aside>

<div class="min-h-screen lg:pl-64">
    <!-- Barre du haut sur mobile -->
    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-encre-200 bg-white/80 px-4 py-3 backdrop-blur lg:hidden">
        <button type="button" id="ouvrir-menu-agent" class="grid size-10 place-items-center rounded-xl text-encre-700 ring-1 ring-encre-200 hover:bg-encre-100" aria-label="Ouvrir le menu" aria-controls="menu-agent" aria-expanded="false">{$icone('menu', 'text-xl')}</button>
        <p class="text-sm font-bold text-encre-900">Espace agent</p>
        <span class="ml-auto grid size-9 place-items-center rounded-full bg-marque-600 text-xs font-bold text-white">{$e(initialesAgent($agent))}</span>
    </header>
HTML;
}

/** Ferme le contenu, ajoute l'assistant IA agent et les scripts du portail. */
function finPageAgent(bool $avecAssistant = true): string
{
    $v = fn (string $f) => @filemtime(__DIR__ . '/../../' . $f);
    $html = "</div>\n";

    if ($avecAssistant) {
        $html .= assistantAgent();
        $html .= '<script src="../Frontend/assets/js/icones.js?v=' . $v('Frontend/assets/js/icones.js') . '" defer></script>' . "\n";
        $html .= '<script src="../Frontend/IA/assistant.js?v=' . $v('Frontend/IA/assistant.js') . '" defer></script>' . "\n";
    }
    $html .= '<script src="assets/js/portail_agent.js?v=' . $v('Agent-prenium/assets/js/portail_agent.js') . '" defer></script>' . "\n";
    return $html;
}

/** Assistant IA agent : bouton « tête de robot » et fenêtre (ids lus par Frontend/IA/assistant.js). */
function assistantAgent(): string
{
    $icone = 'icone'; // appelable dans le bloc de texte : {$icone('fermer')}

    return <<<HTML
<!-- ASSISTANT IA AGENT -->
<button type="button" id="ia-agent-button" aria-label="Ouvrir l’assistant IA" aria-controls="ia-agent-window"
        class="bouton-robot group fixed right-6 bottom-6 z-50 grid size-14 cursor-pointer place-items-center rounded-full bg-gradient-to-br from-marque-500 to-marque-700 text-white shadow-lg shadow-marque-900/30 transition hover:scale-105">
    <span class="robot-tete grid">
        <svg class="robot" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <g class="robot-antenne"><path d="M12 8V4"/><circle cx="12" cy="3" r="1" fill="currentColor"/></g>
            <rect x="4" y="8" width="16" height="12" rx="3"/>
            <path d="M2 14h2"/><path d="M20 14h2"/>
            <g class="robot-yeux"><path d="M9 13v2"/><path d="M15 13v2"/></g>
        </svg>
    </span>
    <span class="pointer-events-none absolute right-full mr-3 rounded-xl group-aria-expanded:hidden bg-encre-900 px-3 py-2 text-xs font-semibold whitespace-nowrap text-white opacity-0 transition group-hover:opacity-100">Orienter une demande avec l’IA</span>
</button>

<section id="ia-agent-window" class="hidden fixed inset-0 z-50 flex flex-col overflow-hidden bg-white sm:inset-auto sm:right-6 sm:bottom-24 sm:h-[min(600px,calc(100vh-8rem))] sm:w-[400px] sm:rounded-3xl sm:shadow-2xl sm:ring-1 sm:ring-encre-900/10"
         role="dialog" aria-labelledby="ia-agent-titre">
    <header class="flex items-center gap-3 bg-encre-900 px-4 py-3.5 text-white">
        <span class="grid size-10 place-items-center rounded-full bg-marque-500/20 text-xl text-marque-300">{$icone('assistant')}</span>
        <div class="min-w-0 flex-1">
            <h2 id="ia-agent-titre" class="text-sm font-bold">Assistant IA — Espace agent</h2>
            <p class="flex items-center gap-1.5 text-xs text-encre-300"><span class="size-2 rounded-full bg-marque-400"></span> Orientation vers le bon service</p>
        </div>
        <button type="button" id="ia-close-btn" class="grid size-9 cursor-pointer place-items-center rounded-full text-encre-300 hover:bg-white/10 hover:text-white" aria-label="Fermer l’assistant">{$icone('fermer', 'text-lg')}</button>
    </header>

    <div id="ia-messages" class="ia-messages flex flex-1 flex-col gap-3 overflow-y-auto bg-encre-50 p-4" role="log" aria-live="polite">
        <div class="msg-ia">Décrivez la demande du citoyen : je propose le service compétent. Vous validez avant tout transfert.</div>
    </div>

    <div id="ia-loader" class="hidden flex items-center gap-2 bg-encre-50 px-4 py-2 text-xs text-encre-500">
        <span class="size-4 animate-spin rounded-full border-2 border-encre-300 border-t-marque-600"></span>
        Analyse en cours…
    </div>

    <div class="flex items-center gap-2 border-t border-encre-200 bg-white p-3">
        <label for="ia-input" class="sr-only">Votre message</label>
        <input type="text" id="ia-input" autocomplete="off"
               placeholder="Décrivez la demande, sans nom ni coordonnées…"
               title="Les noms, téléphones, adresses précises et données de santé sont masqués automatiquement, mais évitez de les saisir. Rien n'est conservé."
               class="min-w-0 flex-1 rounded-full border border-encre-200 bg-encre-50 px-4 py-2.5 text-sm outline-none focus:border-marque-500 focus:bg-white focus:ring-4 focus:ring-marque-500/15">
        <button type="button" id="ia-send-btn" aria-label="Envoyer"
                class="grid size-11 shrink-0 cursor-pointer place-items-center rounded-full bg-marque-600 text-lg text-white transition hover:bg-marque-700 disabled:cursor-wait disabled:opacity-50">{$icone('envoyer')}</button>
    </div>
</section>
HTML;
}
