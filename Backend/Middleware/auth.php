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
        refuser_acces(401, 'Session expirée, veuillez vous reconnecter.',
            '/Site_officiel/Agent-prenium/connexion_agent.php');
    }
    return (int) $_SESSION['agent_id'];
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
