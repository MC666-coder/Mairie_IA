-- =========================================================
-- Migration 004 : purge des conversations du chatbot citoyen (RGPD)
-- ---------------------------------------------------------
-- Depuis cette version, les échanges entre un citoyen et l'assistant
-- d'accueil ne sont plus enregistrés en base : le fil de discussion
-- vit uniquement dans la session. Seule la demande que le citoyen
-- confirme lui-même est conservée (table demandes).
-- Cette migration supprime les conversations enregistrées par les
-- versions précédentes. Idempotente.
--
-- La table est conservée (vide) pour ne rien casser ; elle pourra être
-- supprimée plus tard avec : DROP TABLE ai_conversations;
-- =========================================================

TRUNCATE TABLE ai_conversations;
