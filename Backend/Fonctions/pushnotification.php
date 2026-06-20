<?php
function pushNotifications($db , $citoyen_id, $demande_id, $type, $contenu){
    // type autorisés
    $types_autorises = ['statut', 'message', 'document'];
    if (!in_array($type, $types_autorises)){
        throw new Exception("Type de notification non autorisé : " . $type);

    }
    // insertion de la notification 
    $sql = "INSERT INTO notifications (citoyen_id, demande_id, type, contenu) VALUES (:citoyen_id, :demande_id, :type, :contenu)";
    $stmt = $db->prepare($sql);

    $stmt->execute([
        ':citoyen_id' => $citoyen_id,
        ':demande_id' => $demande_id,
        ':type' => $type,
        ':contenu' => $contenu
    ]);

    return $db->lastInsertId();
}

?>