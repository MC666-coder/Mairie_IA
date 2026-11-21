<?php
/* =========================================================
   Création d'une demande par un citoyen
   ---------------------------------------------------------
   Valeurs partagées entre le formulaire (Frontend/nouvelle_demande.php)
   et son traitement (Frontend/traitement_nouvelle_demande.php) :
   une seule liste, donc pas de décalage entre ce qui est proposé et ce qui est accepté.
   ========================================================= */

/** Types proposés au citoyen (codes de la table types_demandes). */
const TYPES_DEMANDE = [
    'plainte'    => ['libelle' => 'Plainte',    'icone' => 'alerte',   'aide' => 'Un problème à signaler'],
    'question'   => ['libelle' => 'Question',   'icone' => 'aide',     'aide' => 'Besoin d’une information'],
    'suggestion' => ['libelle' => 'Suggestion', 'icone' => 'ampoule',  'aide' => 'Une idée pour la commune'],
];

/** Priorités possibles pour un citoyen ('urgente' est réservée aux agents). */
const PRIORITES_DEMANDE = [
    'basse'   => 'Basse',
    'normale' => 'Normale',
    'haute'   => 'Haute',
];

/** Longueur du message (en caractères). */
const MESSAGE_MIN = 10;
const MESSAGE_MAX = 2000;

/** Icône d'un service, d'après son nom (icône « mairie » si aucun mot ne correspond). */
function iconeService(string $nom): string
{
    $correspondances = [
        'social'        => 'coeur',
        'cimeti'        => 'pousse',
        'communication' => 'megaphone',
        'culture'       => 'musique',
        'environnement' => 'feuille',
        'civil'         => 'carte-identite',
        'nement'        => 'calendrier', // Événementiel
        'finance'       => 'euro',
        'police'        => 'bouclier',
        'humaines'      => 'groupe',
        'scolaire'      => 'ecole',
        'transport'     => 'bus',
        'urbanisme'     => 'maison',
        'voirie'        => 'cone',
    ];
    $nom = mb_strtolower($nom, 'UTF-8');
    foreach ($correspondances as $mot => $icone) {
        if (str_contains($nom, $mot)) {
            return $icone;
        }
    }
    return 'mairie';
}
