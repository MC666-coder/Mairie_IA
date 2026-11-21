<?php
/* =========================================================
   ICÔNES SVG DU SITE (style « trait fin », d'après Lucide, licence ISC)
   ---------------------------------------------------------
   Usage :  <?= icone('cadenas') ?> Connexion
   - L'icône prend la couleur du texte (currentColor) et la taille de la police (1.1em).
   - Décorative par défaut (aria-hidden) : le texte à côté porte le sens.
   - Même liste côté JS : Frontend/assets/js/icones.js (garder les deux synchronisés).
   ========================================================= */

const ICONES_SVG = [
    'cadenas'        => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    'connexion'      => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/>',
    'inscription'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>',
    'trombone'       => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
    'fermer'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'boite-reception'=> '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    'envoyer'        => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    'courrier'       => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    'alerte'         => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    'valide'         => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
    'mairie'         => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
    'conversation'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'stylo'          => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    'assistant'      => '<path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>',
    'chevron-haut'   => '<path d="m18 15-6-6-6 6"/>',
    'fleche-gauche'  => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
    'fleche-droite'  => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'chevron-bas'    => '<path d="m6 9 6 6 6-6"/>',
    'bouclier'       => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
    'chevron-droite' => '<path d="m9 18 6-6-6-6"/>',
    'cercle'         => '<circle cx="12" cy="12" r="10"/>',
    'utilisateur'    => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'telephone'      => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    'mallette'       => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    'oeil'           => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'oeil-barre'     => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>',
];

/**
 * Renvoie le code SVG d'une icône (chaîne vide si le nom est inconnu).
 * $libelle : texte lu par les lecteurs d'écran, seulement si l'icône est seule (sans texte à côté).
 */
function icone(string $nom, string $classe = '', string $libelle = ''): string
{
    if (!isset(ICONES_SVG[$nom])) {
        return '';
    }
    $classes = trim('icone icone-' . $nom . ' ' . $classe);
    $accessibilite = $libelle !== ''
        ? 'role="img" aria-label="' . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '"'
        : 'aria-hidden="true" focusable="false"';

    return '<svg class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" ' . $accessibilite
        . ' width="1.1em" height="1.1em" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        . ' style="vertical-align:-0.18em;flex-shrink:0">'
        . ICONES_SVG[$nom] . '</svg>';
}

/**
 * Boutons « Connexion » et « Inscription » de la barre de navigation, identiques sur toutes les pages
 * (fond gris, icône bleue pour Connexion, verte pour Inscription ; blanche au survol ou quand le bouton est actif).
 * $actif : 'connexion' ou 'inscription' pour surligner le bouton de la page en cours.
 */
function boutonsConnexion(string $actif = ''): string
{
    $boutons = [
        'connexion' => [
            'lien'    => 'choix_connexion.php',
            'texte'   => 'Connexion',
            'couleur' => '#003b8e',
            'svg'     => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
        ],
        'inscription' => [
            'lien'    => 'choix_inscription.php',
            'texte'   => 'Inscription',
            'couleur' => '#0a7c2f',
            'svg'     => '<circle cx="12" cy="8" r="4"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/><line x1="20" y1="12" x2="20" y2="18"/><line x1="23" y1="15" x2="17" y2="15"/>',
        ],
    ];

    $html = '';
    foreach ($boutons as $cle => $b) {
        $classe = 'nav-btn' . ($cle === $actif ? ' active' : '');
        $courant = $cle === $actif ? ' aria-current="page"' : '';
        $html .= '<a href="' . $b['lien'] . '" class="' . $classe . '"' . $courant . '>'
            . '<span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"'
            . ' stroke="' . $b['couleur'] . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . $b['svg'] . '</svg></span>' . $b['texte'] . '</a>' . "\n";
    }
    return $html;
}
