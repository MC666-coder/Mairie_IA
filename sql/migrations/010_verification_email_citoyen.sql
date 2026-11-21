-- =========================================================
-- Migration 010 : confirmation de l'adresse e-mail + mot de passe oublié (citoyens)
-- ---------------------------------------------------------
-- À l'inscription, le citoyen reçoit un lien valable 24 h ; il ne peut
-- se connecter qu'après avoir cliqué dessus (Frontend/verifier_email.php).
--   email_verifie           : 1 = adresse confirmée
--   token_verification      : empreinte SHA-256 du jeton envoyé par e-mail
--                             (le jeton lui-même n'est jamais stocké)
--   verification_expiration : fin de validité du lien
-- DEFAULT 1 : les comptes DÉJÀ existants restent utilisables ;
-- seule l'inscription en ligne crée des comptes à confirmer (email_verifie = 0).
-- Relançable sans risque (colonnes ajoutées seulement si absentes).
-- =========================================================

SET @base = DATABASE();

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'email_verifie') = 0,
    'ALTER TABLE citoyens ADD COLUMN email_verifie TINYINT(1) NOT NULL DEFAULT 1 AFTER password',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'token_verification') = 0,
    'ALTER TABLE citoyens ADD COLUMN token_verification CHAR(64) NULL DEFAULT NULL AFTER email_verifie',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'verification_expiration') = 0,
    'ALTER TABLE citoyens ADD COLUMN verification_expiration DATETIME NULL DEFAULT NULL AFTER token_verification',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

-- Mot de passe oublié (citoyens) : même principe, lien valable 1 h
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'token_reset') = 0,
    'ALTER TABLE citoyens ADD COLUMN token_reset CHAR(64) NULL DEFAULT NULL AFTER verification_expiration',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'reset_expiration') = 0,
    'ALTER TABLE citoyens ADD COLUMN reset_expiration DATETIME NULL DEFAULT NULL AFTER token_reset',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND INDEX_NAME = 'token_verification') = 0,
    'ALTER TABLE citoyens ADD INDEX token_verification (token_verification)',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;
