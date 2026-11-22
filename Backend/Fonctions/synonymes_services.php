<?php
/* =========================================================
   Synonymes de recherche des services (Frontend/Services.php)
   ---------------------------------------------------------
   Les mots que les citoyens tapent vraiment : « mort » doit mener à
   l'État civil (décès), « poubelle » à l'Environnement, etc.
   Ils ne sont pas affichés : ils servent seulement à la recherche
   (assets/js/services.js). Le JS ignore les accents, les majuscules,
   les pluriels simples, et trouve aussi les débuts de mots
   (« inscri » → « inscription »).
   Pour enrichir la recherche : ajouter des mots ici, rien d'autre à changer.
   ========================================================= */

const SYNONYMES_SERVICES = [
    'État civil' => [
        'mort', 'mourir', 'décédé', 'défunt', 'obsèques', 'enterrement', 'funérailles', 'certificat de décès',
        'bébé', 'nouveau-né', 'accouchement', 'naître', 'déclarer une naissance', 'paternité',
        'marier', 'se marier', 'noces', 'union', 'divorce', 'séparation', 'pacsé',
        'nom', 'prénom', 'changement de nom', 'extrait', 'copie', 'certificat', 'papiers',
        'carte d’identité', 'cni', 'passeport', 'identité', 'recensement', 'nationalité', 'attestation',
    ],
    'Urbanisme' => [
        'maison', 'construire', 'construction', 'bâtir', 'agrandir', 'agrandissement', 'extension',
        'véranda', 'garage', 'abri de jardin', 'cabane', 'terrasse', 'toit', 'toiture', 'façade',
        'fenêtre', 'mur', 'portail', 'plan', 'terrain', 'parcelle', 'plu', 'démolir', 'démolition',
        'rénovation', 'rénover', 'panneaux solaires', 'photovoltaïque', 'urbanisme', 'architecte',
    ],
    'Voirie et travaux' => [
        'rue', 'chaussée', 'trou', 'bosse', 'goudron', 'lumière', 'éclairage public', 'panne',
        'feu rouge', 'feu tricolore', 'signalisation', 'passage piéton', 'parking', 'se garer',
        'voiture', 'circulation', 'ralentisseur', 'dos d’âne', 'vitesse', 'neige', 'verglas',
        'arbre tombé', 'caniveau', 'égout', 'bouche d’égout', 'inondation', 'fuite d’eau', 'piste cyclable',
    ],
    'Environnement' => [
        'ordures', 'ordures ménagères', 'recyclage', 'recycler', 'déchetterie', 'ramassage', 'collecte',
        'compost', 'verre', 'sac jaune', 'saleté', 'sale', 'propreté', 'crotte', 'déjection',
        'tapage', 'nuisance', 'voisin', 'odeur', 'pollution', 'fumée', 'brûlage',
        'arbre', 'haie', 'jardin', 'parc', 'pelouse', 'tonte', 'rats', 'nuisibles', 'moustiques', 'frelons',
    ],
    'CCAS / Social' => [
        'argent', 'pauvreté', 'difficulté', 'difficultés financières', 'dette', 'loyer', 'expulsion',
        'hébergement', 'sans abri', 'sdf', 'allocation', 'caf', 'aide sociale', 'chômage',
        'personne âgée', 'retraite', 'retraité', 'senior', 'aîné', 'isolement', 'seul',
        'handicapé', 'aidant', 'colis alimentaire', 'faim', 'nourriture', 'banque alimentaire',
        'violence', 'violences', 'santé', 'mutuelle', 'aide à domicile', 'portage de repas', 'social',
    ],
    'Scolaire et jeunesse' => [
        'écolier', 'élève', 'maternelle', 'primaire', 'classe', 'maître', 'maîtresse', 'instituteur',
        'rentrée', 'inscrire', 'repas', 'restauration scolaire', 'menu', 'accueil périscolaire',
        'centre aéré', 'vacances', 'mercredi', 'crèche', 'nounou', 'assistante maternelle', 'bébé',
        'jeune', 'jeunes', 'ado', 'adolescent', 'transport scolaire', 'bus scolaire', 'fournitures', 'enfants',
    ],
    'Communication' => [
        'info', 'infos', 'actualité', 'actualités', 'journal', 'magazine', 'site internet', 'site web',
        'facebook', 'instagram', 'affiche', 'panneau d’affichage', 'annonce', 'presse', 'journaliste',
        'publicité', 'diffuser',
    ],
    'Culture et sport' => [
        'foot', 'football', 'tennis', 'basket', 'handball', 'rugby', 'gymnase', 'terrain de sport',
        'piscine municipale', 'location de salle', 'louer une salle', 'salle des fêtes', 'fête',
        'concert', 'spectacle', 'musique', 'théâtre', 'cinéma', 'médiathèque', 'livre', 'livres',
        'lecture', 'exposition', 'musée', 'patrimoine', 'club', 'bénévole', 'bénévolat', 'loisir', 'loisirs',
    ],
    'Finances' => [
        'payer', 'impayé', 'prix', 'tarif', 'tarifs', 'impôt', 'impôts', 'taxe foncière',
        'taxe d’habitation', 'argent public', 'remboursement', 'rembourser', 'chèque',
        'carte bancaire', 'prélèvement', 'trésor public', 'devis', 'comptabilité',
    ],
    'Ressources humaines' => [
        'travail', 'travailler', 'job', 'boulot', 'poste', 'recruter', 'embauche', 'embaucher',
        'candidater', 'postuler', 'lettre de motivation', 'stagiaire', 'alternance', 'apprentissage',
        'apprenti', 'saisonnier', 'agent', 'fonctionnaire', 'concours', 'personnel', 'offre d’emploi',
    ],
];
