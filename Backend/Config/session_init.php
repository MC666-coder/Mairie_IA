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
        // Appel AJAX : réponse JSON (une redirection HTML casserait le JavaScript)
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Session expirée, veuillez vous reconnecter.']);
            exit;
        }
        require_once __DIR__ . '/app.php';
        header("Location: " . chemin_app('Agent-prenium/connexion_agent.php?session=expiree'));
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
