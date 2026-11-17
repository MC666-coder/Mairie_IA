<?php
/* =========================================================
   Configuration de l'application (lue depuis le fichier .env)
   ---------------------------------------------------------
   Une seule source de vérité pour l'adresse du site, l'email
   d'expédition et le nom de la commune : le même code tourne
   en local (WampServer) et en ligne sans modification.

   require_once __DIR__ . '/../Backend/Config/app.php';
   url_app('Agent-prenium/activation.php')  // URL absolue (emails)
   chemin_app('Agent-prenium/connexion_agent.php') // chemin (redirections)
   ========================================================= */

require_once __DIR__ . '/env.php';

// Valeurs par défaut = installation locale WampServer
define('APP_URL',        rtrim($_ENV['APP_URL'] ?? 'http://localhost/Site_officiel', '/'));
define('MAIL_FROM',      $_ENV['MAIL_FROM'] ?? 'no-reply@localhost');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Mairie');
define('NOM_COMMUNE',    $_ENV['NOM_COMMUNE'] ?? 'Ma Commune');

/**
 * URL absolue vers une page du site (pour les liens envoyés par email).
 */
function url_app(string $chemin = ''): string
{
    return APP_URL . '/' . ltrim($chemin, '/');
}

/**
 * Chemin depuis la racine du serveur (pour les redirections internes).
 * Ex. APP_URL = http://localhost/Site_officiel -> /Site_officiel/...
 */
function chemin_app(string $chemin = ''): string
{
    $base = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
    return $base . '/' . ltrim($chemin, '/');
}

/**
 * Écrit une ligne dans un journal de sécurité (Agent-prenium/logs/).
 * Ne jamais y mettre de mot de passe, jeton ou donnée personnelle sensible.
 */
function journal_securite(string $fichier, string $message): void
{
    $dossier = __DIR__ . '/../../Agent-prenium/logs';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0750, true);
    }
    file_put_contents(
        $dossier . '/' . basename($fichier),
        date('Y-m-d H:i:s') . ' - ' . $message . "\n",
        FILE_APPEND | LOCK_EX
    );
}

/**
 * En-têtes d'un email texte envoyé par la mairie.
 */
function entetes_email(): string
{
    // Le nom est encodé pour supporter les accents (RFC 2047)
    $nom = mb_encode_mimeheader(MAIL_FROM_NAME, 'UTF-8');
    return "From: {$nom} <" . MAIL_FROM . ">\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";
}
