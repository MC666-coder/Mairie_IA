<?php
/* =========================================================
   Fournisseurs d'IA interchangeables (Gemini ou Claude)
   ---------------------------------------------------------
   Le prompt est construit une seule fois (gemini.php) puis envoyé
   au fournisseur choisi dans .env :

       IA_FOURNISSEUR=gemini   (par défaut)
       IA_FOURNISSEUR=claude   (+ ANTHROPIC_API_KEY, CLAUDE_MODELE optionnel)

   Chaque fournisseur reçoit EXACTEMENT le même texte : on peut ainsi
   les comparer équitablement et en changer sans toucher au reste du code.

   Deux usages partagent cette couche, chacun avec sa propre clé :
     - « agent »   : back-office (GOOGLE_API_KEY_AGENT, GEMINI_MODELE...)
     - « citoyen » : chatbot d'accueil (GOOGLE_API_KEY). Ses réglages
       *_CITOYEN sont optionnels et reprennent sinon ceux de l'agent.
   ========================================================= */

require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;

/**
 * Envoie le prompt au fournisseur configuré et renvoie le texte de la réponse
 * (null en cas d'échec : l'appelant affiche alors « momentanément indisponible »).
 *
 * @param callable $log         journal technique (métadonnées uniquement)
 * @param callable $logContenu  journal du contenu (seulement si DEBUG_IA=true)
 * @param string   $usage       'agent' (par défaut) ou 'citoyen'
 */
function envoyerPromptIA(
    string $prompt,
    callable $log,
    callable $logContenu,
    ?string $fournisseur = null,
    string $usage = 'agent'
): ?string {
    $fournisseur = strtolower($fournisseur ?? reglageIA('IA_FOURNISSEUR', $usage, 'gemini'));

    return match ($fournisseur) {
        'claude' => envoyerClaude($prompt, $log, $logContenu, $usage),
        'gemini' => envoyerGemini($prompt, $log, $logContenu, $usage),
        default  => (function () use ($log, $fournisseur) {
            $log("❌ Fournisseur d'IA inconnu : $fournisseur (attendu : gemini ou claude)");
            return null;
        })(),
    };
}

/**
 * Lit un réglage du .env pour un usage donné : pour le citoyen, on cherche
 * d'abord NOM_CITOYEN (ex. GEMINI_MODELE_CITOYEN), puis NOM, puis la valeur
 * par défaut.
 */
function reglageIA(string $nom, string $usage, ?string $defaut = null): ?string
{
    if ($usage === 'citoyen' && !empty($_ENV[$nom . '_CITOYEN'])) {
        return $_ENV[$nom . '_CITOYEN'];
    }
    return !empty($_ENV[$nom]) ? $_ENV[$nom] : $defaut;
}

/* ---------------------------------------------------------
   Google Gemini (API REST via cURL)
--------------------------------------------------------- */
function envoyerGemini(string $prompt, callable $log, callable $logContenu, string $usage = 'agent'): ?string
{
    // Chaque usage a sa clé : on peut ainsi suivre et limiter sa consommation.
    $nomCle = $usage === 'citoyen' ? 'GOOGLE_API_KEY' : 'GOOGLE_API_KEY_AGENT';
    $cle = $_ENV[$nomCle] ?? null;
    if (!$cle) {
        $log("❌ Clé API Gemini ($nomCle) manquante");
        return null;
    }

    $modele = reglageIA('GEMINI_MODELE', $usage, 'gemini-3.8-flash');
    // Un citoyen attend dans une fenêtre de discussion : délai plus court.
    $delaiMax = $usage === 'citoyen' ? 25 : 60;
    $data = ["contents" => [["role" => "user", "parts" => [["text" => $prompt]]]]];
    $logContenu("Payload envoyé : " . json_encode($data, JSON_UNESCAPED_UNICODE));
    $log("Fournisseur : Gemini ($modele)");

    // Erreurs passagères (serveur saturé, quota par minute) : on réessaie
    // avec un délai croissant. Les autres erreurs sont définitives.
    $erreursPassageres = [429, 500, 503];
    // Secondes avant chaque nouvelle tentative (une seule pour le citoyen,
    // pour ne pas le faire attendre trop longtemps).
    $delais = $usage === 'citoyen' ? [2] : [2, 5];
    $tentative = 0;

    while (true) {
        $tentative++;
        $ch = curl_init("https://generativelanguage.googleapis.com/v1/models/{$modele}:generateContent");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            // Clé dans l'en-tête plutôt que dans l'URL : elle n'apparaît pas
            // dans les journaux de proxy ni dans les messages d'erreur.
            CURLOPT_HTTPHEADER => ["Content-Type: application/json", "x-goog-api-key: $cle"],
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => $delaiMax,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($ch);
        $codeHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $log("❌ Erreur cURL : " . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        $logContenu("Réponse brute Google : " . $response);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $log("❌ Réponse non JSON (HTTP $codeHttp, " . strlen($response) . " octets)");
            return null;
        }
        if (!isset($decoded['error'])) {
            break;
        }

        $code = (int) ($decoded['error']['code'] ?? $codeHttp);
        if (in_array($code, $erreursPassageres, true) && $tentative <= count($delais)) {
            $attente = $delais[$tentative - 1];
            $log("⚠️ Gemini indisponible ($code), nouvelle tentative dans {$attente} s");
            sleep($attente);
            continue;
        }
        $log("❌ Erreur API Google : " . json_encode($decoded['error']));
        return null;
    }

    // La réponse peut arriver en plusieurs morceaux : on les assemble tous
    // (sauf les éventuelles « pensées » internes du modèle).
    $candidat = $decoded['candidates'][0] ?? [];
    $texte = '';
    foreach ($candidat['content']['parts'] ?? [] as $partie) {
        if (empty($partie['thought']) && isset($partie['text'])) {
            $texte .= $partie['text'];
        }
    }
    $fin = $candidat['finishReason'] ?? 'inconnue';
    $log("Fin de réponse Gemini : $fin, tokens : " . json_encode($decoded['usageMetadata']['candidatesTokenCount'] ?? null));
    if ($fin === 'MAX_TOKENS') {
        $log("⚠️ Réponse Gemini tronquée (limite de longueur atteinte)");
    }
    if ($texte === '') {
        $log("❌ Réponse Gemini sans texte");
        return null;
    }
    return trim($texte);
}

/* ---------------------------------------------------------
   Anthropic Claude (SDK PHP officiel : anthropic-ai/sdk)
--------------------------------------------------------- */
function envoyerClaude(string $prompt, callable $log, callable $logContenu, string $usage = 'agent'): ?string
{
    $cle = $_ENV['ANTHROPIC_API_KEY'] ?? null;
    if (!$cle) {
        $log("❌ Clé API Claude (ANTHROPIC_API_KEY) manquante");
        return null;
    }

    $modele = reglageIA('CLAUDE_MODELE', $usage, 'claude-opus-5-5');
    $effort = reglageIA('CLAUDE_EFFORT', $usage, 'high');   // low | medium | high | xhigh | max
    $log("Fournisseur : Claude ($modele, effort $effort)");

    try {
        $client = new Client(apiKey: $cle);

        $message = $client->beta->messages->create(
            model: $modele,
            maxTokens: 16000,
            messages: [['role' => 'user', 'content' => $prompt]],
            outputConfig: ['effort' => $effort],
            // Si le modèle décline une demande pour raison de sécurité, l'API
            // la relance automatiquement sur un modèle de repli adapté.
            betas: ['server-side-fallback-2026-07-01'],
            fallbacks: 'default',
        );
    } catch (AuthenticationException $e) {
        $log("❌ Claude : clé API invalide");
        return null;
    } catch (RateLimitException $e) {
        $log("❌ Claude : limite de requêtes atteinte, réessayer plus tard");
        return null;
    } catch (APIStatusException $e) {
        $log("❌ Claude : erreur API (" . ($e->type?->value ?? 'inconnue') . ")");
        return null;
    } catch (APIConnectionException $e) {
        $log("❌ Claude : connexion impossible à l'API");
        return null;
    }

    if ($message->stopReason === 'refusal') {
        $log("❌ Claude a décliné la demande (" . ($message->stopDetails?->category ?? 'sans catégorie') . ")");
        return null;
    }

    $texte = '';
    foreach ($message->content as $bloc) {
        if ($bloc->type === 'text') {
            $texte .= $bloc->text;
        }
    }
    $log("Claude : " . $message->usage->inputTokens . " tokens envoyés, "
        . $message->usage->outputTokens . " reçus (stop : " . $message->stopReason . ")");
    $logContenu("Réponse Claude : " . $texte);

    return $texte !== '' ? trim($texte) : null;
}
