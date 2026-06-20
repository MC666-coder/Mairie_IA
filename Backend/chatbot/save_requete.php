<?php

function saveRequete(PDO $pdo, int $citoyenId, array $IA, string $messageOriginal): ?int
{
    try {

        /* ---------------------------------------
           1. Extraction et normalisation
        ---------------------------------------- */

        // Type
        $type = strtolower(trim($IA['data']['type'] ?? ''));
        $typesValides = ['plainte', 'question', 'suggestion'];

        if (!in_array($type, $typesValides, true)) {
            $type = 'question';
        }

        // Message final
        $messageRequete = trim($IA['data']['message'] ?? $messageOriginal);

        // Sécurité : taille max
        if (strlen($messageRequete) > 5000) {
            $messageRequete = substr($messageRequete, 0, 5000);
        }

        // Priorité
        $priorite = strtolower(trim($IA['data']['priorite'] ?? 'normale'));
        $prioritesValides = ['basse', 'normale', 'haute'];

        if (!in_array($priorite, $prioritesValides, true)) {
            $priorite = 'normale';
        }

        /* ---------------------------------------
           2. Détection intelligente du service
        ---------------------------------------- */

        $stmt = $pdo->query("SELECT id, nom_service, mots_cles FROM services");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $serviceId = null;
        $messageLower = strtolower($messageRequete);

        foreach ($services as $service) {
            $keywords = array_filter(array_map('trim', explode(',', $service['mots_cles'] ?? '')));

            foreach ($keywords as $kw) {
                if ($kw !== '' && stripos($messageLower, strtolower($kw)) !== false) {
                    $serviceId = (int)$service['id'];
                    break 2;
                }
            }
        }

        // Aucun service trouvé → service générique si existant
        if (!$serviceId) {
            $fallback = $pdo->query("SELECT id FROM services ORDER BY id ASC LIMIT 1")->fetchColumn();
            $serviceId = $fallback ? (int)$fallback : 1;
        }

        /* ---------------------------------------
           3. Insertion de la requête
        ---------------------------------------- */

        $stmt = $pdo->prepare("
            INSERT INTO requette 
            (type, services_id, message, priorite, statut, citoyens_id, created_at)
            VALUES (:type, :service, :message, :priorite, 'analyse_ia', :citoyen, NOW())
        ");

        $stmt->execute([
            ':type'     => $type,
            ':service'  => $serviceId,
            ':message'  => $messageRequete,
            ':priorite' => $priorite,
            ':citoyen'  => $citoyenId
        ]);

        $requeteId = (int)$pdo->lastInsertId();

        /* ---------------------------------------
           4. Enregistrement de la décision IA
        ---------------------------------------- */

        $stmt = $pdo->prepare("
            INSERT INTO ai_decisions (requette_id, decision, justification, created_at)
            VALUES (:req, :decision, :justif, NOW())
        ");

        $stmt->execute([
            ':req'      => $requeteId,
            ':decision' => "Création automatique d’une requête par l’IA",
            ':justif'   => json_encode($IA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);

        return $requeteId;

    } catch (Exception $e) {
        error_log("Erreur saveRequete : " . $e->getMessage());
        return null;
    }
}
