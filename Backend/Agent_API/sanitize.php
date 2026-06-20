<?php
// Backend/IA/sanitize.php

/**
 * Nettoie un message agent avant envoi à l'IA.
 * Objectif : RGPD + préserver le sens.
 */
function sanitizeAgentMessage(string $message): string
{
    // 1) Emails -> [email]
    $message = preg_replace(
        '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u',
        '[email]',
        $message
    );

    // 2) Numéros de téléphone FR -> [téléphone]
    $message = preg_replace(
        '/\b0[1-9](?:[\s\.\-]?\d{2}){4}\b/u',
        '[téléphone]',
        $message
    );

    // 3) N° dossier / identifiants numériques longs -> [identifiant]
    $message = preg_replace(
        '/\b\d{6,}\b/u',
        '[identifiant]',
        $message
    );

    // 4) Adresses postales simples (très approximatif) -> [adresse]
    $message = preg_replace(
        '/\b(\d{1,3})\s+(rue|avenue|av\.?|boulevard|bd\.?|impasse|chemin|route)\b[^\n]*/iu',
        '[adresse]',
        $message
    );

    // 5) Noms propres simples -> [nom]
    // On reste volontairement soft : un mot avec majuscule au début
    $message = preg_replace(
        '/\b([A-ZÉÈÊÎÏÂÀÔÙÛÜÇ][a-zéèêëàâäîïôöùûüç\-]{2,})\b/u',
        '[nom]',
        $message
    );

    // 6) Nettoyage espaces multiples
    $message = preg_replace('/\s{2,}/u', ' ', $message);
    $message = trim($message);

    return $message;
}


?>