-- =========================================================
-- Migration 006 : événements de DÉMONSTRATION pour l'agenda
-- ---------------------------------------------------------
-- Événements FICTIFS, datés par rapport au jour où la migration est
-- lancée (toujours « à venir »). Seuls ceux publiés ET visibles des
-- citoyens apparaissent dans l'agenda : le brouillon et l'événement
-- non visible servent à vérifier qu'ils restent cachés.
-- Idempotente : les événements « [Démo] » sont supprimés puis recréés.
-- =========================================================

DELETE FROM evenements WHERE titre LIKE '[Démo]%';

SET @agent  = COALESCE((SELECT MIN(id) FROM agents), 0);
SET @defaut = (SELECT MIN(id) FROM services);
SET @culture = COALESCE((SELECT id FROM services WHERE nom_service = 'Culture et sport' LIMIT 1), @defaut);
SET @env     = COALESCE((SELECT id FROM services WHERE nom_service = 'Environnement' LIMIT 1), @defaut);
SET @ccas    = COALESCE((SELECT id FROM services WHERE nom_service = 'CCAS / Social' LIMIT 1), @defaut);
SET @jeunes  = COALESCE((SELECT id FROM services WHERE nom_service = 'Scolaire et jeunesse' LIMIT 1), @defaut);
SET @fetes   = COALESCE((SELECT id FROM services WHERE nom_service = 'Événementiel' LIMIT 1), @defaut);

INSERT INTO evenements
    (titre, description, version_courte, date_debut, date_fin, lieu, service_id, agent_createur_id, statut, visible_citoyens)
VALUES
('[Démo] Marché de Noël',
 'Marché de Noël avec artisans locaux, animations pour les enfants et vin chaud. Entrée libre.',
 'Artisans locaux, animations et vin chaud. Entrée libre.',
 CURDATE() + INTERVAL 5 DAY + INTERVAL 10 HOUR, CURDATE() + INTERVAL 6 DAY + INTERVAL 19 HOUR,
 'Place de la Mairie', @fetes, @agent, 'publie', 1),

('[Démo] Concert de l''école de musique',
 'Concert de fin de trimestre des élèves de l''école de musique municipale. Gratuit, tout public.',
 'Concert gratuit des élèves de l''école de musique.',
 CURDATE() + INTERVAL 9 DAY + INTERVAL 18 HOUR, CURDATE() + INTERVAL 9 DAY + INTERVAL 20 HOUR,
 'Salle des fêtes', @culture, @agent, 'publie', 1),

('[Démo] Atelier compostage',
 'Atelier pratique pour apprendre à composter ses déchets de cuisine et de jardin. Sur inscription auprès du service Environnement.',
 'Apprendre à composter. Sur inscription.',
 CURDATE() + INTERVAL 3 DAY + INTERVAL 14 HOUR, CURDATE() + INTERVAL 3 DAY + INTERVAL 16 HOUR,
 'Jardins partagés', @env, @agent, 'publie', 1),

('[Démo] Repas des aînés',
 'Repas offert par le CCAS aux habitants de plus de 70 ans. Inscription à l''accueil de la mairie.',
 'Repas offert aux plus de 70 ans. Inscription en mairie.',
 CURDATE() + INTERVAL 12 DAY + INTERVAL 12 HOUR, CURDATE() + INTERVAL 12 DAY + INTERVAL 16 HOUR,
 'Salle des fêtes', @ccas, @agent, 'publie', 1),

('[Démo] Tournoi de football des jeunes',
 'Tournoi amical pour les 8-14 ans, organisé avec les clubs de la commune.',
 'Tournoi amical pour les 8-14 ans.',
 CURDATE() + INTERVAL 16 DAY + INTERVAL 9 HOUR, CURDATE() + INTERVAL 16 DAY + INTERVAL 17 HOUR,
 'Stade municipal', @jeunes, @agent, 'publie', 1),

('[Démo] Brouillon : réunion interne (ne doit PAS apparaître)',
 'Événement en brouillon : invisible pour le public.', NULL,
 CURDATE() + INTERVAL 4 DAY + INTERVAL 9 HOUR, CURDATE() + INTERVAL 4 DAY + INTERVAL 10 HOUR,
 'Mairie', @fetes, @agent, 'brouillon', 1),

('[Démo] Publié mais non visible (ne doit PAS apparaître)',
 'Événement publié mais réservé aux agents : invisible pour le public.', NULL,
 CURDATE() + INTERVAL 7 DAY + INTERVAL 9 HOUR, CURDATE() + INTERVAL 7 DAY + INTERVAL 10 HOUR,
 'Mairie', @fetes, @agent, 'publie', 0);
