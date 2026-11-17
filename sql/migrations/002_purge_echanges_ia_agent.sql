-- =========================================================
-- Migration 002 : purge des échanges avec l'IA agent (RGPD)
-- ---------------------------------------------------------
-- Depuis cette version, les échanges entre un agent et l'IA ne sont
-- plus enregistrés (minimisation des données : ils pouvaient contenir
-- des noms, coordonnées ou données de santé de citoyens).
-- Cette migration supprime ceux enregistrés par les versions
-- précédentes. Elle est idempotente : la relancer ne fait rien de plus.
--
-- La table est conservée (vide) pour ne rien casser ; elle pourra être
-- supprimée plus tard avec : DROP TABLE ai_conversations_agents;
-- =========================================================

TRUNCATE TABLE ai_conversations_agents;
