<?php
/* =========================================================
   Tests des règles du jeu d'énigmes (Backend/Fonctions/jeu_enigmes.php)
   Lancement :   php tests/jeu/regles_test.php
   ========================================================= */

require __DIR__ . '/../../Backend/Fonctions/jeu_enigmes.php';

$reussis = 0;
$echecs = 0;
function verifier(string $nom, bool $condition): void
{
    global $reussis, $echecs;
    $condition ? $reussis++ : $echecs++;
    echo ($condition ? '  OK    ' : '  ÉCHEC ') . $nom . "\n";
}

$moulin = ['type' => 'texte', 'niveau' => 'facile', 'reponse' => 'Le moulin', 'reponses_acceptees' => 'moulin|moulin a vent'];
$date   = ['type' => 'date',  'niveau' => 'moyen',  'reponse' => '1905', 'reponses_acceptees' => null];
$dur    = ['type' => 'photo', 'niveau' => 'difficile', 'reponse' => 'Marianne', 'reponses_acceptees' => ''];

echo "Réponses écrites\n";
verifier('« Le Moulin » = « moulin »',              verifierReponse($moulin, 'Le Moulin')['correct']);
verifier('« un moulin à vent ! » accepté',          verifierReponse($moulin, 'un moulin à vent !')['correct']);
verifier('« MOULIN » (majuscules) accepté',         verifierReponse($moulin, 'MOULIN')['correct']);
verifier('faute de frappe « mouiln » tolérée',      verifierReponse($moulin, 'mouiln')['correct']);
verifier('« moule » refusé',                        !verifierReponse($moulin, 'moule')['correct']);
verifier('réponse vide refusée',                    !verifierReponse($moulin, '   ')['correct']);
verifier('« la marianne » accepté',                 verifierReponse($dur, 'la Marianne')['correct']);
verifier('« Mariane » (1 faute) accepté',           verifierReponse($dur, 'Mariane')['correct']);

echo "Dates\n";
verifier('1905 correct',                            verifierReponse($date, '1905')['correct']);
verifier('1950 → « plus ancien »',                  verifierReponse($date, '1950')['indication'] === 'plus_ancien');
verifier('1850 → « plus récent »',                  verifierReponse($date, '1850')['indication'] === 'plus_recent');
verifier('pas de tolérance sur une date (1904)',    !verifierReponse($date, '1904')['correct']);
verifier('6 essais au plus pour une date',          essaisMaximum($date) === 6);

echo "Points\n";
verifier('facile sans erreur : 10',                 pointsGagnes($moulin, 0, false) === 10);
verifier('moyen avec indice : 10',                  pointsGagnes($date, 0, true) === 10);
verifier('difficile, 2 erreurs : 24',               pointsGagnes($dur, 2, false) === 24);
verifier('jamais moins de 30 % (difficile, 20 erreurs : 9)', pointsGagnes($dur, 20, false) === 9);
verifier('au moins 1 point',                        pointsGagnes($moulin, 50, true) >= 1);

echo "Parcours\n";
$e = [['id' => 1], ['id' => 2], ['id' => 3]];
verifier('au départ : la 1re disponible, les autres verrouillées',
    statutsParcours($e, []) === [1 => 'disponible', 2 => 'verrouillee', 3 => 'verrouillee']);
verifier('1re trouvée : la 2e se débloque',
    statutsParcours($e, [1 => 'resolue']) === [1 => 'resolue', 2 => 'disponible', 3 => 'verrouillee']);
verifier('réponse demandée : la suivante se débloque aussi',
    statutsParcours($e, [1 => 'revelee', 2 => 'resolue']) === [1 => 'revelee', 2 => 'resolue', 3 => 'disponible']);
verifier('« en cours » ne débloque pas la suivante',
    statutsParcours($e, [1 => 'en_cours']) === [1 => 'disponible', 2 => 'verrouillee', 3 => 'verrouillee']);

echo "Pseudonymes\n";
verifier('« Léa_85 » valide',                       pseudoValide('Léa_85'));
verifier('« ab » trop court',                       !pseudoValide('ab'));
verifier('« <script> » refusé',                     !pseudoValide('<script>'));
verifier('espace au début refusé',                  !pseudoValide(' Léa'));

echo "\n$reussis réussi(s), $echecs échec(s)\n";
exit($echecs > 0 ? 1 : 0);
