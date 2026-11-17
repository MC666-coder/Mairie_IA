<?php
/* =========================================================
   Tableau de bord citoyen : mise à jour automatique (AJAX)
   ---------------------------------------------------------
   GET, lecture seule. Renvoie les statistiques et la liste des demandes
   du citoyen CONNECTÉ (jamais celles d'un autre), en HTML déjà échappé,
   produit par le même gabarit que la page.

   Paramètre facultatif ?signature=... : si les données n'ont pas changé,
   la réponse est minimale ({"modifie": false}) et rien n'est redessiné.
   ========================================================= */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Fonctions/tableau_citoyen.php';

if (!isset($_SESSION['citoyen_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Session expirée, veuillez vous reconnecter.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Méthode non autorisée.']);
    exit;
}

// Lecture seule : on libère tout de suite la session pour ne pas
// bloquer les autres requêtes du citoyen (chatbot) pendant ce temps.
$citoyenId = (int) $_SESSION['citoyen_id'];
session_write_close();

try {
    $donnees = donneesTableauCitoyen($pdo, $citoyenId);
} catch (PDOException $e) {
    error_log("tableau_de_bord citoyen : lecture impossible");
    http_response_code(500);
    echo json_encode(['message' => 'Tableau de bord momentanément indisponible.']);
    exit;
}

$signature = signatureTableauCitoyen($donnees);
if (hash_equals($signature, (string) ($_GET['signature'] ?? ''))) {
    echo json_encode(['modifie' => false]);
    exit;
}

echo json_encode([
    'modifie'     => true,
    'signature'   => $signature,
    'stats'       => htmlStatsCitoyen($donnees['stats']),
    'demandes'    => htmlDemandesCitoyen($donnees['demandes']),
    'nb_demandes' => count($donnees['demandes']),
    'ids'         => array_map(fn($d) => (int) $d['id'], $donnees['demandes']),
], JSON_UNESCAPED_UNICODE);
