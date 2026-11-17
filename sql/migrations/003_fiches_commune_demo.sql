-- =========================================================
-- Migration 003 : fiches de DÉMONSTRATION pour l'IA d'accueil
-- ---------------------------------------------------------
-- L'IA citoyen ne répond qu'à partir de la table ia_conn_ville
-- (+ services, travaux, événements et actualités publiés).
-- Ces fiches sont FICTIVES : chaque mairie les remplace par ses
-- propres informations (horaires, contacts, démarches...).
--
-- Idempotente : les fiches de démonstration sont supprimées puis
-- recréées, les fiches ajoutées par la mairie ne sont pas touchées.
-- =========================================================

DELETE FROM ia_conn_ville WHERE titre LIKE '[Démo]%';

INSERT INTO ia_conn_ville (categorie, titre, contenu) VALUES
('mairie', '[Démo] Horaires et accès de la mairie',
 'La mairie est ouverte du lundi au vendredi de 8h30 à 12h00 et de 13h30 à 17h00, et le samedi de 9h00 à 12h00 (permanence état civil uniquement). Elle est fermée les jours fériés. Adresse : 1 place de la Mairie. Téléphone de l''accueil : 01 23 45 67 89. Accessible aux personnes à mobilité réduite (entrée côté parking).'),

('mairie', '[Démo] Prendre rendez-vous',
 'Les rendez-vous (cartes d''identité, passeports, urbanisme, CCAS) se prennent par téléphone à l''accueil ou directement au guichet. Les cartes d''identité et passeports nécessitent une pré-demande en ligne sur le site de l''ANTS (ants.gouv.fr) avant le rendez-vous.'),

('mairie', '[Démo] Actes d''état civil',
 'Les copies et extraits d''actes de naissance, de mariage et de décès sont gratuits. Ils se demandent au guichet de l''état civil (avec une pièce d''identité) ou en ligne sur service-public.fr pour les actes enregistrés dans la commune. Délai habituel : une semaine par courrier.'),

('mairie', '[Démo] Urbanisme',
 'Le service Urbanisme reçoit sur rendez-vous le mardi et le jeudi après-midi. Les déclarations préalables et permis de construire se déposent en ligne sur le guichet numérique des autorisations d''urbanisme de la commune ou au service Urbanisme. Le plan local d''urbanisme (PLU) est consultable en mairie.'),

('ville', '[Démo] Collecte des déchets',
 'Ordures ménagères (bac gris) : collecte le lundi et le jeudi. Tri sélectif (bac jaune) : collecte le mercredi, une semaine sur deux. Les bacs se sortent la veille au soir. Déchetterie : route de la Forêt, ouverte du mardi au samedi de 9h00 à 12h00 et de 14h00 à 18h00, gratuite pour les habitants sur présentation d''un justificatif de domicile. Les encombrants sont collectés sur rendez-vous auprès du service Environnement.'),

('ville', '[Démo] École, cantine et périscolaire',
 'Les inscriptions scolaires se font auprès du service Scolaire et jeunesse avec le livret de famille, un justificatif de domicile et le carnet de vaccination de l''enfant. L''inscription à la cantine et à la garderie se fait au même service. Les menus de la cantine sont affichés à l''école et publiés chaque mois sur le site de la commune.'),

('securite', '[Démo] Police municipale',
 'La Police municipale est joignable du lundi au samedi de 8h00 à 19h00 au 01 23 45 67 90. Elle gère le stationnement, la tranquillité publique et les objets trouvés. En dehors de ces horaires ou en cas d''urgence, composer le 17.'),

('securite', '[Démo] Numéros d''urgence',
 'Urgence vitale : SAMU 15. Police secours : 17. Pompiers : 18. Numéro d''urgence européen : 112. Personnes sourdes ou malentendantes : 114 (SMS). Violences conjugales : 3919. Ces numéros sont gratuits et joignables 24h/24.'),

('mairie', '[Démo] CCAS et aides sociales',
 'Le Centre communal d''action sociale (CCAS) accompagne les personnes en difficulté : aides d''urgence, aide aux factures d''énergie, logement, accompagnement des personnes âgées. Il reçoit sur rendez-vous du lundi au vendredi. Les demandes sont traitées de façon confidentielle.'),

('ville', '[Démo] Salles municipales et associations',
 'Les salles municipales peuvent être louées par les habitants et les associations auprès du service Culture et sport. La liste des associations de la commune est disponible à l''accueil de la mairie.');
