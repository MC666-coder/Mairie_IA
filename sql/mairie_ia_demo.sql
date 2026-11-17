-- =====================================================================
-- Base de DÉMONSTRATION Mairie_IA (données fictives, aucune donnée réelle)
-- Comptes de test :
--   Agent   : demo.agent@exemple.fr   / DemoAgent2026!
--   Citoyen : demo.citoyen@exemple.fr / DemoCitoyen2026!
-- =====================================================================

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : sam. 20 juin 2026 à 09:13
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mairie_ia`
--

-- --------------------------------------------------------

--
-- Structure de la table `agents`
--

DROP TABLE IF EXISTS `agents`;
CREATE TABLE IF NOT EXISTS `agents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `doit_definir_mot_de_passe` tinyint(1) DEFAULT '1',
  `token_activation` varchar(255) DEFAULT NULL,
  `token_expiration` datetime DEFAULT NULL,
  `service_id` int NOT NULL,
  `role` varchar(100) NOT NULL,
  `niveau_acces` enum('agent','manager','admin') DEFAULT 'agent',
  `telephone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'inactif',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `token_reset` varchar(255) DEFAULT NULL,
  `reset_expiration` datetime DEFAULT NULL,
  `identifiant_agent` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `ai_conversations`
--

DROP TABLE IF EXISTS `ai_conversations`;
CREATE TABLE IF NOT EXISTS `ai_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `citoyens_id` int DEFAULT NULL,
  `user_message` text,
  `ia_reponse` text,
  `intent` varchar(100) DEFAULT NULL,
  `confidence` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `citoyens_id` (`citoyens_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `ai_conversations_agents`
--

DROP TABLE IF EXISTS `ai_conversations_agents`;
CREATE TABLE IF NOT EXISTS `ai_conversations_agents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agent_id` int NOT NULL,
  `page` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `user_message` text NOT NULL,
  `ai_response` text NOT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ai_agent` (`agent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--




























-- --------------------------------------------------------

--
-- Structure de la table `ai_decisions`
--

DROP TABLE IF EXISTS `ai_decisions`;
CREATE TABLE IF NOT EXISTS `ai_decisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `requette_id` int DEFAULT NULL,
  `demande_id` int DEFAULT NULL,
  `decision` text,
  `justification` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requette_id` (`requette_id`),
  KEY `demande_id` (`demande_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `citoyens`
--

DROP TABLE IF EXISTS `citoyens`;
CREATE TABLE IF NOT EXISTS `citoyens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_citoyen` varchar(100) DEFAULT NULL,
  `prenom_citoyen` varchar(100) DEFAULT NULL,
  `email_citoyen` varchar(150) DEFAULT NULL,
  `telephone_citoyen` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_citoyen` (`email_citoyen`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `demandes`
--

DROP TABLE IF EXISTS `demandes`;
CREATE TABLE IF NOT EXISTS `demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `citoyen_id` int NOT NULL,
  `service_id` int DEFAULT NULL,
  `agent_id` int DEFAULT NULL,
  `nom_citoyen` varchar(150) DEFAULT NULL,
  `email_citoyen` varchar(150) DEFAULT NULL,
  `telephone_citoyen` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `resume_ia` text,
  `pieces_jointes` json DEFAULT NULL,
  `urgence_detectee_ia` tinyint(1) DEFAULT '0',
  `score_confiance_ia` decimal(5,2) DEFAULT NULL,
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `statut` enum('nouvelle','analyse_ia','en_cours','en_attente','resolue','cloturee') DEFAULT 'nouvelle',
  `commentaire_agent` text,
  `reponse_citoyen` text,
  `canal` enum('site','ia','formulaire','agent','telephone') DEFAULT 'site',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_mise_a_jour` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_cloture` timestamp NULL DEFAULT NULL,
  `type_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_demande_citoyen` (`citoyen_id`),
  KEY `fk_demande_service` (`service_id`),
  KEY `fk_demande_agent` (`agent_id`),
  KEY `type_id` (`type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `enigmes`
--

DROP TABLE IF EXISTS `enigmes`;
CREATE TABLE IF NOT EXISTS `enigmes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` text,
  `reponse` varchar(255) DEFAULT NULL,
  `niveau` enum('facile','moyen','difficile') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enigme_scores`
--

DROP TABLE IF EXISTS `enigme_scores`;
CREATE TABLE IF NOT EXISTS `enigme_scores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `citoyens_id` int DEFAULT NULL,
  `enigme_id` int DEFAULT NULL,
  `score` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `citoyens_id` (`citoyens_id`),
  KEY `enigme_id` (`enigme_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

DROP TABLE IF EXISTS `evenements`;
CREATE TABLE IF NOT EXISTS `evenements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `resume_ia` text,
  `version_courte` text,
  `version_longue` text,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `salle_id` int DEFAULT NULL,
  `service_id` int NOT NULL,
  `agent_createur_id` int NOT NULL,
  `agent_responsable_id` int DEFAULT NULL,
  `statut` enum('brouillon','en_attente','valide','publie','refuse') DEFAULT 'brouillon',
  `valide_par` int DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `visible_citoyens` tinyint(1) DEFAULT '0',
  `image_principale` varchar(255) DEFAULT NULL,
  `documents` text,
  `materiel` text,
  `equipe_technique` text,
  `meteo_prevue` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `agent_createur_id` (`agent_createur_id`),
  KEY `agent_responsable_id` (`agent_responsable_id`),
  KEY `valide_par` (`valide_par`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fichiers_demandes`
--

DROP TABLE IF EXISTS `fichiers_demandes`;
CREATE TABLE IF NOT EXISTS `fichiers_demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `demande_id` int NOT NULL,
  `agent_id` int DEFAULT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `taille` int DEFAULT NULL,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fichier_agent` (`agent_id`),
  KEY `idx_fichiers_demande` (`demande_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_demandes`
--

DROP TABLE IF EXISTS `historique_demandes`;
CREATE TABLE IF NOT EXISTS `historique_demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `demande_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `demande_id` (`demande_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--




-- --------------------------------------------------------

--
-- Structure de la table `historique_recherche`
--

DROP TABLE IF EXISTS `historique_recherche`;
CREATE TABLE IF NOT EXISTS `historique_recherche` (
  `id` int NOT NULL AUTO_INCREMENT,
  `requette_id` int DEFAULT NULL,
  `action` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requette_id` (`requette_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ia_conn_ville`
--

DROP TABLE IF EXISTS `ia_conn_ville`;
CREATE TABLE IF NOT EXISTS `ia_conn_ville` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categorie` enum('mairie','securite','commerce','finance','agglomeration','ville') DEFAULT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `contenu` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages_demandes`
--

DROP TABLE IF EXISTS `messages_demandes`;
CREATE TABLE IF NOT EXISTS `messages_demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `demande_id` int NOT NULL,
  `auteur_type` enum('citoyen','agent') NOT NULL,
  `citoyen_id` int DEFAULT NULL,
  `agent_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `parent_id` int DEFAULT NULL,
  `statut` enum('nouveau','lu','répondu') DEFAULT 'nouveau',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demande_id` (`demande_id`),
  KEY `agent_id` (`agent_id`),
  KEY `citoyen_id` (`citoyen_id`),
  KEY `parent_id` (`parent_id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `resume` text NOT NULL,
  `contenu` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `agents_id` int NOT NULL,
  `date_publication` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('publie','brouillon') DEFAULT 'publie',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agents_id` (`agents_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `notes_demandes`
--

DROP TABLE IF EXISTS `notes_demandes`;
CREATE TABLE IF NOT EXISTS `notes_demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `demande_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `note` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `demande_id` (`demande_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `citoyen_id` int NOT NULL,
  `demande_id` int NOT NULL,
  `type` enum('statut','message','document') NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_citoyen` (`citoyen_id`),
  KEY `fk_notifications_demande` (`demande_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `requette`
--

DROP TABLE IF EXISTS `requette`;
CREATE TABLE IF NOT EXISTS `requette` (
  `id` int NOT NULL AUTO_INCREMENT,
  `citoyens_id` int DEFAULT NULL,
  `services_id` int DEFAULT NULL,
  `type` enum('plainte','question','suggestion') DEFAULT NULL,
  `message` text,
  `priorite` enum('basse','normale','haute') DEFAULT 'normale',
  `statut` enum('nouvelle','analyse_ia','en_cours','resolue') DEFAULT 'nouvelle',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `citoyens_id` (`citoyens_id`),
  KEY `services_id` (`services_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--



-- --------------------------------------------------------

--
-- Structure de la table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(150) NOT NULL,
  `description_service` text NOT NULL,
  `mots_cles` text NOT NULL,
  `email_service` varchar(150) DEFAULT NULL,
  `delai_moyen` int DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

INSERT INTO `services` (`id`, `nom_service`, `description_service`, `mots_cles`, `email_service`, `delai_moyen`, `actif`) VALUES
(1, 'État civil', 'Gestion des actes de naissance, mariage, décès, livret de famille, recensement citoyen.', 'naissance, acte, mariage, décès, livret, recensement, état civil', 'etatcivil@mairie.fr', 2, 1),
(2, 'Urbanisme', 'Traitement des permis de construire, déclarations préalables, PLU, autorisations de travaux.', 'permis, travaux, chantier, déclaration, urbanisme, PLU', 'urbanisme@mairie.fr', 30, 1),
(3, 'Voirie et travaux', 'Entretien de la voirie, nids de poule, éclairage public, signalisation, trottoirs.', 'nid de poule, route, éclairage, trottoir, panneau, voirie', 'voirie@mairie.fr', 5, 1),
(4, 'Environnement', 'Gestion des déchets, tri sélectif, pollution, espaces verts, nuisances sonores.', 'déchets, poubelles, tri, pollution, bruit, environnement', 'environnement@mairie.fr', 3, 1),
(5, 'CCAS / Social', 'Aides sociales, logement, RSA, accompagnement des personnes en difficulté.', 'aide sociale, logement, RSA, dossier social, CCAS', 'ccas@mairie.fr', 7, 1),
(6, 'Scolaire et jeunesse', 'Inscriptions scolaires, cantine, garderie, périscolaire, activités jeunesse.', 'école, cantine, inscription, garderie, périscolaire', 'scolaire@mairie.fr', 3, 1),
(7, 'Communication', 'Gestion du site internet, réseaux sociaux, affichage municipal, newsletter.', 'site, réseaux sociaux, communication, affichage', 'communication@mairie.fr', 1, 1),
(8, 'Culture et sport', 'Bibliothèque, médiathèque, associations, salles municipales, événements culturels et sportifs.', 'bibliothèque, médiathèque, sport, association, culture', 'culture@mairie.fr', 5, 1),
(9, 'Finances', 'Suivi des paiements, factures, taxes locales, budget municipal.', 'facture, paiement, taxe, impôts locaux, finances', 'finances@mairie.fr', 10, 1),
(10, 'Ressources humaines', 'Recrutement, gestion du personnel municipal, stages, candidatures.', 'emploi, recrutement, CV, candidature, RH', 'rh@mairie.fr', 7, 1),
(11, 'Police municipale', 'Stationnement, sécurité, objets trouvés, infractions, tranquillité publique.', 'stationnement, sécurité, PV, objets trouvés, police', 'police@mairie.fr', 2, 1),
(12, 'Cimetière', 'Gestion des concessions, renouvellements, entretien des espaces funéraires.', 'cimetière, concession, tombe, renouvellement', 'cimetiere@mairie.fr', 15, 1),
(13, 'Transport / Mobilité', 'Bus, navettes, stationnement, mobilité douce, pistes cyclables.', 'bus, transport, vélo, stationnement, mobilité', 'transport@mairie.fr', 5, 1),
(14, 'Événementiel', 'Organisation des événements municipaux, logistique, réservation de matériel.', 'événement, salle, matériel, organisation, événementiel', 'evenementiel@mairie.fr', 4, 1);

-- --------------------------------------------------------

--
-- Structure de la table `travaux`
--

DROP TABLE IF EXISTS `travaux`;
CREATE TABLE IF NOT EXISTS `travaux` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `rue` varchar(255) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `impact` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `statut` enum('a_venir','en_cours','termine') DEFAULT 'a_venir',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `types_demandes`
--

DROP TABLE IF EXISTS `types_demandes`;
CREATE TABLE IF NOT EXISTS `types_demandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `couleur` varchar(20) DEFAULT '#003b8e',
  `icone` varchar(50) DEFAULT 'fa-circle',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

INSERT INTO `types_demandes` (`id`, `code`, `libelle`, `couleur`, `icone`) VALUES
(1, 'plainte', 'Plainte', '#dc3545', 'fa-triangle-exclamation'),
(2, 'question', 'Question', '#0d6efd', 'fa-circle-question'),
(3, 'suggestion', 'Suggestion', '#198754', 'fa-lightbulb'),
(4, 'signalement', 'Signalement', '#fd7e14', 'fa-bullhorn'),
(5, 'autre', 'Autre', '#6c757d', 'fa-ellipsis');

-- Comptes de démonstration
INSERT INTO `agents` (`id`,`nom`,`prenom`,`email`,`mot_de_passe`,`doit_definir_mot_de_passe`,`service_id`,`role`,`niveau_acces`,`statut`,`identifiant_agent`) VALUES
(1,'Démo','Agent','demo.agent@exemple.fr','$2y$10$n06O1591x/DqdWOMeD4HGur2SwFwXJdXj3SYFpq2yu6w1GCsuLKBu',0,1,'responsable','admin','actif','AGT-2026-000001');
INSERT INTO `citoyens` (`id`,`nom_citoyen`,`prenom_citoyen`,`email_citoyen`,`telephone_citoyen`,`password`) VALUES
(1,'Démo','Citoyen','demo.citoyen@exemple.fr','0600000000','$2y$10$CWcos9yDyS3iV7p9xjnnv.7jW/U.0hX3iYP0n8xY/ZB5zNuN93q.q');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
