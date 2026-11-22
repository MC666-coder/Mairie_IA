<?php
/* =========================================================
   Pages réservées aux citoyens connectés
   ---------------------------------------------------------
   Session absente ou expirée : au lieu d'une page blanche, retour à la
   connexion avec le message « Votre session a expiré » ; après la
   reconnexion, le citoyen revient sur la page qu'il voulait voir.
   Usage, en haut d'une page (après session_start()) :
       $citoyenId = exigerCitoyen();
   ========================================================= */

/** Page de retour acceptée : un fichier .php du dossier Frontend (jamais une adresse extérieure).
 *  Nom de fichier : lettres, « _ » et « - » (ex. Jeu-enigme.php) ; jamais de « / », « : » ou « .. ». */
function retourCitoyenValide(?string $retour): ?string
{
    if ($retour === null || !preg_match('/^[A-Za-z_-]+\.php(\?[A-Za-z0-9_=&%-]*)?$/', $retour)) {
        return null;
    }
    $fichier = strtok($retour, '?');
    return is_file(__DIR__ . '/../../Frontend/' . $fichier) ? $retour : null;
}

/** Adresse de la page de connexion avec le message « session expirée » et la page de retour. */
function urlReconnexionCitoyen(string $retour = ''): string
{
    $url = 'connexion_citoyen.php?session=expiree';
    if (retourCitoyenValide($retour) !== null) {
        $url .= '&retour=' . rawurlencode($retour);
    }
    return $url;
}

/** Vérifie la connexion du citoyen ; sinon, redirige vers la connexion (ou JSON 401 pour un appel AJAX). */
function exigerCitoyen(?string $retour = null): int
{
    if (!empty($_SESSION['citoyen_id']) && !empty($_SESSION['email_citoyen'])) {
        return (int) $_SESSION['citoyen_id'];
    }

    $ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($ajax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Votre session a expiré. Reconnectez-vous.']);
        exit;
    }

    // Par défaut : revenir sur la page demandée (pour une requête GET uniquement)
    if ($retour === null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $retour = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '')
            . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        $retour = rawurldecode($retour);
    }
    header('Location: ' . urlReconnexionCitoyen((string) $retour));
    exit;
}
