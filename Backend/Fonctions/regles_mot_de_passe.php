<?php
/* =========================================================
   Règles du mot de passe — UNE seule source (même règle que les agents)
   ---------------------------------------------------------
   - Côté serveur : erreursMotDePasse() (inscription citoyen)
   - Côté formulaire : la liste REGLES_MOT_DE_PASSE est affichée sous le champ
     et vérifiée en direct par Frontend/assets/js/regles_mot_de_passe.js
     (attribut data-regle = clé ci-dessous ; garder les deux synchronisés).
   ========================================================= */

const LONGUEUR_MIN_MOT_DE_PASSE = 10;

const REGLES_MOT_DE_PASSE = [
    'longueur'   => '10 caractères minimum',
    'majuscule'  => '1 lettre majuscule',
    'minuscule'  => '1 lettre minuscule',
    'chiffre'    => '1 chiffre',
    'special'    => '1 caractère spécial (! ? @ # $ % …)',
];

/**
 * Renvoie la liste des règles NON respectées (tableau vide = mot de passe valide).
 */
function erreursMotDePasse(string $motDePasse): array
{
    $ok = [
        'longueur'  => mb_strlen($motDePasse) >= LONGUEUR_MIN_MOT_DE_PASSE,
        'majuscule' => (bool) preg_match('/\p{Lu}/u', $motDePasse),
        'minuscule' => (bool) preg_match('/\p{Ll}/u', $motDePasse),
        'chiffre'   => (bool) preg_match('/[0-9]/', $motDePasse),
        'special'   => (bool) preg_match('/[^\p{L}0-9]/u', $motDePasse),
    ];

    $erreurs = [];
    foreach ($ok as $cle => $respectee) {
        if (!$respectee) {
            $erreurs[] = REGLES_MOT_DE_PASSE[$cle];
        }
    }
    return $erreurs;
}

/** Liste HTML des règles, affichée sous le champ mot de passe (cochée en direct par le JS). */
function listeReglesMotDePasse(string $idChamp): string
{
    $html = '<ul class="regles-mot-de-passe" id="regles-' . htmlspecialchars($idChamp, ENT_QUOTES, 'UTF-8') . '"'
        . ' data-champ="' . htmlspecialchars($idChamp, ENT_QUOTES, 'UTF-8') . '" aria-live="polite">';
    foreach (REGLES_MOT_DE_PASSE as $cle => $libelle) {
        $html .= '<li data-regle="' . $cle . '">'
            . icone('valide', 'regle-ok') . icone('cercle', 'regle-attente')
            . '<span>' . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="visually-hidden regle-etat"> : pas encore respecté</span></li>';
    }
    return $html . '</ul>';
}
