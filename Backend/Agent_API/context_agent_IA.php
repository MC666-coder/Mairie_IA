<?php



return [
    /* ============================================================
    1) STATUTS DES DEMANDES
    ============================================================ */
    "statuts" => [
        "nouvelle" => [
            "label" => "Nouvelle demande",
            "description" => "Demande reçue, non encore analysée ni traitée.",
            "actions_possibles" => ["analyse_ia", "en_cours"]
        ],
        "analyse_ia" => [
            "label" => "Analyse IA",
            "description" => "Analyse automatique en cours pour détecter incohérences, urgences et actions possibles.",
            "actions_possibles" => ["en_cours"]
        ],
        "en_cours" => [
            "label" => "En cours de traitement",
            "description" => "Demande prise en charge par un agent.",
            "actions_possibles" => ["resolue"]
        ],
        "resolue" => [
            "label" => "Résolue",
            "description" => "Demande clôturée, réponse fournie au citoyen.",
            "actions_possibles" => []
        ]
    ],
    /* ============================================================
   ACTIONS MÉTIER PAR STATUT
   ============================================================ */
    "actions_metier" => [
        "nouvelle" => [
            "Analyser la demande",
            "Vérifier la complétude",
            "Identifier le service compétent",
            "Vérifier l’urgence ou la priorité",
            "Demander des informations complémentaires",
            "Transmettre au service compétent"
        ],
        "analyse_ia" => [
            "Vérifier l’analyse automatique",
            "Compléter les informations manquantes",
            "Transmettre au service compétent",
            "Passer en traitement manuel"
        ],
        "en_cours" => [
            "Vérifier l’avancement",
            "Relancer le service en charge",
            "Ajouter une note interne",
            "Informer le citoyen de l’avancement",
            "Modifier la priorité si nécessaire"
        ],
        "en_attente" => [
            "Identifier la cause du blocage",
            "Relancer le service ou l’agent responsable",
            "Informer le citoyen du délai",
            "Ajouter une note interne",
            "Replanifier si nécessaire"
        ],
        "en_attente_informations" => [
            "Identifier les informations manquantes",
            "Préparer un message pour le citoyen",
            "Relancer le citoyen si nécessaire",
            "Ajouter une note interne"
        ],
        "transmis_service" => [
            "Vérifier la bonne réception",
            "Relancer le service si délai dépassé",
            "Informer le citoyen du transfert",
            "Ajouter une note interne"
        ],
        "planifie" => [
            "Vérifier la date prévue",
            "Informer le citoyen",
            "Modifier la date si nécessaire",
            "Ajouter une note interne"
        ],
        "resolue" => [
            "Vérifier la conformité de la résolution",
            "Informer le citoyen",
            "Clôturer le dossier",
            "Ajouter une note interne"
        ],
        "cloture" => [
            "Réouvrir le dossier si nécessaire",
            "Ajouter une note interne",
            "Informer le citoyen"
        ],
        "refuse" => [
            "Vérifier la justification du refus",
            "Informer le citoyen",
            "Réorienter vers un autre service si possible",
            "Ajouter une note interne"
        ]
    ],
    /* ============================================================
   WORKFLOW MÉTIER DÉTAILLÉ
   (Actions possibles selon STATUT + TYPE DE DEMANDE)
   ============================================================ */
    "workflow_metier" => [

        /* --- VOIRIE --- */
        "voirie" => [
            "nouvelle" => [
                "Analyser la demande",
                "Vérifier la localisation",
                "Demander une photo si absente",
                "Évaluer la dangerosité",
                "Transmettre au service Voirie"
            ],
            "en_cours" => [
                "Vérifier l’avancement des travaux",
                "Relancer l’équipe Voirie",
                "Informer le citoyen du délai estimé"
            ],
            "resolue" => [
                "Vérifier la conformité de l’intervention",
                "Informer le citoyen de la résolution"
            ]
        ],

        /* --- ENVIRONNEMENT / DÉCHETS --- */
        "environnement" => [
            "nouvelle" => [
                "Analyser le type de problème",
                "Vérifier la zone de collecte",
                "Transmettre au service Environnement"
            ],
            "en_cours" => [
                "Vérifier la tournée prévue",
                "Relancer l’équipe Environnement",
                "Informer le citoyen du passage"
            ],
            "resolue" => [
                "Confirmer que la collecte a été effectuée",
                "Informer le citoyen"
            ]
        ],

        /* --- URBANISME --- */
        "urbanisme" => [
            "nouvelle" => [
                "Vérifier la complétude du dossier",
                "Identifier les pièces manquantes",
                "Transmettre au service Urbanisme"
            ],
            "en_cours" => [
                "Vérifier l’état d’instruction",
                "Relancer l’instructeur si délai dépassé",
                "Informer le citoyen du délai réglementaire"
            ],
            "resolue" => [
                "Confirmer la décision (accord/refus)",
                "Informer le citoyen"
            ]
        ],

        /* --- SOCIAL / CCAS --- */
        "ccas" => [
            "nouvelle" => [
                "Identifier l’urgence sociale",
                "Vérifier les justificatifs",
                "Transmettre au CCAS"
            ],
            "en_cours" => [
                "Vérifier la prise en charge",
                "Relancer l’agent social",
                "Informer le citoyen si nécessaire"
            ],
            "resolue" => [
                "Confirmer l’aide attribuée",
                "Informer le citoyen"
            ]
        ],

        /* --- ÉTAT CIVIL --- */
        "etat_civil" => [
            "nouvelle" => [
                "Vérifier l’identité du demandeur",
                "Vérifier le lien avec la personne concernée",
                "Transmettre à l’État civil"
            ],
            "en_cours" => [
                "Vérifier la génération de l’acte",
                "Relancer l’agent si nécessaire"
            ],
            "resolue" => [
                "Confirmer l’envoi de l’acte",
                "Informer le citoyen"
            ]
        ],

        /* --- POLICE MUNICIPALE --- */
        "police" => [
            "nouvelle" => [
                "Analyser la nature du signalement",
                "Évaluer l’urgence",
                "Transmettre à la Police municipale"
            ],
            "en_cours" => [
                "Vérifier l’intervention",
                "Relancer la patrouille",
                "Informer le citoyen si possible"
            ],
            "resolue" => [
                "Confirmer l’intervention",
                "Informer le citoyen"
            ]
        ]
    ],
    /* ============================================================
   ACTIONS SUGGÉRÉES PAR L’IA (JAMAIS EXÉCUTÉES)
   ============================================================ */
    "actions_suggerees" => [

        /* --- ACTIONS GÉNÉRIQUES --- */
        "generiques" => [
            "demander_informations" => "Demander des informations complémentaires au citoyen.",
            "verifier_completude" => "Vérifier si la demande contient toutes les informations nécessaires.",
            "orienter_service" => "Orienter la demande vers le service compétent.",
            "ajouter_note" => "Ajouter une note interne au dossier.",
            "informer_citoyen" => "Informer le citoyen de l’avancement ou du délai.",
            "relancer_service" => "Relancer le service en charge si le délai est dépassé.",
            "evaluer_urgence" => "Évaluer si la demande présente un caractère urgent."
        ],

        /* --- ACTIONS SPÉCIFIQUES PAR SERVICE --- */
        "voirie" => [
            "planifier_intervention" => "Planifier une intervention technique.",
            "verifier_dangerosite" => "Évaluer la dangerosité de la situation.",
            "transmettre_voirie" => "Transmettre la demande au service Voirie."
        ],

        "environnement" => [
            "transmettre_environnement" => "Transmettre au service Environnement.",
            "verifier_tournee" => "Vérifier la tournée de collecte.",
            "planifier_passage" => "Planifier un passage des équipes."
        ],

        "urbanisme" => [
            "verifier_dossier" => "Vérifier la complétude du dossier d’urbanisme.",
            "transmettre_urbanisme" => "Transmettre au service Urbanisme.",
            "identifier_pieces_manquantes" => "Identifier les pièces manquantes."
        ],

        "ccas" => [
            "transmettre_ccas" => "Transmettre au CCAS.",
            "contacter_agent_social" => "Contacter un agent social en cas d’urgence.",
            "verifier_eligibilite" => "Vérifier l’éligibilité à l’aide demandée."
        ],

        "etat_civil" => [
            "generer_acte" => "Générer l’acte demandé.",
            "verifier_identite" => "Vérifier l’identité du demandeur.",
            "envoyer_courrier" => "Envoyer l’acte par courrier."
        ],

        "police" => [
            "transmettre_police" => "Transmettre à la Police municipale.",
            "evaluer_risque" => "Évaluer le risque immédiat.",
            "relancer_patrouille" => "Relancer la patrouille si nécessaire."
        ],

        "transport" => [
            "verifier_dossier_transport" => "Vérifier la demande de stationnement ou mobilité.",
            "transmettre_transport" => "Transmettre au service Mobilité."
        ]
    ],
    /* ============================================================
   RÈGLES DE COHÉRENCE MÉTIER (ANTI-ERREURS IA)
   ============================================================ */
    "coherence_metier" => [

        /* --- RÈGLES PAR STATUT --- */
        "statuts" => [
            "nouvelle" => [
                "interdits" => [
                    "cloturer_dossier",
                    "generer_acte",
                    "planifier_intervention",
                    "informer_resolution"
                ],
                "obligatoires" => [
                    "analyser_demande",
                    "verifier_completude"
                ]
            ],
            "analyse_ia" => [
                "interdits" => [
                    "cloturer_dossier",
                    "informer_resolution"
                ],
                "obligatoires" => [
                    "verifier_analyse",
                    "identifier_informations_manquantes"
                ]
            ],
            "en_cours" => [
                "interdits" => [
                    "analyser_demande",
                    "verifier_completude"
                ],
                "obligatoires" => [
                    "verifier_avancement"
                ]
            ],
            "resolue" => [
                "interdits" => [
                    "planifier_intervention",
                    "relancer_service",
                    "demander_informations"
                ],
                "obligatoires" => [
                    "verifier_resolution"
                ]
            ]
        ],

        /* --- RÈGLES PAR SERVICE --- */
        "services" => [
            "etat_civil" => [
                "interdits" => [
                    "planifier_intervention",
                    "relancer_patrouille",
                    "transmettre_voirie"
                ],
                "obligatoires" => [
                    "verifier_identite"
                ]
            ],
            "voirie" => [
                "interdits" => [
                    "generer_acte",
                    "envoyer_courrier"
                ],
                "obligatoires" => [
                    "verifier_localisation"
                ]
            ],
            "urbanisme" => [
                "interdits" => [
                    "planifier_intervention",
                    "relancer_patrouille"
                ],
                "obligatoires" => [
                    "verifier_dossier"
                ]
            ],
            "ccas" => [
                "interdits" => [
                    "planifier_intervention",
                    "generer_acte"
                ],
                "obligatoires" => [
                    "verifier_urgence_sociale"
                ]
            ],
            "police" => [
                "interdits" => [
                    "generer_acte",
                    "envoyer_courrier"
                ],
                "obligatoires" => [
                    "evaluer_risque"
                ]
            ]
        ],

        /* --- RÈGLES PAR PRIORITÉ --- */
        "priorites" => [
            "haute" => [
                "interdits" => [
                    "traiter_plus_tard",
                    "ignorer_risque"
                ],
                "obligatoires" => [
                    "evaluer_urgence",
                    "proposer_action_rapide"
                ]
            ],
            "moyenne" => [
                "interdits" => [],
                "obligatoires" => [
                    "verifier_completude"
                ]
            ],
            "basse" => [
                "interdits" => [
                    "declencher_intervention_urgente"
                ],
                "obligatoires" => []
            ]
        ]
    ],

    /* ============================================================
    2) WORKFLOW INTERNE
    ============================================================ */
    "workflow" => [
        "nouvelle" => ["analyse_ia", "en_cours"],
        "analyse_ia" => ["en_cours"],
        "en_cours" => ["resolue"],
        "resolue" => []
    ],

    /* ============================================================
    3) SERVICES MUNICIPAUX
    ============================================================ */
    "services" => [
        "etat_civil" => [
            "nom" => "État civil",
            "responsabilites" => "Actes, mariages, naissances, décès, rectifications.",
            "types_demandes" => ["acte_naissance", "acte_mariage", "acte_deces", "rectification_acte"]
        ],
        "urbanisme" => [
            "nom" => "Urbanisme",
            "responsabilites" => "Permis, déclarations préalables, conformité, certificats.",
            "types_demandes" => ["permis_construire", "declaration_prealable", "certificat_urbanisme"]
        ],
        "ccas" => [
            "nom" => "CCAS / Social",
            "responsabilites" => "Aides sociales, RSA, logement, urgences sociales.",
            "types_demandes" => ["aide_sociale", "rsa", "logement_social", "aide_urgence"]
        ],
        "voirie" => [
            "nom" => "Voirie et travaux publics",
            "responsabilites" => "Chaussées, trottoirs, signalisation, travaux.",
            "types_demandes" => ["nid_poule", "signalement_voirie", "demande_travaux"]
        ],
        "environnement" => [
            "nom" => "Environnement",
            "responsabilites" => "Déchets, espaces verts, nuisances, pollution.",
            "types_demandes" => ["collecte_dechets", "espaces_verts", "signalement_pollution"]
        ],
        "scolaire" => [
            "nom" => "Scolaire et jeunesse",
            "responsabilites" => "Inscriptions, cantine, garderie, activités jeunesse.",
            "types_demandes" => ["inscription_scolaire", "activites_jeunesse", "aide_bourse"]
        ],
        "communication" => [
            "nom" => "Communication",
            "responsabilites" => "Affichage public, événements, réseaux sociaux.",
            "types_demandes" => ["demande_affichage", "evenement_communication"]
        ],
        "culture" => [
            "nom" => "Culture et loisirs",
            "responsabilites" => "Bibliothèques, équipements sportifs, activités culturelles.",
            "types_demandes" => ["reservation_equipement", "demande_activite"]
        ],
        "rh" => [
            "nom" => "Ressources humaines",
            "responsabilites" => "Congés, paie, formation, recrutement.",
            "types_demandes" => ["demande_conge", "question_paie", "demande_formation"]
        ],
        "police" => [
            "nom" => "Police municipale",
            "responsabilites" => "Sécurité, stationnement, interventions.",
            "types_demandes" => ["signalement_securite", "demande_intervention"]
        ],
        "cimetiere" => [
            "nom" => "Cimetières",
            "responsabilites" => "Concessions, inhumations, entretien.",
            "types_demandes" => ["demande_concession", "demande_inhumation"]
        ],
        "transport" => [
            "nom" => "Transport et mobilité",
            "responsabilites" => "Stationnement, mobilité, transports.",
            "types_demandes" => ["demande_stationnement", "signalement_transport"]
        ],
        "evenements" => [
            "nom" => "Évènements et festivités",
            "responsabilites" => "Organisation, logistique, autorisations.",
            "types_demandes" => ["organisation_evenement", "demande_participation"]
        ]
    ],

    /* ============================================================
   TYPES DE DEMANDES → SERVICE COMPÉTENT
   ============================================================ */
    "types_demandes" => [

        /* --- VOIRIE / TRAVAUX PUBLICS --- */
        "nid_poule" => [
            "libelle" => "Nid-de-poule",
            "service" => "voirie",
            "priorite" => "moyenne",
            "delai_standard" => "5 jours ouvrés",
            "documents_requis" => ["photo", "adresse précise"],
            "risques" => ["danger piéton", "détérioration véhicule"],
            "actions_recommandees" => ["transmettre_voirie", "planifier_intervention"]
        ],
        "trottoir_endommage" => [
            "libelle" => "Trottoir endommagé",
            "service" => "voirie",
            "priorite" => "moyenne",
            "delai_standard" => "7 jours ouvrés",
            "documents_requis" => ["photo", "localisation"],
            "risques" => ["chute piéton"],
            "actions_recommandees" => ["transmettre_voirie"]
        ],
        "signalisation_defectueuse" => [
            "libelle" => "Signalisation défectueuse",
            "service" => "voirie",
            "priorite" => "haute",
            "delai_standard" => "48h",
            "documents_requis" => ["photo", "adresse"],
            "risques" => ["accident routier"],
            "actions_recommandees" => ["transmettre_voirie", "alerter_police"]
        ],

        /* --- ENVIRONNEMENT / DÉCHETS / ESPACES VERTS --- */
        "collecte_dechets" => [
            "libelle" => "Problème de collecte des déchets",
            "service" => "environnement",
            "priorite" => "moyenne",
            "delai_standard" => "48h",
            "documents_requis" => ["adresse", "type de bac"],
            "risques" => ["nuisances", "odeurs"],
            "actions_recommandees" => ["transmettre_environnement"]
        ],
        "espaces_verts" => [
            "libelle" => "Entretien des espaces verts",
            "service" => "environnement",
            "priorite" => "basse",
            "delai_standard" => "7 jours ouvrés",
            "documents_requis" => ["localisation"],
            "risques" => [],
            "actions_recommandees" => ["planifier_intervention"]
        ],
        "nuisances_sonores" => [
            "libelle" => "Nuisances sonores",
            "service" => "police",
            "priorite" => "haute",
            "delai_standard" => "immédiat",
            "documents_requis" => ["adresse", "description"],
            "risques" => ["trouble voisinage"],
            "actions_recommandees" => ["transmettre_police"]
        ],

        /* --- URBANISME --- */
        "permis_construire" => [
            "libelle" => "Permis de construire",
            "service" => "urbanisme",
            "priorite" => "haute",
            "delai_standard" => "2 mois",
            "documents_requis" => ["plan de masse", "plan de situation", "photos"],
            "risques" => ["non-conformité", "travaux illégaux"],
            "actions_recommandees" => ["verifier_dossier", "transmettre_urbanisme"]
        ],
        "declaration_prealable" => [
            "libelle" => "Déclaration préalable",
            "service" => "urbanisme",
            "priorite" => "moyenne",
            "delai_standard" => "1 mois",
            "documents_requis" => ["plans", "photos"],
            "risques" => [],
            "actions_recommandees" => ["verifier_dossier"]
        ],

        /* --- SOCIAL / CCAS --- */
        "aide_sociale" => [
            "libelle" => "Aide sociale",
            "service" => "ccas",
            "priorite" => "haute",
            "delai_standard" => "24h",
            "documents_requis" => ["justificatif identité", "justificatif domicile"],
            "risques" => ["urgence sociale"],
            "actions_recommandees" => ["transmettre_ccas", "contacter_agent_social"]
        ],
        "logement_social" => [
            "libelle" => "Demande de logement social",
            "service" => "ccas",
            "priorite" => "moyenne",
            "delai_standard" => "variable",
            "documents_requis" => ["dossier complet"],
            "risques" => [],
            "actions_recommandees" => ["verifier_dossier"]
        ],

        /* --- ÉTAT CIVIL --- */
        "acte_naissance" => [
            "libelle" => "Acte de naissance",
            "service" => "etat_civil",
            "priorite" => "basse",
            "delai_standard" => "48h",
            "documents_requis" => ["identité du demandeur", "lien avec la personne"],
            "risques" => [],
            "actions_recommandees" => ["generer_acte", "envoyer_courrier"]
        ],
        "acte_mariage" => [
            "libelle" => "Acte de mariage",
            "service" => "etat_civil",
            "priorite" => "basse",
            "delai_standard" => "48h",
            "documents_requis" => ["identité"],
            "risques" => [],
            "actions_recommandees" => ["generer_acte"]
        ],

        /* --- POLICE MUNICIPALE --- */
        "signalement_securite" => [
            "libelle" => "Signalement de sécurité",
            "service" => "police",
            "priorite" => "haute",
            "delai_standard" => "immédiat",
            "documents_requis" => ["adresse", "description"],
            "risques" => ["danger immédiat"],
            "actions_recommandees" => ["transmettre_police"]
        ],

        /* --- TRANSPORT / MOBILITÉ --- */
        "demande_stationnement" => [
            "libelle" => "Demande de stationnement",
            "service" => "transport",
            "priorite" => "basse",
            "delai_standard" => "72h",
            "documents_requis" => ["adresse", "justificatif"],
            "risques" => [],
            "actions_recommandees" => ["verifier_dossier"]
        ]
    ],

    /* ============================================================
    5) PRIORITÉS
    ============================================================ */
    "priorites" => [
        "haute" => [
            "description" => "Demande urgente impactant sécurité, santé ou situation sociale.",
            "exemples" => ["signalement_securite", "aide_sociale"]
        ],
        "moyenne" => [
            "description" => "Demande importante nécessitant un traitement dans un délai raisonnable.",
            "exemples" => ["nid_poule", "signalement_voirie"]
        ],
        "basse" => [
            "description" => "Demande non urgente pouvant être traitée à plus long terme.",
            "exemples" => ["acte_naissance", "demande_affichage"]
        ]
    ],
    /* ============================================================
   COMPORTEMENT IA SELON LA PRIORITÉ
   ============================================================ */
    "comportement_priorites" => [

        "haute" => [
            "analyse" => [
                "Identifier immédiatement les risques potentiels",
                "Vérifier s’il existe un danger pour les personnes ou les biens",
                "Repérer les éléments manquants bloquants",
                "Prioriser les actions rapides et les transmissions urgentes"
            ],
            "actions_recommandees" => [
                "Alerter le service compétent",
                "Transmettre en priorité",
                "Informer rapidement l’agent responsable",
                "Proposer une réponse citoyenne rapide et claire"
            ],
            "ton" => "Direct, clair, orienté action, sans alarme inutile"
        ],

        "moyenne" => [
            "analyse" => [
                "Vérifier la complétude du dossier",
                "Identifier les éléments à clarifier",
                "Analyser le contexte métier standard",
                "Repérer les délais raisonnables"
            ],
            "actions_recommandees" => [
                "Transmettre au service compétent",
                "Planifier une intervention si nécessaire",
                "Informer le citoyen du délai standard"
            ],
            "ton" => "Professionnel, structuré, équilibré"
        ],

        "basse" => [
            "analyse" => [
                "Vérifier la conformité de la demande",
                "Identifier les documents requis",
                "Analyser sans urgence particulière"
            ],
            "actions_recommandees" => [
                "Traiter dans les délais standards",
                "Informer le citoyen du délai",
                "Demander des précisions si nécessaire"
            ],
            "ton" => "Calme, administratif, explicatif"
        ]
    ],
    /* ============================================================
   CONTEXTE MÉTIER GLOBAL (SYNTHÈSE POUR L’IA)
   ============================================================ */
    "contexte_metier_global" => [

        /* --- SYNTHÈSE DES STATUTS --- */
        "statuts" => [
            "nouvelle" => "Demande reçue, non encore analysée.",
            "analyse_ia" => "Analyse automatique en cours.",
            "en_cours" => "Demande prise en charge par un agent.",
            "en_attente" => "Demande bloquée en attente d’action.",
            "en_attente_informations" => "Informations citoyen manquantes.",
            "transmis_service" => "Transmise au service compétent.",
            "planifie" => "Intervention programmée.",
            "resolue" => "Traitement terminé.",
            "cloture" => "Dossier fermé.",
            "refuse" => "Demande non recevable."
        ],

        /* --- SYNTHÈSE DES SERVICES --- */
        "services" => [
            "etat_civil" => "Actes, mariages, naissances, décès.",
            "urbanisme" => "Permis, déclarations, conformité.",
            "ccas" => "Aides sociales, urgences, logement.",
            "voirie" => "Chaussées, trottoirs, signalisation.",
            "environnement" => "Déchets, espaces verts, nuisances.",
            "scolaire" => "Inscriptions, cantine, garderie.",
            "communication" => "Affichage, événements.",
            "culture" => "Bibliothèques, équipements sportifs.",
            "rh" => "Congés, paie, formation.",
            "police" => "Sécurité, stationnement, interventions.",
            "cimetiere" => "Concessions, inhumations.",
            "transport" => "Mobilité, stationnement.",
            "evenements" => "Organisation, logistique."
        ],

        /* --- SYNTHÈSE DES PRIORITÉS --- */
        "priorites" => [
            "haute" => "Urgence : sécurité, santé, urgence sociale.",
            "moyenne" => "Important mais non urgent.",
            "basse" => "Traitement standard."
        ],

        /* --- SYNTHÈSE DES ACTIONS POSSIBLES --- */
        "actions" => [
            "analyse" => "Analyser la demande, vérifier complétude.",
            "transmission" => "Transmettre au service compétent.",
            "relance" => "Relancer un service ou un citoyen.",
            "information" => "Informer le citoyen ou un agent.",
            "planification" => "Planifier une intervention.",
            "verification" => "Vérifier avancement, conformité.",
            "cloture" => "Clôturer le dossier si résolu."
        ],

        /* --- SYNTHÈSE DES RÈGLES DE COHÉRENCE --- */
        "coherence" => [
            "etat_civil" => "Aucune intervention technique.",
            "voirie" => "Aucune génération d’acte.",
            "urbanisme" => "Respect des délais réglementaires.",
            "ccas" => "Priorité aux urgences sociales.",
            "police" => "Traitement immédiat des urgences."
        ],

        /* --- SYNTHÈSE DES WORKFLOWS --- */
        "workflow" => [
            "standard" => ["nouvelle", "analyse_ia", "en_cours", "resolue"],
            "avec_attente" => ["nouvelle", "analyse_ia", "en_attente", "en_cours", "resolue"],
            "avec_transmission" => ["nouvelle", "transmis_service", "en_cours", "resolue"]
        ],

        /* --- SYNTHÈSE DES RÉPONSES --- */
        "modeles_reponses" => [
            "accuse" => "Votre demande a bien été prise en compte.",
            "attente_infos" => "Des informations complémentaires sont nécessaires.",
            "transmission" => "Votre demande a été transmise au service compétent.",
            "resolution" => "Votre demande a été traitée."
        ]
    ],
    /* ============================================================
   INTÉGRATION FINALE IA (MASTER BINDING LAYER)
   ============================================================ */
    "integration_finale_ia" => [

        /* --- COMMENT L’IA DOIT UTILISER LE CONTEXTE --- */
        "principes" => [
            "L’IA doit toujours croiser : statut + type de demande + service + priorité.",
            "L’IA doit utiliser les actions métier adaptées au statut.",
            "L’IA doit utiliser les actions suggérées sans jamais les exécuter.",
            "L’IA doit respecter les règles de cohérence (interdits / obligatoires).",
            "L’IA doit utiliser les modèles de réponses contextuels pour formuler les messages.",
            "L’IA doit suivre le workflow métier détaillé pour proposer des actions réalistes.",
            "L’IA doit utiliser le contexte global comme référence en cas d’ambiguïté."
        ],

        /* --- PRIORITÉ D’UTILISATION DES BLOCS --- */
        "ordre_prioritaire" => [
            "1" => "coherence_metier",
            "2" => "statuts",
            "3" => "types_demandes",
            "4" => "services",
            "5" => "priorites",
            "6" => "workflow_metier",
            "7" => "actions_metier",
            "8" => "actions_suggerees",
            "9" => "modeles_reponses_contextuels",
            "10" => "contexte_metier_global"
        ],

        /* --- RÈGLES DE DÉCISION IA --- */
        "regles_decision" => [
            "Si une action est interdite par coherence_metier, l’IA ne doit jamais la proposer.",
            "Si une action est obligatoire, l’IA doit la mentionner dans les propositions.",
            "Si le statut ne permet pas une action, l’IA doit l’ignorer.",
            "Si le type de demande impose un service, l’IA doit s’y conformer.",
            "Si la priorité est haute, l’IA doit proposer des actions rapides et adaptées.",
            "Si plusieurs actions sont possibles, l’IA doit les classer par pertinence métier."
        ],

        /* --- RÈGLES DE FORMULATION --- */
        "formulation" => [
            "L’IA doit toujours rester neutre, professionnelle et administrative.",
            "L’IA doit toujours rappeler que l’agent doit valider toute action.",
            "L’IA doit éviter toute spéculation ou interprétation non fondée.",
            "L’IA doit utiliser les modèles de réponses contextuels quand c’est pertinent."
        ]
    ],

    /* ============================================================
    6) RÈGLES IA (RGPD + LIMITES)
    ============================================================ */
    "regles_ia" => [
        "pas_de_contact_citoyen" => "L'IA ne contacte jamais un citoyen.",
        "pas_de_decision" => "L'IA ne prend aucune décision.",
        "pas_de_modification" => "L'IA ne modifie jamais un dossier.",
        "validation_humaine" => "Toute action doit être validée par un agent.",
        "pas_de_donnees_sensibles" => "L'IA ne demande ni ne génère de données personnelles non nécessaires.",
        "pas_de_memoire_long_terme" => "L'IA ne conserve aucune information après la session."
    ],

    /* ============================================================
    7) TON ET STYLE
    ============================================================ */
    "communication" => [
        "ton" => "Neutre, administratif, professionnel.",
        "style" => "Clair, structuré, concis.",
        "comportement" => "Pas d'émotions, pas d'opinions, pas de spéculation."
    ],

    /* ============================================================
    8) MODÈLES DE RÉPONSES
    ============================================================ */
    "modeles_reponses" => [
        "accuse_reception" => "Bonjour, votre demande a bien été prise en compte.",
        "demande_precisions" => "Afin de traiter votre demande, pourriez-vous préciser les éléments suivants :",
        "incoherence_detectee" => "Une incohérence a été détectée dans votre demande. Merci de vérifier les informations suivantes :",
        "orientation_service" => "Votre demande a été transmise au service compétent."
    ],
    /* ============================================================
   MODÈLES DE RÉPONSES CONTEXTUELS (STATUT + TYPE + PRIORITÉ)
   ============================================================ */
    "modeles_reponses_contextuels" => [

        /* --- VOIRIE --- */
        "voirie" => [
            "nouvelle" => "Votre signalement a bien été reçu. Une analyse est en cours afin d’évaluer la situation et de déterminer les actions nécessaires.",
            "en_cours" => "Votre demande est actuellement en cours de traitement par nos équipes Voirie. Nous reviendrons vers vous dès que des informations supplémentaires seront disponibles.",
            "resolue" => "L’intervention concernant votre signalement a été réalisée. Nous vous remercions de votre vigilance."
        ],

        /* --- ENVIRONNEMENT / DÉCHETS --- */
        "environnement" => [
            "nouvelle" => "Votre demande a été transmise au service Environnement pour analyse.",
            "en_cours" => "Votre demande est en cours de traitement. Une intervention est programmée selon la tournée prévue.",
            "resolue" => "Le service Environnement nous informe que l’intervention a été effectuée."
        ],

        /* --- URBANISME --- */
        "urbanisme" => [
            "nouvelle" => "Votre dossier a été reçu et va être examiné par le service Urbanisme.",
            "en_cours" => "Votre demande est en cours d’instruction conformément aux délais réglementaires.",
            "resolue" => "L’instruction de votre dossier est terminée. Une décision vous a été transmise."
        ],

        /* --- SOCIAL / CCAS --- */
        "ccas" => [
            "nouvelle" => "Votre demande a été transmise au CCAS pour évaluation.",
            "en_cours" => "Votre demande est en cours de traitement par un agent du CCAS.",
            "resolue" => "Votre demande a été traitée. Le CCAS vous a communiqué la décision."
        ],

        /* --- ÉTAT CIVIL --- */
        "etat_civil" => [
            "nouvelle" => "Votre demande d’acte a été prise en compte et sera traitée dans les meilleurs délais.",
            "en_cours" => "Votre acte est en cours de préparation.",
            "resolue" => "Votre acte a été généré et vous a été transmis."
        ],

        /* --- POLICE MUNICIPALE --- */
        "police" => [
            "nouvelle" => "Votre signalement a été transmis à la Police municipale.",
            "en_cours" => "Une intervention est en cours ou programmée.",
            "resolue" => "La Police municipale a traité votre signalement."
        ],

        /* --- TRANSPORT / MOBILITÉ --- */
        "transport" => [
            "nouvelle" => "Votre demande a été transmise au service Mobilité.",
            "en_cours" => "Votre demande est en cours de traitement.",
            "resolue" => "Votre demande a été traitée par le service Mobilité."
        ]
    ]

];
?>
