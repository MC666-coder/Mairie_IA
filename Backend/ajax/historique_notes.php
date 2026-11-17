<?php
/* =========================================================
   Historique des actions + notes internes d'une demande (AJAX)
   ---------------------------------------------------------
   GET, lecture seule, réservé aux agents connectés. Appelé après chaque
   action (statut, assignation, note, pièce jointe, réponse) pour
   réafficher l'historique et les notes tels qu'enregistrés en base,
   avec le même gabarit que la page gestion_demande.php.
   ========================================================= */
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
exiger_agent();
session_write_close();   // lecture seule : on ne bloque pas les autres requêtes

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Fonctions/historique_demande.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$demandeId = filter_input(INPUT_GET, 'demande_id', FILTER_VALIDATE_INT);
if (!$demandeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Identifiant de demande invalide.']);
    exit;
}

try {
    $historique = historiqueDemande($pdo, $demandeId);
    $notes      = notesDemande($pdo, $demandeId);
} catch (PDOException $e) {
    error_log("historique_notes : lecture impossible");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Historique momentanément indisponible.']);
    exit;
}

echo json_encode([
    'success'    => true,
    'historique' => htmlHistorique($historique),
    'notes'      => htmlNotes($notes),
], JSON_UNESCAPED_UNICODE);
