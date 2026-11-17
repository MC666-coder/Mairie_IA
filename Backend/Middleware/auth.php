<?php
/* =========================================================
   Middleware d'authentification — portail agent
   ---------------------------------------------------------
   À inclure APRÈS session_init.php :

     require_once __DIR__ . '/../Backend/Middleware/auth.php';
     exiger_agent();                          // agent connecté
     exiger_niveau(['manager', 'admin']);     // + niveau d'accès
   ========================================================= */

const NIVEAUX_ACCES = ['agent', 'manager', 'admin'];

/**
 * Indique si la requête attend du JSON (appel AJAX) plutôt qu'une page.
 */
function requete_ajax(): bool
{
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

/**
 * Stoppe la requête : JSON pour l'AJAX, redirection ou message sinon.
 */
function refuser_acces(int $code, string $message, ?string $redirection = null): never
{
    http_response_code($code);

    if (requete_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
    } elseif ($redirection !== null) {
        header('Location: ' . $redirection);
    } else {
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }
    exit;
}

/**
 * Exige un agent connecté (session ouverte par traitement_connexion_agent.php).
 */
function exiger_agent(): int
{
    if (empty($_SESSION['agent_id'])) {
        require_once __DIR__ . '/../Config/app.php';
        refuser_acces(401, 'Session expirée, veuillez vous reconnecter.',
            chemin_app('Agent-prenium/connexion_agent.php'));
    }
    return (int) $_SESSION['agent_id'];
}

/**
 * Jeton CSRF des actions agent (créé une fois par session).
 * À placer dans la page : <meta name="csrf-token" content="...">
 */
function jeton_csrf(): string
{
    if (empty($_SESSION['csrf_agent'])) {
        $_SESSION['csrf_agent'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_agent'];
}

/**
 * Vérifie le jeton CSRF sur toute requête qui modifie des données (POST).
 * Le JavaScript l'envoie dans l'en-tête X-CSRF-Token.
 */
function verifier_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $recu = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_agent']) || !is_string($recu)
        || !hash_equals($_SESSION['csrf_agent'], $recu)) {
        refuser_acces(403, 'Requête refusée (jeton de sécurité invalide). Rechargez la page.');
    }
}

/**
 * Exige un agent connecté ayant l'un des niveaux d'accès donnés.
 *
 * @param string[] $niveaux ex. ['manager', 'admin']
 */
function exiger_niveau(array $niveaux): string
{
    exiger_agent();

    $niveau = $_SESSION['niveau_acces'] ?? '';
    if (!in_array($niveau, $niveaux, true)) {
        refuser_acces(403, 'Accès refusé : droits insuffisants.');
    }
    return $niveau;
}
