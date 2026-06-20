<?php
require_once __DIR__ . '/../Config/db.php';

function getEmailCitoyen($db, $citoyen_id) {
    $sql = "SELECT email FROM citoyens WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $citoyen_id]);

    $email = $stmt->fetchColumn();

    if (!$email) {
        throw new Exception("Email introuvable pour le citoyen ID : " . $citoyen_id);
    }

    return $email;
}

?>