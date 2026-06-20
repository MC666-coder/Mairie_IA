<?php
require_once __DIR__ . '/../Config/db.php';

function getCitoyenIdFromDemande($db, $demande_id) {
    $sql = "SELECT citoyen_id FROM demandes WHERE id = :demande_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':demande_id' => $demande_id]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("Aucun citoyen trouvé pour la demande ID : " . $demande_id);
    }

    return $result['citoyen_id'];
}

?>