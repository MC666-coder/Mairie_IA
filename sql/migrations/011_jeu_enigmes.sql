-- =========================================================
-- Migration 011 : jeu d'énigmes du patrimoine (parcours à débloquer)
-- ---------------------------------------------------------
-- enigmes : trois types
--   'texte' : devinette, réponse libre       (ex. « le moulin »)
--   'photo' : deviner une œuvre / un monument d'après une image
--   'date'  : deviner une année              (réponse = nombre)
--   reponse            : réponse affichée une fois l'énigme terminée
--   reponses_acceptees : autres réponses acceptées, séparées par « | »
--                        (la comparaison ignore majuscules, accents et articles)
--   ordre              : place dans le parcours (on débloque dans cet ordre)
-- enigme_scores : progression d'un citoyen sur une énigme
--   statut 'en_cours' | 'resolue' | 'revelee' (réponse demandée : 0 point)
-- citoyens.pseudo_jeu : nom affiché dans le classement (jamais le vrai nom)
-- Relançable sans risque. Les énigmes ajoutées à la fin sont des
-- exemples de DÉMONSTRATION : la mairie les remplace par les siennes.
-- IMPORTANT (accents) : importer en UTF-8, par exemple
--   mysql --default-character-set=utf8mb4 -u root mairie_ia < 011_jeu_enigmes.sql
-- (phpMyAdmin importe en UTF-8 par défaut).
-- =========================================================

SET @base = DATABASE();

-- ---------- enigmes ----------
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'enigmes' AND COLUMN_NAME = 'titre') = 0,
    'ALTER TABLE enigmes
        ADD COLUMN titre VARCHAR(120) NOT NULL DEFAULT '''' AFTER id,
        ADD COLUMN type ENUM(''texte'',''photo'',''date'') NOT NULL DEFAULT ''texte'' AFTER titre,
        ADD COLUMN lieu VARCHAR(120) NULL DEFAULT NULL AFTER type,
        ADD COLUMN image VARCHAR(255) NULL DEFAULT NULL AFTER question,
        ADD COLUMN reponses_acceptees VARCHAR(500) NULL DEFAULT NULL AFTER reponse,
        ADD COLUMN indice VARCHAR(255) NULL DEFAULT NULL AFTER reponses_acceptees,
        ADD COLUMN explication TEXT NULL AFTER indice,
        ADD COLUMN ordre INT NOT NULL DEFAULT 0 AFTER niveau,
        ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1 AFTER ordre,
        ADD KEY idx_enigmes_ordre (actif, ordre)',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

ALTER TABLE enigmes MODIFY question TEXT NOT NULL, MODIFY reponse VARCHAR(255) NOT NULL,
                    MODIFY niveau ENUM('facile','moyen','difficile') NOT NULL DEFAULT 'facile';

-- ---------- enigme_scores (progression) ----------
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'enigme_scores' AND COLUMN_NAME = 'statut') = 0,
    'ALTER TABLE enigme_scores
        ADD COLUMN statut ENUM(''en_cours'',''resolue'',''revelee'') NOT NULL DEFAULT ''en_cours'' AFTER enigme_id,
        ADD COLUMN tentatives INT NOT NULL DEFAULT 0 AFTER statut,
        ADD COLUMN indice_utilise TINYINT(1) NOT NULL DEFAULT 0 AFTER tentatives,
        ADD COLUMN termine_at DATETIME NULL DEFAULT NULL AFTER created_at',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

ALTER TABLE enigme_scores MODIFY citoyens_id INT NOT NULL, MODIFY enigme_id INT NOT NULL,
                          MODIFY score INT NOT NULL DEFAULT 0;

-- Une seule ligne par citoyen et par énigme
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'enigme_scores' AND INDEX_NAME = 'uniq_citoyen_enigme') = 0,
    'ALTER TABLE enigme_scores ADD UNIQUE KEY uniq_citoyen_enigme (citoyens_id, enigme_id)',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

-- ---------- citoyens : pseudonyme du classement ----------
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @base AND TABLE_NAME = 'citoyens' AND COLUMN_NAME = 'pseudo_jeu') = 0,
    'ALTER TABLE citoyens ADD COLUMN pseudo_jeu VARCHAR(30) NULL DEFAULT NULL, ADD UNIQUE KEY uniq_pseudo_jeu (pseudo_jeu)',
    'DO 0');
PREPARE etape FROM @sql; EXECUTE etape; DEALLOCATE PREPARE etape;

-- ---------- Énigmes de DÉMONSTRATION (seulement si la table est vide) ----------
-- Les photos sont des illustrations (Frontend/assets/images/jeu/) :
-- la mairie les remplace par de vraies photos de la commune.
INSERT INTO enigmes (titre, type, lieu, question, image, reponse, reponses_acceptees, indice, explication, niveau, ordre)
SELECT * FROM (
    SELECT 'Le géant au bord du chemin' AS titre, 'texte' AS type, 'Le moulin' AS lieu,
           'Je tourne sans jamais avancer, le vent est mon moteur et je transforme le grain en farine. Qui suis-je ?' AS question,
           NULL AS image, 'Le moulin' AS reponse, 'moulin|moulin a vent' AS reponses_acceptees,
           'On trouvait autrefois beaucoup de ces bâtiments en Vendée.' AS indice,
           'Les moulins à vent servaient à moudre le grain. Beaucoup ont disparu, mais certains sont restaurés et se visitent.' AS explication,
           'facile' AS niveau, 1 AS ordre
    UNION ALL SELECT 'Quel est ce monument ?', 'photo', 'Le moulin',
           'Regardez bien cette image : quel bâtiment typique de nos campagnes reconnaissez-vous ?',
           'moulin.svg', 'Un moulin à vent', 'moulin|moulin a vent|moulin a ailes', 'Il a quatre grandes ailes.',
           'Ses ailes, couvertes de toile, captent le vent pour faire tourner la meule.',
           'facile', 2
    UNION ALL SELECT 'Une date à retrouver', 'date', 'La place de la mairie',
           'En quelle année la Révolution française a-t-elle commencé ? (La prise de la Bastille a eu lieu le 14 juillet de cette année-là.)',
           NULL, '1789', NULL, 'C’est à la fin du XVIIIe siècle.',
           'Le 14 juillet 1789, la prise de la Bastille marque le début de la Révolution. Les communes sont créées peu après, en décembre 1789.',
           'facile', 3
    UNION ALL SELECT 'Le gardien du village', 'texte', 'L’église',
           'Je sonne les heures et les fêtes, je vis tout en haut et l’on me tire par une corde. Qui suis-je ?',
           NULL, 'La cloche', 'cloche|cloches', 'Je loge dans le clocher.',
           'Les cloches rythmaient la vie du village : heures, mariages, baptêmes, mais aussi alertes en cas d’incendie.',
           'moyen', 4
    UNION ALL SELECT 'L’eau et le linge', 'photo', 'Le lavoir',
           'Autrefois, on venait ici à genoux pour laver le linge à la main. Quel est ce lieu ?',
           'lavoir.svg', 'Un lavoir', 'lavoir|lavoirs|le lavoir communal', 'Il est toujours au bord de l’eau, souvent avec un toit.',
           'Les lavoirs étaient aussi des lieux de rencontre et de discussion pour les habitants.',
           'moyen', 5
    UNION ALL SELECT 'La loi de séparation', 'date', 'L’église',
           'En quelle année la loi de séparation des Églises et de l’État a-t-elle été votée en France ?',
           NULL, '1905', NULL, 'Au tout début du XXe siècle.',
           'La loi du 9 décembre 1905 garantit la liberté de conscience et sépare les Églises de l’État.',
           'moyen', 6
    UNION ALL SELECT 'Le symbole de la République', 'photo', 'La mairie',
           'On trouve son buste dans chaque mairie de France. Qui est-elle ?',
           'marianne.svg', 'Marianne', 'marianne|la marianne|buste de marianne', 'Elle porte un bonnet phrygien.',
           'Marianne représente la République française. Son buste est présent dans les mairies et sur les timbres.',
           'difficile', 7
    UNION ALL SELECT 'La fin de la Grande Guerre', 'date', 'Le monument aux morts',
           'Le 11 novembre, on se rassemble devant le monument aux morts. En quelle année a été signé l’armistice de la Première Guerre mondiale ?',
           NULL, '1918', NULL, 'La guerre a duré un peu plus de quatre ans.',
           'L’armistice du 11 novembre 1918 met fin aux combats. Presque chaque commune a érigé un monument aux morts après la guerre.',
           'difficile', 8
) AS demo
WHERE NOT EXISTS (SELECT 1 FROM enigmes);
