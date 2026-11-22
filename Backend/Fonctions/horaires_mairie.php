<?php
/* =========================================================
   Horaires d'ouverture de la mairie
   ---------------------------------------------------------
   Une seule source pour les pages « La mairie » et « Contacts » :
   - les jours et créneaux d'ouverture
   - etatOuverture() : « Ouvert maintenant · Ferme à 12h00 »
     ou « Fermé · Ouvre lundi à 8h30 », à l'heure de Paris
   ========================================================= */

const JOURS_OUVERTS = [1, 2, 3, 4, 5]; // 1 = lundi … 7 = dimanche
// Heures au format « HH:MM » : comparer deux chaînes de ce format revient à comparer les heures
const CRENEAUX = [['08:30', '12:00'], ['13:30', '17:00']];

/** "08:30" → "8h30" */
function heureLisible(string $heure): string
{
    return ltrim(str_replace(':', 'h', $heure), '0');
}

/**
 * Indique si la mairie est ouverte maintenant, et sinon quand elle rouvre.
 * L'heure est prise à Paris : le serveur peut être réglé sur un autre fuseau (ex. UTC).
 *
 * @return array{ouvert: bool, texte: string, detail: string}
 */
function etatOuverture(?DateTimeImmutable $maintenant = null): array
{
    $maintenant ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
    $jour  = (int) $maintenant->format('N');
    $heure = $maintenant->format('H:i');

    if (in_array($jour, JOURS_OUVERTS, true)) {
        foreach (CRENEAUX as [$debut, $fin]) {
            if ($heure >= $debut && $heure < $fin) {
                return ['ouvert' => true, 'texte' => 'Ouvert maintenant', 'detail' => 'Ferme à ' . heureLisible($fin)];
            }
            if ($heure < $debut) {
                return ['ouvert' => false, 'texte' => 'Fermé', 'detail' => 'Ouvre à ' . heureLisible($debut)];
            }
        }
    }

    // Après la fermeture ou le week-end : prochain jour ouvert
    $noms = [1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'];
    $suivant = $maintenant;
    do {
        $suivant = $suivant->modify('+1 day');
    } while (!in_array((int) $suivant->format('N'), JOURS_OUVERTS, true));
    $quand = $suivant->format('Y-m-d') === $maintenant->modify('+1 day')->format('Y-m-d')
        ? 'demain'
        : $noms[(int) $suivant->format('N')];

    return ['ouvert' => false, 'texte' => 'Fermé', 'detail' => 'Ouvre ' . $quand . ' à ' . heureLisible(CRENEAUX[0][0])];
}

/** Pastille HTML « Ouvert maintenant » (verte) ou « Fermé » (grise), suivie du détail. */
function pastilleOuverture(array $etat): string
{
    $couleurs = $etat['ouvert']
        ? ['bg-green-50 text-green-700 ring-green-600/20', 'bg-green-500']
        : ['bg-slate-100 text-slate-700 ring-slate-500/20', 'bg-slate-400'];
    return '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold ring-1 ' . $couleurs[0] . '">'
        . '<span class="size-2 rounded-full ' . $couleurs[1] . '"></span> ' . htmlspecialchars($etat['texte'], ENT_QUOTES, 'UTF-8') . '</span>'
        . ' <span class="text-sm text-slate-500">' . htmlspecialchars($etat['detail'], ENT_QUOTES, 'UTF-8') . '</span>';
}
