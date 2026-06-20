<?php
session_start();

/* ---------------------------------------
   Sécurisation renforcée de la déconnexion
---------------------------------------- */

// Suppression des variables de session
$_SESSION = [];

// Suppression du cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruction de la session
session_destroy();

// Empêche le retour arrière du navigateur
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Regénération d’un nouvel ID de session vide (anti fixation)
session_start();
session_regenerate_id(true);

// Redirection propre
header("Location: index.php");
exit();
