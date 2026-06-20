<?php
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // Chargement du .env à la racine du projet
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (Exception $e) {
    // Journalisation interne, jamais d'affichage public
    error_log("Erreur chargement .env : " . $e->getMessage());
}

// Sécurisation : récupération propre des variables
global $API_KEY;
global $API_KEY_AGENT;

// On ne fait jamais confiance à $_ENV directement sans fallback
$API_KEY = $_ENV['GOOGLE_API_KEY'] ?? null;
$API_KEY_AGENT = $_ENV['GOOGLE_API_KEY_AGENT'] ?? null;

// Vérification minimale : si la clé est absente, on log mais on ne casse pas l'app
if (!$API_KEY) {
    error_log("⚠️ GOOGLE_API_KEY manquante dans le fichier .env");
}
if (!$API_KEY_AGENT) {
    error_log("⚠️ GOOGLE_API_KEY_AGENT manquante dans le fichier .env");
}
?>