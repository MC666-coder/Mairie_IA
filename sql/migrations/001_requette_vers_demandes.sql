-- =====================================================================
-- Migration 001 : table `requette` -> table `demandes`
-- ---------------------------------------------------------------------
-- Problème corrigé : le portail citoyen écrivait dans `requette` alors
-- que le portail agent lit `demandes`. Les demandes des citoyens
-- n'arrivaient donc jamais chez les agents.
--
-- Ce script :
--   1. ajoute la colonne ai_decisions.demande_id
--   2. COPIE les demandes de `requette` vers `demandes`
--   3. relie les anciennes décisions IA aux nouvelles demandes
--
-- Sans risque : rien n'est supprimé, et le script peut être relancé
-- sans créer de doublons. La table `requette` reste en place.
--
-- Utilisation : phpMyAdmin > base mairie_ia > onglet SQL > coller > Exécuter
-- =====================================================================

-- 1. ai_decisions.demande_id (ajoutée seulement si elle n'existe pas)
SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'ai_decisions'
                  AND column_name = 'demande_id');
SET @sql := IF(@existe = 0,
    'ALTER TABLE ai_decisions ADD COLUMN demande_id INT NULL AFTER requette_id, ADD KEY demande_id (demande_id)',
    'SELECT ''colonne demande_id déjà présente'' AS info');
PREPARE etape FROM @sql;
EXECUTE etape;
DEALLOCATE PREPARE etape;

-- 2. Copie des demandes (ignore celles déjà copiées)
INSERT INTO demandes (citoyen_id, service_id, type_id, message, priorite, statut, canal, created_at)
SELECT
    r.citoyens_id,
    r.services_id,
    (SELECT t.id FROM types_demandes t WHERE t.code = r.type),
    COALESCE(r.message, ''),
    -- priorité vide ou inconnue (ex. ancienne valeur « moyenne ») -> normale
    IF(r.priorite IN ('basse', 'normale', 'haute'), r.priorite, 'normale'),
    COALESCE(NULLIF(r.statut, ''), 'nouvelle'),
    -- les demandes créées par l'assistant IA étaient au statut analyse_ia
    IF(r.statut = 'analyse_ia', 'ia', 'formulaire'),
    r.created_at
FROM requette r
WHERE r.citoyens_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM demandes d
      WHERE d.citoyen_id = r.citoyens_id
        AND d.created_at <=> r.created_at
        AND d.message = COALESCE(r.message, '')
  )
ORDER BY r.id;

-- 3. Rattache les anciennes décisions IA aux demandes copiées
UPDATE ai_decisions ad
JOIN requette r ON r.id = ad.requette_id
JOIN demandes d ON d.citoyen_id = r.citoyens_id
               AND d.created_at <=> r.created_at
               AND d.message = COALESCE(r.message, '')
SET ad.demande_id = d.id
WHERE ad.demande_id IS NULL;

-- Vérification
SELECT
    (SELECT COUNT(*) FROM requette WHERE citoyens_id IS NOT NULL) AS demandes_dans_requette,
    (SELECT COUNT(*) FROM requette r WHERE r.citoyens_id IS NOT NULL AND EXISTS (
        SELECT 1 FROM demandes d WHERE d.citoyen_id = r.citoyens_id
          AND d.created_at <=> r.created_at AND d.message = COALESCE(r.message, ''))) AS deja_presentes_dans_demandes;
