<?php
/* =========================================================
   Comparaison Gemini / Claude sur l'IA agent (ligne de commande)
   ---------------------------------------------------------
   Usage (depuis la racine du projet) :
     php tests/ia/comparer_ia.php
     php tests/ia/comparer_ia.php --fournisseurs=claude --cas=1,5

   ⚠️ Chaque lancement appelle les API payantes (quelques centimes).
   Le rapport est écrit HORS du dossier du projet (variable RAPPORTS_IA_DIR
   dans .env, sinon le dossier personnel de l'utilisateur).
   ========================================================= */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script réservé à la ligne de commande.\n");
}

require_once __DIR__ . '/../../Backend/Agent_API/gemini.php';

// ---- Options -------------------------------------------------------------
$options = getopt('', ['fournisseurs::', 'cas::']);
$fournisseurs = array_filter(explode(',', $options['fournisseurs'] ?? 'gemini,claude'));
$filtreCas = isset($options['cas']) ? array_map('intval', explode(',', $options['cas'])) : null;

$cas = require __DIR__ . '/demandes_test.php';
if ($filtreCas) {
    $cas = array_values(array_filter($cas, fn($c) => in_array($c['id'], $filtreCas, true)));
}

// ---- Exécution -----------------------------------------------------------
$resultats = [];
foreach ($cas as $c) {
    foreach ($fournisseurs as $f) {
        $_ENV['IA_FOURNISSEUR'] = $f;
        $contexte = [
            'action' => $c['action'],
            'agent'  => ['id' => 0, 'role' => 'agent', 'service' => $c['service']],
        ];
        echo "Cas {$c['id']} - $f ... ";
        $debut = microtime(true);
        $reponse = callAgentAI($c['message'], $contexte);
        $duree = round(microtime(true) - $debut, 1);
        echo ($reponse === null ? "ÉCHEC" : "ok") . " ({$duree} s)\n";
        $resultats[$c['id']][$f] = ['reponse' => $reponse, 'duree' => $duree];
    }
}

// ---- Rapport Markdown ------------------------------------------------------
$md  = "# Comparaison IA agent : " . implode(' vs ', array_map('ucfirst', $fournisseurs)) . "\n\n";
$md .= "Date : " . date('d/m/Y H:i') . "  \n";
$md .= "Modèles : Gemini `" . ($_ENV['GEMINI_MODELE'] ?? 'gemini-3.8-flash') . "` · Claude `"
     . ($_ENV['CLAUDE_MODELE'] ?? 'claude-opus-5-5') . "` (effort " . ($_ENV['CLAUDE_EFFORT'] ?? 'high') . ")\n\n";
$md .= "**Comment noter :** pour chaque cas, cochez les critères respectés par chaque IA (1 point par critère), "
     . "puis reportez les totaux dans le tableau final. Données de test entièrement fictives.\n\n";

foreach ($cas as $c) {
    $md .= "---\n\n## Cas {$c['id']} : {$c['titre']}\n\n";
    $md .= "*Mode : `{$c['action']}` · Service de l'agent : `{$c['service']}`*\n\n";
    $md .= "> " . str_replace("\n", "\n> ", $c['message']) . "\n\n";
    $md .= "**Critères attendus :**\n\n";
    foreach ($c['criteres'] as $crit) {
        $md .= "- " . implode(' ', array_map(fn($f) => "[ ] " . ucfirst($f), $fournisseurs)) . " : $crit\n";
    }
    foreach ($fournisseurs as $f) {
        $r = $resultats[$c['id']][$f];
        $md .= "\n### Réponse " . ucfirst($f) . " ({$r['duree']} s)\n\n";
        $md .= $r['reponse'] ?? "*Aucune réponse : voir Backend/Agent_API/ia_agent_debug.log*";
        $md .= "\n";
    }
    $md .= "\n";
}

$md .= "---\n\n## Tableau des scores\n\n| Cas | " . implode(' | ', array_map('ucfirst', $fournisseurs)) . " |\n|---|"
     . str_repeat('---|', count($fournisseurs)) . "\n";
foreach ($cas as $c) {
    $md .= "| {$c['id']}. {$c['titre']} (/" . count($c['criteres']) . ") |" . str_repeat('   |', count($fournisseurs)) . "\n";
}
$md .= "| **Total** |" . str_repeat('   |', count($fournisseurs)) . "\n\n";
$md .= "Temps de réponse moyen : ";
foreach ($fournisseurs as $f) {
    $durees = array_column(array_map(fn($id) => $resultats[$id][$f], array_keys($resultats)), 'duree');
    $md .= ucfirst($f) . " " . round(array_sum($durees) / max(count($durees), 1), 1) . " s  ";
}
$md .= "\n";

$dossier = $_ENV['RAPPORTS_IA_DIR'] ?? (getenv('USERPROFILE') ?: getenv('HOME')) . '/Mairie_IA_rapports';
if (!is_dir($dossier)) {
    mkdir($dossier, 0750, true);
}
$fichier = $dossier . '/comparaison_ia_' . date('Y-m-d_Hi') . '.md';
file_put_contents($fichier, $md);
echo "\nRapport : $fichier\n";
