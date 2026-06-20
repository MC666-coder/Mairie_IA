<?php
require_once __DIR__ . '/../Backend/Config/session_init.php';



/* ---------------------------------------------------------
   2) Suppression du cookie de session
--------------------------------------------------------- */
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

/* ---------------------------------------------------------
   3) Destruction de la session
--------------------------------------------------------- */
session_destroy();

/* ---------------------------------------------------------
   4) Empêcher le retour arrière (cache navigateur)
--------------------------------------------------------- */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* ---------------------------------------------------------
   5) Redirection vers la page de connexion
--------------------------------------------------------- */
header("Location: connexion_agent.php");
exit;
?>
