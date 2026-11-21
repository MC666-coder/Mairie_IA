<?php
/* =========================================================
   Carte « profil connecté » (en-tête des pages agent et citoyen)
   ---------------------------------------------------------
   Rond avec les initiales, nom, badge de rôle, puis une ligne
   par information avec une icône.
   Styles : Frontend/assets/css/carte_profil.css (chargé sur chaque page)
   La carte garde l'id « bulle-connexion-agent » : sa place dans
   l'en-tête reste celle définie par le CSS de chaque page.
   ========================================================= */
require_once __DIR__ . '/icones.php';

/**
 * @param string $prenom
 * @param string $nom
 * @param string $badge   rôle affiché dans la pastille (« Responsable », « Citoyen »...)
 * @param array  $lignes  [[nom d'icône, libellé (lecteur d'écran), valeur], ...]
 */
function carteProfil(string $prenom, string $nom, string $badge, array $lignes, string $variante = 'agent'): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $initiales = mb_strtoupper(mb_substr(trim($prenom), 0, 1) . mb_substr(trim($nom), 0, 1));
    $nomComplet = trim($prenom . ' ' . $nom);

    $html = '<div id="bulle-connexion-agent" class="carte-profil carte-profil-' . $e($variante) . '" aria-label="Personne connectée">'
        . '<div class="carte-profil-entete">'
        . '<span class="carte-profil-avatar" aria-hidden="true">' . $e($initiales !== '' ? $initiales : '?') . '</span>'
        . '<div class="carte-profil-identite">'
        . '<span class="carte-profil-nom">' . $e($nomComplet) . '</span>'
        . '<span class="carte-profil-badge">' . $e($badge) . '</span>'
        . '</div></div>';

    if ($lignes) {
        $html .= '<ul class="carte-profil-infos">';
        foreach ($lignes as [$nomIcone, $libelle, $valeur]) {
            $html .= '<li>' . icone($nomIcone)
                . '<span class="visually-hidden">' . $e($libelle) . ' : </span>'
                . '<span title="' . $e($libelle) . '">' . $e((string) $valeur) . '</span></li>';
        }
        $html .= '</ul>';
    }
    return $html . '</div>';
}

/** Carte d'un agent (tableau $agent des pages Agent-prenium). */
function carteProfilAgent(array $agent): string
{
    $niveaux = ['agent' => 'Agent', 'manager' => 'Manager', 'admin' => 'Administrateur'];
    $niveau = (string) ($agent['niveau_acces'] ?? '');
    $service = $agent['Nom_service'] ?? $agent['nom_service'] ?? 'Service non renseigné';

    return carteProfil(
        (string) ($agent['prenom_agent'] ?? ''),
        (string) ($agent['nom_agent'] ?? ''),
        mb_convert_case((string) ($agent['role'] ?? 'Agent'), MB_CASE_TITLE, 'UTF-8'),
        [
            ['carte-identite', 'Identifiant agent', (string) ($agent['identifiant_agent'] ?? '')],
            ['mairie', 'Service', (string) $service],
            ['bouclier', "Niveau d'accès", $niveaux[$niveau] ?? $niveau],
        ],
        'agent'
    );
}

/** Carte d'un citoyen (tableau $citoyen des pages de l'espace citoyen). */
function carteProfilCitoyen(array $citoyen, int $citoyenId): string
{
    return carteProfil(
        (string) ($citoyen['prenom_citoyen'] ?? ''),
        (string) ($citoyen['nom_citoyen'] ?? ''),
        'Citoyen',
        [
            ['carte-identite', 'Numéro citoyen', 'N° ' . $citoyenId],
        ],
        'citoyen'
    );
}
