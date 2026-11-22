<?php
/* =========================================================
   JEU D'ÉNIGMES DU PATRIMOINE : règles du jeu
   ---------------------------------------------------------
   Fonctions « pures » (aucun accès à la base) : testées par
   tests/jeu/regles_test.php. L'API (Backend/jeu/api.php) les utilise.

   - Parcours : une énigme se débloque quand la précédente est terminée
     (trouvée, ou réponse demandée).
   - Points : facile 10, moyen 20, difficile 30
       · l'indice divise les points par 2
       · chaque mauvaise réponse retire 10 % (jamais moins de 30 % des points)
       · « Voir la réponse » : 0 point, mais on peut continuer le parcours
   - Énigmes de date : réponse = une année ; on indique « plus ancien » ou
     « plus récent », avec 6 essais au plus (sinon la recherche par
     dichotomie trouverait toujours la réponse).
   - Réponses écrites : majuscules, accents, articles et ponctuation ignorés,
     et une petite faute de frappe est tolérée (« mouiln » = « moulin »).
   ========================================================= */

const POINTS_PAR_NIVEAU     = ['facile' => 10, 'moyen' => 20, 'difficile' => 30];
const ESSAIS_MAX_DATE       = 6;
const ESSAIS_MAX_TEXTE      = 15;
const PENALITE_PAR_ERREUR   = 0.10;
const POINTS_PLANCHER       = 0.30;
const REPONSE_LONGUEUR_MAX  = 100;
const ARTICLES_IGNORES      = ['le', 'la', 'les', 'l', 'un', 'une', 'des', 'du', 'de', 'd', 'au', 'aux'];

/** « Le Moulin à vent ! » → « moulin a vent » (minuscules, sans accents, sans articles ni ponctuation). */
function normaliserReponse(string $texte): string
{
    $texte = mb_strtolower(trim($texte), 'UTF-8');
    $texte = strtr($texte, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ú' => 'u', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae', '’' => ' ', "'" => ' ',
    ]);
    $texte = preg_replace('/[^a-z0-9 ]+/', ' ', $texte);
    $mots = array_filter(explode(' ', $texte), fn ($m) => $m !== '' && !in_array($m, ARTICLES_IGNORES, true));
    return implode(' ', $mots);
}

/** Toutes les réponses acceptées d'une énigme, normalisées. */
function reponsesAcceptees(array $enigme): array
{
    $liste = array_merge([$enigme['reponse']], explode('|', (string) ($enigme['reponses_acceptees'] ?? '')));
    return array_values(array_unique(array_filter(array_map('normaliserReponse', $liste))));
}

/**
 * Vérifie une réponse. Renvoie ['correct' => bool, 'indication' => 'plus_ancien'|'plus_recent'|null].
 * Pour une date, l'indication dit dans quel sens chercher.
 */
function verifierReponse(array $enigme, string $reponse): array
{
    if ($enigme['type'] === 'date') {
        $annee = (int) preg_replace('/\D/', '', $reponse);
        $bonne = (int) $enigme['reponse'];
        if ($annee === $bonne) {
            return ['correct' => true, 'indication' => null];
        }
        return ['correct' => false, 'indication' => $annee > $bonne ? 'plus_ancien' : 'plus_recent'];
    }

    $proposee = normaliserReponse($reponse);
    if ($proposee === '') {
        return ['correct' => false, 'indication' => null];
    }
    foreach (reponsesAcceptees($enigme) as $acceptee) {
        // Petite faute de frappe tolérée sur les réponses d'au moins 5 lettres
        $tolerance = mb_strlen($acceptee) >= 5 ? 1 : 0;
        if ($proposee === $acceptee
            || levenshtein($proposee, $acceptee) <= $tolerance
            || ($tolerance > 0 && lettresInversees($proposee, $acceptee))) {
            return ['correct' => true, 'indication' => null];
        }
    }
    return ['correct' => false, 'indication' => null];
}

/** Deux mots identiques à deux lettres voisines inversées près (« mouiln » / « moulin »). */
function lettresInversees(string $a, string $b): bool
{
    if (strlen($a) !== strlen($b) || $a === $b) {
        return false;
    }
    $differences = [];
    for ($i = 0, $n = strlen($a); $i < $n; $i++) {
        if ($a[$i] !== $b[$i]) {
            $differences[] = $i;
        }
    }
    return count($differences) === 2
        && $differences[1] === $differences[0] + 1
        && $a[$differences[0]] === $b[$differences[1]]
        && $a[$differences[1]] === $b[$differences[0]];
}

/** Points maximum d'une énigme (selon son niveau). */
function pointsMaximum(array $enigme): int
{
    return POINTS_PAR_NIVEAU[$enigme['niveau']] ?? 10;
}

/** Points gagnés en trouvant la réponse, après $erreurs mauvaises réponses. */
function pointsGagnes(array $enigme, int $erreurs, bool $indiceUtilise): int
{
    $points = pointsMaximum($enigme) * ($indiceUtilise ? 0.5 : 1);
    $coefficient = max(POINTS_PLANCHER, 1 - PENALITE_PAR_ERREUR * $erreurs);
    return max(1, (int) round($points * $coefficient));
}

/** Nombre d'essais autorisés pour une énigme. */
function essaisMaximum(array $enigme): int
{
    return $enigme['type'] === 'date' ? ESSAIS_MAX_DATE : ESSAIS_MAX_TEXTE;
}

/**
 * Statut de chaque énigme du parcours pour un joueur.
 * $progression : [enigme_id => statut en base] ; renvoie [enigme_id => 'verrouillee'|'disponible'|'resolue'|'revelee'].
 * Une énigme est disponible quand toutes celles d'avant sont terminées.
 */
function statutsParcours(array $enigmesDansLOrdre, array $progression): array
{
    $statuts = [];
    $precedentesTerminees = true;
    foreach ($enigmesDansLOrdre as $e) {
        $enBase = $progression[$e['id']] ?? null;
        if (in_array($enBase, ['resolue', 'revelee'], true)) {
            $statuts[$e['id']] = $enBase;
        } else {
            $statuts[$e['id']] = $precedentesTerminees ? 'disponible' : 'verrouillee';
            $precedentesTerminees = false;
        }
    }
    return $statuts;
}

/** Pseudonyme valide : 3 à 20 caractères, lettres, chiffres, espace, tiret, point ou tiret bas. */
function pseudoValide(string $pseudo): bool
{
    return (bool) preg_match('/^[\p{L}\p{N} ._-]{3,20}$/u', $pseudo) && trim($pseudo) === $pseudo;
}
