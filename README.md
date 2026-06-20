Ce projet est un site web permettant aux citoyens de réaliser leurs démarches en ligne et aux agents municipaux de gérer les demandes, les messages et les événements.
Ce dernier inclut deux assistants IA (portail citoyen et portail agent) basés sur Google Gemini.


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
- `citoyens`
- `agents`

**DÉMARCHES**
- `demandes`
- `types_demandes`
- `fichiers_demandes`
- `historique_demandes`
- `notes_demandes`
- `messages_demandes`

**INTELLIGENCE ARTIFICIELLE**
- `ai_conversations`
- `ai_conversations_agents`
- `ai_decisions`
- `historique_recherche`
- `ia_conn_ville`

**ÉVÈNEMENTS**
- `evenements` (utilisé par FullCalendar)

**ACTUALITÉS & NEWSLETTER**
- `news`
- `newsletter_subscribers`

**SERVICES MUNICIPAUX**
- `services`
- `travaux`

**MODULES EN COURS DE DÉVELOPPEMENT**
- `notifications` (en cours)
- `enigmes et enigma_scores` (jeu non encore créé)

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
