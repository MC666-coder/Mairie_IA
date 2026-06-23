Pendant mon année sabbatique, et n’ayant toujours pas trouvé d’alternance, j’ai décidé de me challenger afin de gagner en compétences et d’augmenter mes chances dans ma recherche d’alternance.
Je me suis donc intéressé à un problème réel rencontré par de nombreuses structures publiques, notamment les mairies : comment les agents municipaux peuvent-ils gérer efficacement un grand nombre de demandes, et comment les citoyens peuvent-ils s’orienter et réaliser leurs démarches en ligne de manière simple et rapide ?
À partir de cette réflexion, j’ai développé un site web complet dans le cadre d’un entraînement personnel, avec pour objectif de reproduire les fonctionnalités essentielles d’un portail municipal moderne.

Ce projet est un site web permettant aux citoyens de réaliser leurs démarches en ligne et aux agents municipaux de gérer les demandes, les messages et les événements.
Ce dernier inclut deux assistants IA (portail citoyen et portail agent) basés sur Google Gemini.

----------------------------------------------------
FONCTIONNALITÉS PRINCIPALES
----------------------------------------------------
 **Portail Citoyen**:
 - création et suivis des démarches en ligne
 - Envoie de documents et pièces jointes
 - Messagerie intégrée avec la mairie
 - Historique complet des demandes (avec filtres)
 - Assistant IA citoyen (Google Gemini) pour guider l'utilisateur et même executer des demandes à la place de l'utilisateur si ce dernier l'autorise.
   
**Portail Agent Municipal**:
 - Gestion des demandes citoyennes
 - Consultation et réponse aux messages
 - Ajout de notes internes sur les dossiers
 - Suivi des statuts et historique des actions effectuées sur un dossier citoyen
 - Assistant IA agent (Google Gemini) pour accélérer le traitement
   
**Événements & Agenda**:
 - Gestion des événements municipaux
 - Affichage dynamique via Fullcalendar (en cours....)
 - Vue calendrier pour les agents (en cours....)
   
**Actualités & Communication**:
 - Publication d'actualités municipales
 - Système d'abonnement newsletter
 - gestion des abonnés
   
**Services municipaux**:
 - Listes des services disponibles
 - Informations sur les travaux en cours
   
**Modules en développement**:
 - Système de notifications (en cours.....)
 - Mini-jeu "énigmes" avec classement (en cours....)    
   


----------------------------------------------------
TECHNOLOGIES UTILISÉES
----------------------------------------------------

- HTML / CSS / JavaScript
- AJAX
- PHP
- MySQL
- Google Gemini
- FullCalendar (agenda municipal)
- WampServer
- Git / GitHub



----------------------------------------------------
STRUCTURE DE LA BASE DE DONNÉES
----------------------------------------------------

La base de donnée mairie_ia est organisée autour de plusieurs modules.


**CITOYENS & AGENTS**
- `citoyens` : Stocke les comptes citoyens(identité, email, mot de passe, informations personnelles).
- `agents` : Contient les comptes des agents municpaux (rôle, service, accès back-office).

**DÉMARCHES**
- `demandes` : Enregistre chaque démarche effectuée par un citoyen (type, description, statut, dates).
- `types_demandes` : Liste les catégories de démarches disponibles (ex: carte d'identité, attestation.....).
- `fichiers_demandes` : stocke les pièces jointes envoyées par les citoyens.
- `historique_demandes` : Trace etoutes les actions affectuées sur une demande (création, modification, validation....).
- `notes_demandes` : Permet aux agents d'ajouter des notes internes sur un dossier.
- `messages_demandes` : Gère la messagerie entre citoyens et agents pour chaque demande.

**INTELLIGENCE ARTIFICIELLE**
- `ai_conversations` : historique des conversations entre le citoyen et l’IA.
- `ai_conversations_agents` : historique des conversations entre les agents et l’IA.
- `ai_decisions` : enregistre les actions que l’IA a proposées ou exécutées (ex : remplir un formulaire).
- `historique_recherche` : sauvegarde les recherches effectuées par les utilisateurs via l’IA.
- `ia_conn_ville` : stocke les informations de connexion ou d’interaction entre l’IA et la mairie.

**ÉVÈNEMENTS**
- `evenements` (utilisé par FullCalendar) : contient les événements municipaux affichés dans FullCalendar (titre, date, description).

**ACTUALITÉS & NEWSLETTER**
- `news` : articles et actualités publiés par la mairie.
- `newsletter_subscribers`  : liste des abonnés à la newsletter.

**SERVICES MUNICIPAUX**
- `services` : liste les services municipaux (urbanisme, état civil, jeunesse…).
- `travaux` : informations sur les travaux en cours dans la commune.

**MODULES EN COURS DE DÉVELOPPEMENT**
- `notifications` (en cours) : Futur système de notifications (status, alertes, messages)
- `enigmes et enigma_scores` (jeu non encore créé) : Mini-jeu "énigmes" avec classement (gamification).

Contenu de db.php :
--------------------------------------------------
```php
$servername = 'localhost';
$username = 'root';
$dbname = 'mairie_ia';
$password = '';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "erreur de connexion à votre base de donnée" . $e->getMessage();
    $pdo = NULL;
}
```

----------------------------------------------------
CAPTURE d'écran & vidéos (vide pour l'instant)
----------------------------------------------------




----------------------------------------------------
INSTALLATION DU PROJET
----------------------------------------------------

 1. **Cloner le projet**
```bash
git clone https://github.com/MC666-coder/Mairie_IA.git
```

 2. **Accéder au dossier du projet**
```bash
cd Mairie_IA/SITE_OFFICIEL
```

 3. **Copier le fichier d'environnement**
```bash
cp .env.example .env
```

 4. **Modifier le fichier .env**
 Ajouter :
 - Clé API Google Gemini

 5. **Importer la base de données**
 Étapes dans phpMyAdmin :
 - Créer une base nommée : mairie_ia
- Importer le fichier : sql/mairie_ia.sql

 6. **Démarrer WampServer**

 7. **Placer le dossier SITE_OFFICIEL dans :**
 C:/wamp64/www/

 8. **Lancer le site dans le navigateur**
```bash 
http://localhost/SITE_OFFICIEL/Frontend/index.php
```


----------------------------------------------------
STRUCTURE DU PROJET
----------------------------------------------------
    
    SITE_OFFICIEL/
    ├── Frontend/
    ├── Agent-prenium/
    ├── Backend/
    │   ├── Config/
    │   ├── ajax/
    │   ├── Agent_API/
    │   └── chatbot/
    ├── sql/
    └── composer.json



----------------------------------------------------
CONTACT
----------------------------------------------------

Email : mbourouclaudemarvine@gmail.com
LinkedIn : https://www.linkedin.com/in/claude-marvine-mbourou-ab05a12b7
