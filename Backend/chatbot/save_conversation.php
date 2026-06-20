<?php

function saveConversation(PDO $pdo, int $citoyenId, string $message, array $IA): bool
{
    try {

        /* ---------------------------------------
           Validation stricte des données
        ---------------------------------------- */

        // Message utilisateur
        $message = trim($message);
        if ($message === '' || strlen($message) > 5000) {
            throw new Exception("Message utilisateur invalide.");
        }

        // Réponse IA
        $ia_response = trim($IA['response'] ?? '');
        if ($ia_response === '' || strlen($ia_response) > 5000) {
            $ia_response = "Réponse IA indisponible.";
        }

        // Intent IA
        $intent = trim($IA['intent'] ?? 'inconnu');
        if ($intent === '') {
            $intent = 'inconnu';
        }

        // Confiance IA
        $confidence = floatval($IA['confidence'] ?? 0);
        if ($confidence < 0 || $confidence > 1) {
            $confidence = 0;
        }

        /* ---------------------------------------
           Préparation requête SQL sécurisée
        ---------------------------------------- */

        $stmt = $pdo->prepare("
            INSERT INTO ai_conversations 
            (citoyens_id, user_message, ia_reponse, intent, confidence, created_at)
            VALUES (:citoyen, :user_msg, :ia_msg, :intent, :confidence, NOW())
        ");

        return $stmt->execute([
            ':citoyen'    => $citoyenId,
            ':user_msg'   => $message,
            ':ia_msg'     => $ia_response,
            ':intent'     => $intent,
            ':confidence' => $confidence
        ]);

    } catch (Exception $e) {
        error_log("Erreur saveConversation : " . $e->getMessage());
        return false;
    }
}
?>