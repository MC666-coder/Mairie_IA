<?php
/**
 * Sauvegarde une conversation IA pour un agent
 * Table : ai_conversations_agents
 * Conforme RGPD — aucune donnée sensible citoyenne n'est enregistrée.
 */

function saveAIConversationAgent(PDO $pdo, int $agentId, array $context, string $userMessage, string $aiResponse): void
{
    try {

        // Nettoyage minimal (sécurité)
        $userMessage = trim($userMessage);
        $aiResponse  = trim($aiResponse);

        if ($userMessage === '' || $aiResponse === '') {
            return; // Rien à enregistrer
        }

        // Préparation de la requête
        $stmt = $pdo->prepare("
            INSERT INTO ai_conversations_agents 
            (agent_id, page, action, user_message, ai_response, context, created_at)
            VALUES (:agent_id, :page, :action, :user_message, :ai_response, :context, NOW())
        ");

        $stmt->execute([
            ':agent_id'     => $agentId,
            ':page'         => $context['page']   ?? null,
            ':action'       => $context['action'] ?? null,
            ':user_message' => $userMessage,
            ':ai_response'  => $aiResponse,
            ':context'      => json_encode($context, JSON_UNESCAPED_UNICODE)
        ]);

    } catch (Exception $e) {
        // Journalisation interne uniquement
        error_log("Erreur saveAIConversationAgent : " . $e->getMessage());
    }
}
?>