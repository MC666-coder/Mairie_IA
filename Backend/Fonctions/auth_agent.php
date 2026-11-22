<?php
/* =========================================================
   PAGES DE CONNEXION DU PORTAIL AGENT (style « écran partagé »)
   ---------------------------------------------------------
   connexion, mot de passe oublié, activation, nouveau mot de passe,
   pages de confirmation et pages d'erreur (lien expiré…).
   Feuille de style : Agent-prenium/assets/css/agent.css (classe .auth-agent).
   Les champs viennent de formulaire_auth.php (champAuth…), habillés
   pour le portail agent ; les scripts du mot de passe restent les mêmes.

   Usage :
       <?= debutAuthAgent('Connexion') ?>
           … formulaire …
       <?= finAuthAgent(['mot_de_passe_visible.js']) ?>

       pageMessageAgent(...)   page complète (succès ou erreur), puis exit
   ========================================================= */

require_once __DIR__ . '/icones.php';
require_once __DIR__ . '/formulaire_auth.php';

/** Début de page : en-tête HTML, panneau de présentation à gauche, colonne du formulaire à droite. */
function debutAuthAgent(string $titreOnglet): string
{
    $css   = 'assets/css/agent.css?v=' . @filemtime(__DIR__ . '/../../Agent-prenium/assets/css/agent.css');
    $titre = htmlspecialchars($titreOnglet, ENT_QUOTES, 'UTF-8');
    $logo  = icone('mairie');
    $coche = icone('valide');

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titre} - Espace agent</title>
    <link rel="stylesheet" href="{$css}">
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';
                   script-src 'self'; object-src 'none'; base-uri 'self';">
</head>
<body class="auth-agent">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

    <!-- PANNEAU DE PRÉSENTATION (grand écran) -->
    <aside class="relative hidden flex-col justify-between overflow-hidden bg-encre-900 p-12 text-encre-300 lg:flex">
        <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-marque-500/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-16 size-96 rounded-full bg-marque-600/10 blur-3xl"></div>

        <p class="relative flex items-center gap-3 text-sm font-semibold text-white">
            <span class="grid size-10 place-items-center rounded-xl bg-marque-500/15 text-xl text-marque-400">{$logo}</span>
            Mairie · Espace agent
        </p>

        <div class="relative max-w-md">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-white xl:text-4xl">
                Traitez les demandes des citoyens, au même endroit.
            </h1>
            <ul class="mt-8 grid gap-3 text-sm">
                <li class="flex items-center gap-3"><span class="text-marque-400">{$coche}</span> Demandes, conversations et documents</li>
                <li class="flex items-center gap-3"><span class="text-marque-400">{$coche}</span> Assistant IA pour orienter vers le bon service</li>
                <li class="flex items-center gap-3"><span class="text-marque-400">{$coche}</span> Accès sécurisé, réservé au personnel</li>
            </ul>
        </div>

        <p class="relative text-xs text-encre-500">Espace réservé aux agents de la mairie.</p>
    </aside>

    <!-- COLONNE DU FORMULAIRE -->
    <main class="flex flex-col items-center justify-center bg-white px-5 py-10 sm:px-10">
        <div class="w-full max-w-md">
            <!-- Logo (petit écran, le panneau de gauche est masqué) -->
            <p class="mb-8 flex items-center gap-2.5 text-sm font-bold text-encre-900 lg:hidden">
                <span class="grid size-9 place-items-center rounded-xl bg-encre-900 text-lg text-marque-400">{$logo}</span>
                Mairie · Espace agent
            </p>
HTML;
}

/** Fin de page ; $scripts : fichiers de Frontend/assets/js à charger (mot_de_passe_visible.js…). */
function finAuthAgent(array $scripts = []): string
{
    $html = "        </div>\n    </main>\n</div>\n";
    foreach ($scripts as $s) {
        $s = basename($s);
        $html .= '<script src="../Frontend/assets/js/' . $s . '?v=' . @filemtime(__DIR__ . '/../../Frontend/assets/js/' . $s) . '" defer></script>' . "\n";
    }
    return $html . "</body>\n</html>\n";
}

/**
 * Page complète de message (confirmation ou erreur) au même style, puis arrêt du script.
 * Remplace les anciennes pages blanches (die("Lien invalide.")…).
 * $type : 'succes' (vert), 'info' (bleu ardoise) ou 'erreur' (rouge).
 */
function pageMessageAgent(string $titre, string $texte, string $lien, string $texteLien,
                          string $type = 'succes', string $lienSecondaire = '', string $texteSecondaire = ''): never
{
    $styles = [
        'succes' => ['valide', 'bg-marque-50 text-marque-600 ring-marque-600/20'],
        'info'   => ['courrier', 'bg-encre-100 text-encre-700 ring-encre-500/20'],
        'erreur' => ['alerte', 'bg-red-50 text-red-600 ring-red-600/20'],
    ];
    [$nomIcone, $couleurs] = $styles[$type] ?? $styles['info'];
    $e = fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    if ($type === 'erreur') {
        http_response_code(400);
    }

    echo debutAuthAgent($titre);
    echo '<div class="text-center sm:text-left">'
        . '<span class="mx-auto grid size-14 place-items-center rounded-2xl text-2xl ring-1 sm:mx-0 ' . $couleurs . '">' . icone($nomIcone) . '</span>'
        . '<h2 class="mt-6 text-2xl font-extrabold tracking-tight text-encre-900">' . $e($titre) . '</h2>'
        . '<p class="mt-3 text-encre-600">' . nl2br($e($texte)) . '</p>'
        . '<div class="mt-8 flex flex-col gap-3 sm:flex-row">'
        . '<a href="' . $e($lien) . '" class="btn-submit inline-flex items-center justify-center gap-2 no-underline">' . $e($texteLien) . ' ' . icone('fleche-droite') . '</a>'
        . ($lienSecondaire !== ''
            ? '<a href="' . $e($lienSecondaire) . '" class="inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-encre-600 hover:bg-encre-100">' . $e($texteSecondaire) . '</a>'
            : '')
        . '</div></div>';
    echo finAuthAgent();
    exit;
}
