-- =========================================================
-- Migration 007 : suppression des doublons de l'historique des actions
-- ---------------------------------------------------------
-- Jusqu'à cette version, chaque changement de statut, assignation et note
-- était enregistré DEUX fois (par le serveur ET par le JavaScript via
-- Ajouter_historique.php, aujourd'hui supprimé), et un double clic pouvait
-- en ajouter d'autres.
--
-- Un doublon = même demande, même agent, même action, même détail,
-- à 2 secondes près. Dans chaque série, la PREMIÈRE ligne est gardée.
-- Idempotente : relancée, elle ne supprime plus rien.
-- =========================================================

DELETE h2
FROM historique_demandes h2
JOIN historique_demandes h1
  ON  h1.demande_id = h2.demande_id
  AND h1.agent_id  <=> h2.agent_id
  AND h1.action     = h2.action
  AND h1.details   <=> h2.details
  AND h1.id         < h2.id
  AND ABS(TIMESTAMPDIFF(SECOND, h1.created_at, h2.created_at)) <= 2;
