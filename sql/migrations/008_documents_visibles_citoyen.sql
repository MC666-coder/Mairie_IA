-- =========================================================
-- Migration 008 : documents échangés dans la conversation
-- ---------------------------------------------------------
-- fichiers_demandes.visible_citoyen : 1 = document envoyé AU citoyen par
--   l'agent (ex. attestation) ; 0 = pièce jointe interne à la mairie.
--   Les documents déposés par le citoyen (agent_id NULL) lui restent
--   toujours visibles.
-- fichiers_demandes.message_id : message de la conversation auquel le
--   document est joint.
-- Idempotente : chaque colonne n'est ajoutée que si elle n'existe pas.
-- =========================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fichiers_demandes'
                  AND COLUMN_NAME = 'visible_citoyen');
SET @sql := IF(@existe = 0,
    'ALTER TABLE fichiers_demandes ADD COLUMN visible_citoyen TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT 1');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fichiers_demandes'
                  AND COLUMN_NAME = 'message_id');
SET @sql := IF(@existe = 0,
    'ALTER TABLE fichiers_demandes ADD COLUMN message_id INT NULL DEFAULT NULL, ADD INDEX idx_fichiers_message (message_id)',
    'SELECT 1');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;
