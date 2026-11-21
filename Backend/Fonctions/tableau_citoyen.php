<?php
require_once __DIR__ . '/icones.php';
/* =========================================================
   Tableau de bord citoyen : données + gabarits HTML
   ---------------------------------------------------------
   UNE SEULE source pour l'affichage des statistiques et des demandes :
   - Frontend/Portail_citoyen.php l'utilise au chargement de la page ;
   - Backend/citoyen/tableau_de_bord.php l'utilise pour la mise à jour
     automatique (AJAX).
   Tout texte est échappé ici, côté serveur : le JavaScript se contente
   d'insérer un HTML déjà sûr.
   ========================================================= */

const LIBELLES_STATUT = [
    'nouvelle'   => 'Nouvelle',
    'analyse_ia' => 'Analysée par l\'IA',
    'en_cours'   => 'En cours',
    'en_attente' => 'En attente',
    'resolue'    => 'Résolue',
    'cloturee'   => 'Clôturée',
];

const LIBELLES_PRIORITE = [
    'basse'   => 'Basse',
    'normale' => 'Normale',
    'haute'   => 'Haute',
    'urgente' => 'Urgente',
];

/** Échappement HTML court (texte venant de la base ou du citoyen). */
function echapper(?string $texte, string $defaut = 'non renseigné'): string
{
    return htmlspecialchars($texte ?? $defaut, ENT_QUOTES, 'UTF-8');
}

/**
 * Statistiques et demandes du citoyen connecté (uniquement les siennes).
 *
 * @return array{stats: array<string,int>, demandes: list<array>}
 */
function donneesTableauCitoyen(PDO $pdo, int $citoyenId): array
{
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(statut = 'nouvelle'), 0)   AS nouvelle,
            COALESCE(SUM(statut = 'analyse_ia'), 0) AS analyse_ia,
            COALESCE(SUM(statut = 'en_cours'), 0)   AS en_cours,
            COALESCE(SUM(statut = 'resolue'), 0)    AS resolue
        FROM demandes
        WHERE citoyen_id = ?
    ");
    $stmt->execute([$citoyenId]);
    $stats = array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC) ?: []);

    $stmt = $pdo->prepare("
        SELECT d.id, t.libelle AS type, d.message, d.priorite, d.statut, d.created_at,
               d.date_mise_a_jour, s.nom_service, s.description_service,
               -- Messages de la mairie que le citoyen n'a pas encore lus
               (SELECT COUNT(*) FROM messages_demandes m
                WHERE m.demande_id = d.id AND m.auteur_type = 'agent' AND m.statut = 'nouveau') AS messages_non_lus
        FROM demandes d
        LEFT JOIN services s       ON d.service_id = s.id
        LEFT JOIN types_demandes t ON d.type_id = t.id
        WHERE d.citoyen_id = ?
        ORDER BY d.created_at DESC, d.id DESC
    ");
    $stmt->execute([$citoyenId]);

    return ['stats' => $stats, 'demandes' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Empreinte des données : le navigateur ne redessine que si elle change
 * (nouvelle demande, changement de statut, de service...).
 */
function signatureTableauCitoyen(array $donnees): string
{
    return hash('sha256', json_encode($donnees));
}

/* ---------------------------------------------------------
   Apparence (classes Tailwind CSS, compilées dans Frontend/assets/css/tailwind.css)
   Après une modification de classes ici : cd Frontend puis npm run css
   Classes écrites EN ENTIER : Tailwind ne repère pas les classes construites par morceaux.
--------------------------------------------------------- */

/** Couleurs des pastilles de statut. */
const COULEURS_STATUT = [
    'nouvelle'   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'analyse_ia' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
    'en_cours'   => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'en_attente' => 'bg-orange-50 text-orange-800 ring-orange-600/20',
    'resolue'    => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'cloturee'   => 'bg-slate-100 text-slate-600 ring-slate-500/20',
];

/** Couleurs des pastilles de priorité (seules « haute » et « urgente » sont mises en avant). */
const COULEURS_PRIORITE = [
    'haute'   => 'bg-orange-50 text-orange-700 ring-orange-600/20',
    'urgente' => 'bg-red-50 text-red-700 ring-red-600/20',
];

/** Étapes de la frise d'avancement, et étape atteinte pour chaque statut. */
const ETAPES_DEMANDE = ['Envoyée', 'Analysée', 'En cours', 'Résolue'];
const ETAPE_PAR_STATUT = [
    'nouvelle' => 1, 'analyse_ia' => 2, 'en_cours' => 3, 'en_attente' => 3, 'resolue' => 4, 'cloturee' => 4,
];

function pastille(string $texte, string $couleurs): string
{
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $couleurs . '">'
        . echapper($texte) . '</span>';
}

function htmlStatsCitoyen(array $stats): string
{
    $cartes = [
        'nouvelle'   => ['Nouvelles demandes', 'boite-reception', 'bg-blue-50 text-blue-700'],
        'analyse_ia' => ["Analysées par l'IA", 'assistant', 'bg-violet-50 text-violet-700'],
        'en_cours'   => ['En cours', 'horloge', 'bg-amber-50 text-amber-700'],
        'resolue'    => ['Résolues', 'valide', 'bg-emerald-50 text-emerald-700'],
    ];
    $html = '';
    foreach ($cartes as $cle => [$titre, $nomIcone, $couleurs]) {
        $html .= '<div class="flex items-center gap-4 rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-900/5">'
            . '<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl ' . $couleurs . '">' . icone($nomIcone) . '</span>'
            . '<div class="flex flex-col">'
            . '<span class="text-2xl font-bold leading-tight text-slate-900">' . (int) ($stats[$cle] ?? 0) . '</span>'
            . '<span class="text-sm text-slate-500">' . echapper($titre) . '</span>'
            . '</div></div>';
    }
    return $html;
}

/**
 * Frise d'avancement : 4 étapes, les étapes atteintes en bleu.
 * $surFonce : version blanche pour un fond foncé (bandeau en dégradé de la page détail).
 */
function friseAvancement(string $statut, bool $surFonce = false): string
{
    $atteinte = ETAPE_PAR_STATUT[$statut] ?? 1;
    $total = count(ETAPES_DEMANDE);
    $html = '<ol class="m-0 mt-4 grid list-none grid-cols-4 gap-1 p-0" aria-label="Avancement : étape ' . $atteinte . ' sur ' . $total
        . ' (' . echapper(ETAPES_DEMANDE[$atteinte - 1]) . ')">';
    foreach (ETAPES_DEMANDE as $i => $libelle) {
        $faite = $i < $atteinte;
        $html .= '<li class="flex flex-col gap-1.5">'
            . '<span class="h-1.5 rounded-full ' . ($surFonce ? ($faite ? 'bg-white' : 'bg-white/25') : ($faite ? 'bg-[#003b8e]' : 'bg-slate-200')) . '"></span>'
            . '<span class="text-[11px] font-medium ' . ($surFonce ? ($faite ? 'text-white' : 'text-blue-200/80') : ($faite ? 'text-[#003b8e]' : 'text-slate-400')) . '">' . echapper($libelle) . '</span>'
            . '</li>';
    }
    return $html . '</ol>';
}

function htmlDemandesCitoyen(array $demandes): string
{
    if (!$demandes) {
        return '<div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">'
            . '<span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-500">' . icone('boite-reception') . '</span>'
            . '<p class="m-0 font-semibold text-slate-900">Vous n’avez encore aucune demande</p>'
            . '<p class="m-0 text-sm text-slate-500">Créez votre première demande, la mairie vous répondra ici.</p>'
            . '<a href="nouvelle_demande.php" class="mt-1 inline-flex items-center gap-2 rounded-full bg-[#003b8e] px-4 py-2 text-sm font-semibold text-white no-underline hover:bg-[#002a66]">'
            . icone('plus') . 'Créer une demande</a></div>';
    }

    $html = '';
    foreach ($demandes as $d) {
        $id       = (int) $d['id'];
        $statut   = (string) $d['statut'];
        $libelle  = LIBELLES_STATUT[$statut] ?? $statut;
        $priorite = (string) $d['priorite'];
        $date     = $d['created_at'] ? date('d/m/Y à H\hi', strtotime($d['created_at'])) : 'date inconnue';
        $service  = $d['nom_service'] ? echapper($d['nom_service']) : 'Service à déterminer';
        $nonLus   = (int) ($d['messages_non_lus'] ?? 0);

        $badge = $nonLus > 0
            ? '<a class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#003b8e] px-3 py-1 text-xs font-semibold text-white no-underline hover:bg-[#002a66]" href="details_demande.php?id=' . $id . '#messagerie-citoyen">'
              . icone('courrier') . ($nonLus > 1 ? $nonLus . ' nouveaux messages' : 'Nouveau message') . ' de la mairie</a>'
            : '';

        $pastilles = pastille($libelle, COULEURS_STATUT[$statut] ?? COULEURS_STATUT['cloturee'])
            . (isset(COULEURS_PRIORITE[$priorite]) ? pastille('Priorité ' . (LIBELLES_PRIORITE[$priorite] ?? $priorite), COULEURS_PRIORITE[$priorite]) : '');

        // Une nouvelle réponse de la mairie : la carte est entourée de bleu
        $cadre = $nonLus > 0 ? 'ring-2 ring-[#003b8e]/60' : 'ring-1 ring-slate-900/5';

        $html .= '<article class="carte-demande relative rounded-2xl bg-white p-5 text-left shadow-sm transition hover:shadow-md ' . $cadre . '" data-id="' . $id . '">'
            . '<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">'
            . '<div class="flex min-w-0 flex-col gap-2">'
            . $badge
            . '<div class="flex flex-wrap items-center gap-2">'
            . '<h3 class="m-0 text-base font-bold text-slate-900">' . echapper($d['type'], 'Demande') . '</h3>'
            . '<span class="text-xs font-medium text-slate-400">N° ' . $id . '</span>'
            . $pastilles . '</div>'
            . '<p class="m-0 line-clamp-2 text-sm text-slate-600" dir="auto">' . echapper($d['message'], '') . '</p>'
            . '<div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">'
            . '<span class="inline-flex items-center gap-1">' . icone('horloge') . echapper($date) . '</span>'
            . '<span class="inline-flex items-center gap-1">' . icone('mairie') . $service . '</span>'
            . '</div></div>'
            . '<a href="details_demande.php?id=' . $id . '" class="inline-flex shrink-0 items-center gap-1 self-start rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:border-[#003b8e] hover:text-[#003b8e]">'
            . 'Voir les détails' . icone('chevron-droite') . '</a>'
            . '</div>'
            . friseAvancement($statut)
            . '</article>';
    }
    return $html;
}
