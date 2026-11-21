<?php
/* =========================================================
   Champs des formulaires de connexion / inscription (style « carte moderne »)
   ---------------------------------------------------------
   Utilisé par : Frontend/connexion_citoyen.php, Frontend/inscriptions_citoyen.php,
                 Agent-prenium/connexion_agent.php
   Styles : .carte-auth dans Frontend/assets/css/style.css
   Bouton « afficher le mot de passe » : Frontend/assets/js/mot_de_passe_visible.js
   ========================================================= */
require_once __DIR__ . '/icones.php';

/**
 * Un champ : étiquette au-dessus, icône à gauche dans la zone de saisie.
 * Pour type="password", ajoute le bouton œil qui affiche / masque le mot de passe.
 */
function champAuth(string $id, string $libelle, string $type, string $nomIcone, string $nom = '',
                   string $valeur = '', string $aide = '', string $apres = ''): string
{
    $nom = $nom !== '' ? $nom : $id;
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    // $aide : courte indication de format sous l'étiquette (liée au champ pour les lecteurs d'écran)
    $idAide = $aide !== '' ? $id . '-aide' : '';
    $html = '<div class="champ-auth">'
        . '<label for="' . $e($id) . '">' . $e($libelle) . '</label>'
        . ($aide !== '' ? '<p class="champ-auth-aide" id="' . $e($idAide) . '">' . $e($aide) . '</p>' : '')
        . '<div class="champ-auth-saisie">'
        . icone($nomIcone, 'champ-auth-icone')
        . '<input type="' . $e($type) . '" id="' . $e($id) . '" name="' . $e($nom) . '"'
        . ($valeur !== '' && $type !== 'password' ? ' value="' . $e($valeur) . '"' : '')
        . ($idAide !== '' ? ' aria-describedby="' . $e($idAide) . '"' : '')
        . ' required>';

    if ($type === 'password') {
        $html .= '<button type="button" class="voir-mot-de-passe" aria-controls="' . $e($id) . '"'
            . ' aria-pressed="false" aria-label="Afficher le mot de passe">'
            . icone('oeil', 'icone-voir') . icone('oeil-barre', 'icone-masquer')
            . '</button>';
    }

    // $apres : HTML ajouté sous la zone de saisie (ex. liste des règles du mot de passe)
    return $html . '</div>' . $apres . '</div>';
}

/**
 * Liste déroulante au même style que champAuth() (icône à gauche, flèche à droite).
 * $options : [valeur => libellé] ; $aide : courte explication sous l'étiquette.
 */
function selectAuth(string $id, string $libelle, string $nomIcone, array $options, string $aide = ''): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $idAide = $aide !== '' ? $id . '-aide' : '';

    $html = '<div class="champ-auth">'
        . '<label for="' . $e($id) . '">' . $e($libelle) . '</label>'
        . ($aide !== '' ? '<p class="champ-auth-aide" id="' . $e($idAide) . '">' . $e($aide) . '</p>' : '')
        . '<div class="champ-auth-saisie champ-auth-select">'
        . icone($nomIcone, 'champ-auth-icone')
        . '<select id="' . $e($id) . '" name="' . $e($id) . '"' . ($idAide !== '' ? ' aria-describedby="' . $e($idAide) . '"' : '') . ' required>';
    foreach ($options as $valeur => $texte) {
        $html .= '<option value="' . $e((string) $valeur) . '">' . $e((string) $texte) . '</option>';
    }
    return $html . '</select>' . icone('chevron-bas', 'champ-auth-fleche') . '</div></div>';
}

/**
 * Carte cliquable de la page « Choisissez votre profil » (choix_connexion / choix_inscription).
 * $variante : 'citoyen' (bleu) ou 'agent' (vert).
 */
function choixProfil(string $lien, string $nomIcone, string $titre, string $texte, string $variante = 'citoyen'): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    return '<a class="choix-profil choix-profil-' . $e($variante) . '" href="' . $e($lien) . '">'
        . '<span class="choix-profil-pastille">' . icone($nomIcone) . '</span>'
        . '<span class="choix-profil-texte">'
        . '<span class="choix-profil-titre">' . $e($titre) . '</span>'
        . '<span class="choix-profil-description">' . $e($texte) . '</span>'
        . '</span>'
        . icone('chevron-droite', 'choix-profil-fleche')
        . '</a>';
}

/** Message « session expirée » (page de connexion ouverte avec ?session=expiree). */
function messageSessionExpiree(): string
{
    if (($_GET['session'] ?? '') !== 'expiree') {
        return '';
    }
    return '<div class="message-auth-info" role="status">' . icone('horloge')
        . ' <span>Votre session a expiré ou vous n’êtes pas connecté. Reconnectez-vous pour continuer.</span></div>';
}

/** Message de réussite ou d'erreur lu dans la session (puis effacé), affiché dans la carte. */
function messageAuth(string $cleSession, string $type = 'error'): string
{
    if (empty($_SESSION[$cleSession])) {
        return '';
    }
    $texte = htmlspecialchars((string) $_SESSION[$cleSession], ENT_QUOTES, 'UTF-8');
    unset($_SESSION[$cleSession]);
    return $type === 'success'
        ? '<div class="message-auth-succes" role="status">' . icone('valide') . ' ' . $texte . '</div>'
        : '<div class="error-message" role="alert">' . $texte . '</div>';
}

/**
 * Bouton « ← Retour » en haut à gauche de la carte (parcours de connexion / inscription).
 * Lien vers la page précédente LOGIQUE du parcours (fonctionne même sans JavaScript
 * et même si l'on arrive directement sur la page depuis un e-mail).
 */
function retourAuth(string $lien, string $libelle = 'Retour'): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    return '<a class="retour-auth" href="' . $e($lien) . '">' . icone('fleche-gauche') . '<span>' . $e($libelle) . '</span></a>';
}

/** En-tête de la carte : pastille ronde avec icône, titre et sous-titre. */
function enteteAuth(string $nomIcone, string $titre, string $sousTitre): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    return '<div class="carte-auth-entete">'
        . '<span class="carte-auth-pastille">' . icone($nomIcone) . '</span>'
        . '<h2>' . $e($titre) . '</h2>'
        . '<p>' . $e($sousTitre) . '</p>'
        . '</div>';
}
