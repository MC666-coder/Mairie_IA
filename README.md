# Portail citoyen & espace agent — Mairie (projet alternance)

Application web PHP pour la gestion des démarches citoyennes et l'accompagnement des agents municipaux, avec assistant IA intégré (Google Gemini).

> **Projet personnel / académique** — démonstration de compétences full-stack (PHP, MySQL, JavaScript, IA).

## Fonctionnalités

- **Portail citoyen** : inscription, connexion, création et suivi de demandes
- **Chatbot IA** : assistant multilingue pour orienter les citoyens (Gemini Flash)
- **Espace agent** : gestion des demandes, messagerie, notes, pièces jointes, assignation
- **Assistant IA agent** : analyse, suggestions d'actions et explications métier (Gemini Pro)
- **Agenda municipal** : calendrier des événements (FullCalendar)
- **Sécurité** : sessions sécurisées, CSRF, PDO préparé, sanitisation des entrées

## Stack technique

| Couche | Technologies |
|--------|-------------|
| Backend | PHP 8+, PDO/MySQL, Composer |
| Frontend | HTML/CSS/JS, FullCalendar, Vite |
| IA | Google Gemini API |
| Environnement | WAMP (Apache + MySQL) |

## Installation locale

### Prérequis

- PHP 8.0+
- MySQL / MariaDB
- Composer
- Node.js (optionnel, pour FullCalendar)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/VOTRE_USERNAME/Site_officiel.git
cd Site_officiel

# 2. Installer les dépendances PHP
composer install

# 3. Configurer les variables d'environnement
copy .env.example .env
# Éditer .env avec vos clés API et identifiants BDD

# 4. Créer la base de données et importer le schéma SQL
# (fichier SQL à fournir séparément si demandé)

# 5. (Optionnel) Dépendances front-end
npm install
```

Placer le projet dans le dossier `www` de WAMP, puis accéder à :
`http://localhost/Site_officiel/Frontend/index.php`

## Structure du projet

```
Site_officiel/
├── Frontend/          # Interface publique et portail citoyen
├── Agent-prenium/     # Espace agent (back-office)
├── Backend/
│   ├── Config/        # Configuration (BDD, env, API)
│   ├── ajax/          # Endpoints AJAX
│   ├── Agent_API/     # API assistant IA agent
│   └── chatbot/       # Chatbot citoyen
├── .env.example       # Modèle de configuration (sans secrets)
└── composer.json
```

## Sécurité

- Les secrets (clés API, mots de passe BDD) sont dans `.env`, **jamais** versionnés
- Les fichiers `.log` et uploads utilisateurs sont exclus du dépôt
- Ne pas exposer ce projet en production sans durcissement supplémentaire (HTTPS, rate limiting, etc.)

## Contact

Projet réalisé dans le cadre d'une recherche d'alternance en développement web.
