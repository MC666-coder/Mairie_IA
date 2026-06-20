<?php
/* ---------------------------------------------------------
   Configuration du cookie de session
   - lifetime = 0 → détruit à la fermeture du navigateur
   - path = '/' → session valable partout
--------------------------------------------------------- */
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/', // adapter selon ton chemin
    'httponly' => true,
    'secure' => false, // mettre true en HTTPS
    'samesite' => 'Lax'
]);

session_start();

/* ---------------------------------------------------------
   Timeout d'inactivité (20 minutes)
--------------------------------------------------------- */
$inactivity_limit = 1200; // 20 minutes

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $inactivity_limit) {
        session_unset();
        session_destroy();
        header("Location: /Site_officiel/Agent-premium/connexion_agent.php");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

/* ---------------------------------------------------------
   Protection contre la fixation de session
--------------------------------------------------------- */
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 300) { // régénération toutes les 5 min
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}
?>
