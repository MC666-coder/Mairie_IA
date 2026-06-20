<?php

require_once __DIR__ . '/env.php';

/* ---------------------------------------
   Configuration base de données
---------------------------------------- */

$host   = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'mairie_ia';
$user   = $_ENV['DB_USER'] ?? 'root';
$pass   = $_ENV['DB_PASS'] ?? '';

/* ---------------------------------------
   Options PDO sécurisées
---------------------------------------- */

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Exceptions → sécurité + debug
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch propre
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Désactive l’émulation → anti-injection
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Encodage complet (emoji, accents)
];

/* ---------------------------------------
   Connexion sécurisée
---------------------------------------- */

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        $options
    );
} catch (PDOException $e) {

    // Journalisation interne (jamais afficher l’erreur brute)
    error_log("Erreur PDO : " . $e->getMessage());

    // Message neutre pour l’utilisateur
    die("Erreur de connexion à la base de données.");
}
?>