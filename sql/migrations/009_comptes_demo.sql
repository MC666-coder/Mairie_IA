-- =========================================================
-- Migration 009 : comptes de DÉMONSTRATION (fictifs)
-- ---------------------------------------------------------
--   Agent   : demo.agent@exemple.fr   / DemoAgent2026!
--   Citoyen : demo.citoyen@exemple.fr / DemoCitoyen2026!
-- Les mêmes comptes que dans sql/mairie_ia_demo.sql, pour une base
-- existante. Idempotente : crée les comptes s'ils manquent, sinon remet
-- le mot de passe de démo et réactive le compte agent.
-- =========================================================

SET @service = (SELECT MIN(id) FROM services);
SET @hash_agent   = '$2y$10$n06O1591x/DqdWOMeD4HGur2SwFwXJdXj3SYFpq2yu6w1GCsuLKBu';
SET @hash_citoyen = '$2y$10$CWcos9yDyS3iV7p9xjnnv.7jW/U.0hX3iYP0n8xY/ZB5zNuN93q.q';

INSERT INTO agents (nom, prenom, email, mot_de_passe, doit_definir_mot_de_passe,
                    service_id, role, niveau_acces, statut, identifiant_agent)
SELECT 'Démo', 'Agent', 'demo.agent@exemple.fr', @hash_agent, 0,
       @service, 'responsable', 'admin', 'actif', 'AGT-DEMO-000001'
WHERE NOT EXISTS (SELECT 1 FROM agents WHERE email = 'demo.agent@exemple.fr');

UPDATE agents
SET mot_de_passe = @hash_agent, doit_definir_mot_de_passe = 0, statut = 'actif'
WHERE email = 'demo.agent@exemple.fr';

INSERT INTO citoyens (nom_citoyen, prenom_citoyen, email_citoyen, telephone_citoyen, password)
SELECT 'Démo', 'Citoyen', 'demo.citoyen@exemple.fr', '0600000000', @hash_citoyen
WHERE NOT EXISTS (SELECT 1 FROM citoyens WHERE email_citoyen = 'demo.citoyen@exemple.fr');

UPDATE citoyens
SET password = @hash_citoyen
WHERE email_citoyen = 'demo.citoyen@exemple.fr';
