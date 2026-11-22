<?php
/* =========================================================
   En-tête et pied de page du site public et de l'espace citoyen
   Style « moderne verre » (classes Tailwind CSS)
   ---------------------------------------------------------
   UNE seule source pour toutes les pages du dossier Frontend/ :
     <?= feuillesGabarit() ?>              dans le <head>, après style.css
     <?= enTetePublic('agenda') ?>         site public (Accueil, Agenda...)
     <?= enTeteCitoyen('tableau', $citoyen, $citoyenId) ?>  espace citoyen connecté
     <?= piedDePage() ?>                   avant </body>
   Les pages Agent-prenium gardent leur propre style.

   Après avoir ajouté ou changé des classes Tailwind ici ou dans une page :
       cd Frontend  puis  npm run css      (regénère assets/css/tailwind.css)
   ========================================================= */
require_once __DIR__ . '/icones.php';
require_once __DIR__ . '/carte_profil.php';

/** Couleur principale de la mairie (classes Tailwind avec valeur arbitraire). */
const BLEU_MAIRIE = '#003b8e';

/** Liens du menu public : clé => [page, libellé]. */
const MENU_PUBLIC = [
    'accueil'     => ['index.php', 'Accueil'],
    'mairie'      => ['mairie.php', 'La mairie'],
    'agenda'      => ['agenda.php', 'Agenda'],
    'actualites'  => ['Actualités.php', 'Actualités'],
    'services'    => ['Services.php', 'Services'],
    'contacts'    => ['Contacts.php', 'Contacts'],
    'newsletter'  => ['Newsletter.php', 'Newsletter'],
];

/** Liens du menu de l'espace citoyen. */
const MENU_CITOYEN = [
    'tableau'  => ['Portail_citoyen.php', 'Tableau de bord', 'tableau'],
    'demande'  => ['nouvelle_demande.php', 'Créer une demande', 'plus'],
    'jeu'      => ['Jeu-enigme.php', 'Énigmes', 'trophee'],
];

/** Feuille Tailwind + script du menu (dans le <head>, APRÈS les autres feuilles de style). */
function feuillesGabarit(): string
{
    $v = fn(string $f): string => (string) @filemtime(__DIR__ . '/../../Frontend/assets/' . $f);
    return '<link rel="stylesheet" href="assets/css/tailwind.css?v=' . $v('css/tailwind.css') . '">' . "\n"
        . '    <script src="assets/js/menu.js?v=' . $v('js/menu.js') . '" defer></script>' . "\n";
}

/** Logo : pastille bleue + nom de la commune. */
function logoMairie(string $lien): string
{
    return '<a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '" class="flex shrink-0 items-center gap-2.5 rounded-xl no-underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#003b8e]">'
        . '<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#003b8e] text-lg text-white shadow-sm">' . icone('mairie') . '</span>'
        . '<span class="flex flex-col leading-tight">'
        . '<span class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Mairie de</span>'
        . '<span class="text-[15px] font-bold text-slate-900">L’Aiguillon-sur-Vie</span>'
        . '</span></a>';
}

/** Un lien du menu (bureau ou mobile). */
function lienMenu(string $page, string $libelle, bool $actif, bool $mobile = false, string $nomIcone = ''): string
{
    $base = $mobile
        ? 'flex items-center gap-2 rounded-xl px-4 py-3 text-[15px] font-medium no-underline transition '
        : 'flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-medium no-underline transition ';
    $etat = $actif
        ? 'bg-[#e6eef9] text-[#003b8e]'
        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    return '<a href="' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '" class="' . $base . $etat . '"'
        . ($actif ? ' aria-current="page"' : '') . '>'
        . ($nomIcone !== '' ? icone($nomIcone) : '')
        . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '</a>';
}

/** Boutons Connexion / Inscription (style pilule). */
function boutonsComptePublic(string $actif, bool $mobile = false): string
{
    $largeur = $mobile ? 'flex-1 justify-center ' : '';
    $connexion = 'inline-flex items-center gap-2 rounded-full bg-[#003b8e] px-4 py-2 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-[#002a66] ' . $largeur;
    $inscription = 'inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/60 px-4 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:border-[#003b8e] hover:text-[#003b8e] ' . $largeur;
    return '<a href="choix_connexion.php" class="' . $connexion . '"' . ($actif === 'connexion' ? ' aria-current="page"' : '') . '>' . icone('connexion') . 'Connexion</a>'
        . '<a href="choix_inscription.php" class="' . $inscription . '"' . ($actif === 'inscription' ? ' aria-current="page"' : '') . '>' . icone('inscription') . 'Inscription</a>';
}

/**
 * Coque commune : barre flottante « verre », menu bureau, bouton burger, menu mobile.
 * Les anciennes règles CSS sur « header » sont neutralisées par les classes (block, p-0, border-0...).
 */
function coqueEnTete(string $lienLogo, string $menuBureau, string $actionsBureau, string $menuMobile, bool $menuCourt = false): string
{
    // Menu court (espace citoyen : 2 liens) visible dès la tablette ; menu public complet à partir de 1280 px.
    // Classes écrites en entier : Tailwind ne repère pas les classes construites par morceaux.
    $nav = $menuCourt ? 'hidden items-center gap-1 md:flex' : 'hidden items-center gap-1 xl:flex';
    $burger = $menuCourt ? 'md:hidden' : 'xl:hidden';

    return '<header class="sticky top-0 z-50 block border-0 bg-transparent px-3 pt-3 pb-0 sm:px-6">'
        . '<div class="mx-auto max-w-6xl">'
        . '<div class="flex items-center justify-between gap-4 rounded-2xl border border-white/70 bg-white/75 px-3 py-2 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-md sm:px-4">'
        . logoMairie($lienLogo)
        . '<nav class="' . $nav . '" aria-label="Menu principal">' . $menuBureau . '</nav>'
        . '<div class="flex items-center gap-2">'
        . $actionsBureau
        . '<button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-700 transition hover:bg-slate-100 ' . $burger . '"'
        . ' data-bascule="menu-mobile" aria-controls="menu-mobile" aria-expanded="false" aria-label="Ouvrir le menu">'
        . icone('menu') . '</button>'
        . '</div></div>'
        . '<nav id="menu-mobile" class="mt-2 hidden flex-col gap-1 rounded-2xl border border-white/70 bg-white/95 p-2 shadow-lg backdrop-blur-md ' . $burger . '" aria-label="Menu principal (mobile)">'
        . $menuMobile
        . '</nav>'
        . '</div></header>';
}

/** En-tête du site public. $actif : clé de MENU_PUBLIC, ou 'connexion' / 'inscription'. */
function enTetePublic(string $actif = ''): string
{
    $bureau = $mobile = '';
    foreach (MENU_PUBLIC as $cle => [$page, $libelle]) {
        $bureau .= lienMenu($page, $libelle, $cle === $actif);
        $mobile .= lienMenu($page, $libelle, $cle === $actif, true);
    }
    $mobile .= '<div class="mt-2 flex gap-2 border-t border-slate-200 px-2 pt-3 pb-1">' . boutonsComptePublic($actif, true) . '</div>';
    $actions = '<div class="hidden items-center gap-2 sm:flex">' . boutonsComptePublic($actif) . '</div>';
    return coqueEnTete('index.php', $bureau, $actions, $mobile);
}

/** En-tête de l'espace citoyen connecté : menu + rond d'initiales qui ouvre la carte de profil. */
function enTeteCitoyen(string $actif, array $citoyen, int $citoyenId): string
{
    $bureau = $mobile = '';
    foreach (MENU_CITOYEN as $cle => [$page, $libelle, $nomIcone]) {
        $bureau .= lienMenu($page, $libelle, $cle === $actif, false, $nomIcone);
        $mobile .= lienMenu($page, $libelle, $cle === $actif, true, $nomIcone);
    }
    $deconnexion = 'flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-[#c62828] no-underline transition hover:bg-red-50';
    $mobile .= '<a href="logout.php" class="' . $deconnexion . '">' . icone('deconnexion') . 'Déconnexion</a>';

    $prenom = (string) ($citoyen['prenom_citoyen'] ?? '');
    $nom = (string) ($citoyen['nom_citoyen'] ?? '');
    $initiales = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

    $profil = '<div class="relative">'
        . '<button type="button" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pr-3 pl-1 text-sm font-semibold text-slate-700 transition hover:border-[#0a7c2f]"'
        . ' data-bascule="menu-profil" aria-controls="menu-profil" aria-expanded="false">'
        . '<span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0a7c2f] text-xs font-bold text-white" aria-hidden="true">' . htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') . '</span>'
        . '<span class="hidden sm:inline">' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . '</span>'
        . icone('chevron-bas') . '</button>'
        . '<div id="menu-profil" class="absolute right-0 top-full z-50 mt-2 hidden w-72 flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">'
        . carteProfilCitoyen($citoyen, $citoyenId)
        . '<a href="logout.php" class="' . $deconnexion . '">' . icone('deconnexion') . 'Déconnexion</a>'
        . '</div></div>';

    return coqueEnTete('Portail_citoyen.php', $bureau, $profil, $mobile, true);
}

/** Pied de page commun (avec la bande tricolore). Les anciennes règles « footer » sont neutralisées. */
function piedDePage(): string
{
    $lien = 'text-slate-600 no-underline transition hover:text-[#003b8e]';
    $titre = 'mb-3 text-sm font-bold uppercase tracking-wider text-slate-900';
    $liens = '';
    foreach (MENU_PUBLIC as [$page, $libelle]) {
        $liens .= '<li><a class="' . $lien . '" href="' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '</a></li>';
    }

    return '<footer class="mt-16 block w-full border-0 bg-white p-0 text-left text-sm text-slate-600">'
        . '<div class="flex h-1" aria-hidden="true"><span class="flex-1 bg-[#003b8e]"></span><span class="flex-1 bg-slate-100"></span><span class="flex-1 bg-[#e1000f]"></span></div>'
        . '<div class="mx-auto grid max-w-6xl gap-10 px-6 py-12 sm:grid-cols-2 lg:grid-cols-4">'
        // Colonne 1 : la commune
        . '<div class="flex flex-col gap-4">' . logoMairie('index.php')
        . '<p class="m-0 text-sm leading-relaxed text-slate-500">Vos idées, notre action : partageons la réussite de L’Aiguillon-sur-Vie.</p>'
        . '<a href="Newsletter.php" class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:border-[#003b8e] hover:text-[#003b8e]">'
        . icone('courrier') . 'S’abonner à la newsletter</a></div>'
        // Colonne 2 : navigation
        . '<div><p class="' . $titre . '">Le site</p><ul class="m-0 flex list-none flex-col gap-2 p-0">' . $liens . '</ul></div>'
        // Colonne 3 : informations légales
        . '<div><p class="' . $titre . '">Informations légales</p><ul class="m-0 flex list-none flex-col gap-2 p-0">'
        . '<li><a class="' . $lien . '" href="mentions-legales.php">Mentions légales</a></li>'
        . '<li><a class="' . $lien . '" href="politique-confidentialite.php">Politique de confidentialité</a></li>'
        . '<li><a class="' . $lien . '" href="accessibilite.php">Accessibilité</a></li>'
        . '<li><a class="' . $lien . '" href="rgpd.php">Données personnelles (RGPD)</a></li>'
        . '</ul></div>'
        // Colonne 4 : coordonnées et horaires
        . '<div><p class="' . $titre . '">Nous trouver</p><ul class="m-0 flex list-none flex-col gap-3 p-0">'
        . '<li class="flex gap-2">' . icone('position', 'mt-1 text-slate-400') . '<span>Mairie de L’Aiguillon-sur-Vie<br>[Adresse à compléter]</span></li>'
        . '<li class="flex gap-2">' . icone('telephone', 'mt-1 text-slate-400') . '<span>[Téléphone à compléter]</span></li>'
        . '<li class="flex gap-2">' . icone('horloge', 'mt-1 text-slate-400') . '<span>Lun–Ven : 9h–12h / 14h–17h<br>Samedi : 9h–12h</span></li>'
        . '</ul></div>'
        . '</div>'
        . '<div class="border-t border-slate-200">'
        . '<div class="mx-auto flex max-w-6xl flex-col gap-2 px-6 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">'
        . '<span>&copy; ' . date('Y') . ' Commune de L’Aiguillon-sur-Vie — Tous droits réservés</span>'
        . '<span>Liberté · Égalité · Fraternité</span>'
        . '</div></div></footer>';
}
