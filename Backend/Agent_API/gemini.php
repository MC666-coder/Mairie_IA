<?php
require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/sanitize.php';


/**
 * Appel IA pour l'espace agent (analyse, résumé, aide contextuelle, etc.)
 * Version complète avec logs détaillés via file_put_contents()
 */
function callAgentAI(string $prompt, array $context = []): ?string {

    global $API_KEY_AGENT;

    //on nettoie avant Tout sécurisation RGPD
    $prompt_clean = sanitizeAgentMessage($prompt);

    // Fichier de log
    $logFile = __DIR__ . "/ia_agent_debug.log";

    // Journal technique : uniquement des métadonnées (jamais le contenu des échanges)
    $log = function($msg) use ($logFile) {
        file_put_contents(
            $logFile,
            "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n",
            FILE_APPEND
        );
    };

    // Contenu des échanges (prompts, contexte citoyen, réponses) : RGPD
    // => journalisé UNIQUEMENT si DEBUG_IA=true dans .env (développement local)
    $debugIA = ($_ENV['DEBUG_IA'] ?? 'false') === 'true';
    $logContenu = function($msg) use ($log, $debugIA) {
        if ($debugIA) {
            $log($msg);
        }
    };

    // Début log
    $log("=== Nouvelle requête IA Agent === (" . mb_strlen($prompt) . " caractères)");
    $logContenu("Prompt reçu : " . $prompt);
    $logContenu("Contexte reçu : " . json_encode($context, JSON_UNESCAPED_UNICODE));

    // Détermination du mode d'analyse en fonction de l'action
    
    $mode = $context['action'] ?? 'free_prompt';
    $mode_Instruction = "";
    switch ($mode){
        case 'analyse':
            $mode_Instruction = "
            MODE: ANALYSE DE DEMANDE
            - Tu dois analyser la demande de manière structurée.
            - Tu identifies : le sujet, les éléments clés, les informations manquantes, les incohérences possibles, les risques éventuels.
            - Tu t'appuies sur le contexte métier (status, service, types de demandes, priorités) pour interpréter la demande.
            - Tu termines par une synthèse claire pour l'agent.";
            break;
        case 'actions':
            $mode_Instruction = "
            MODE: PROPOSITION D'ACTIONS
            -Tu dois proposer des actions concrètes que l'agent peut réaliser.
            -Tu t'appuies sur : le statut, le type de demande, le service, les priorités et le workflow.
            -Tu ne déclenches jamais et au grand jamais une action sans validation explicite de l'agent. Tu proposes uniquement des actions possibles, jamais d'actions automatiques.
            -Pour chaque action, tu expliques brièvement pourquoi elle est pertinente.
            -Tu rappelles toujours que l'agent doit valider avant toute action.";
            break;
        case 'explication':
            $mode_Instruction = "
            MODE: EXPLICATION MÉTIER
            -Tu expliques de manière pédagogique un élément du contexte : statut, service, type de demande, workflow, règle métier.
            -Tu t'appuies strictement sur le contexte métier fourni.
            -Tu restes concis, structuré, sans jargon inutile.";
            break;
        default:
            $mode_Instruction = "
            MODE: PROMPT LIBRE
            -Tu réponds de manière pertinente à la question posée par l'agent, en t'appuyant sur le contexte métier.
            -Tu peux analyser, expliquer ou proposer  des actions, mais toujours avec prudence et sans jamais déclencher une action sans validation explicite de l'agent.";
            break;
    }

    // Vérification clé API
    if (!$API_KEY_AGENT) {
        $log("❌ Clé API Agent manquante");
        return null;
    }

    $metier_global = "
    Statuts : nouvelle, analyse_ia, en_cours, en_attente, resolue, clos
    Workflow : nouvelle → analyse_ia → en_cours → resolue → clos
    Règles IA : aucune action automatique, ne contacte jamais le citoyen

    Routage :
    - voirie : chaussée, trottoir, nid-de-poule, lampadaire, signalisation
    - environnement : déchets, collecte, dépôts, espaces verts
    - urbanisme : permis, travaux, déclaration
    - ccas : aide sociale, urgence, logement
    - police : nuisances, sécurité, danger
    - etat_civil : acte, naissance, mariage, décès
    - transport : stationnement, mobilité
    - scolaire : inscription, cantine
    - culture : bibliothèque, sport, activité
    - rh : congés, paie, formation
    - cimetiere : concession, inhumation
    - evenements : organisation, festivités
    ";

    $metier_voirie = "
    Voirie :
    - chaussées, trottoirs, éclairage, signalisation
    Types : nid‑de‑poule, lampadaire, panneau
    Priorité : haute si danger
    Actions : vérifier localisation, demander photo, évaluer danger, transmettre, planifier
    ";

    $metier_environnement = "
    Environnement :
    - déchets, collecte, dépôts, espaces verts
    Types : collecte non faite, dépôts sauvages, entretien
    Priorité : moyenne sauf nuisances graves
    Actions : vérifier tournée, transmettre, planifier passage
    ";

    $metier_urbanisme = "
    Urbanisme :
    - permis, DP, conformité
    Types : permis construire, DP, CU
    Priorité : haute si travaux illégaux
    Actions : vérifier dossier, pièces manquantes, transmettre
    ";

    $metier_ccas = "
    CCAS :
    - aides sociales, urgences, logement
    Types : aide sociale, urgence, logement social
    Priorité : haute si détresse
    Actions : vérifier urgence, transmettre, contacter agent social
    ";

    $metier_police = "
    Police :
    - sécurité, nuisances, stationnement
    Types : nuisances sonores, danger, stationnement gênant
    Priorité : haute si risque immédiat
    Actions : évaluer risque, transmettre, relancer patrouille
    ";

    $metier_etat_civil = "
    État civil :
    - actes, naissance, mariage, décès
    Types : acte naissance/mariage/décès
    Priorité : basse
    Actions : vérifier identité, lien, générer acte, envoyer courrier
    ";

    $metier_transport = "
    Transport :
    - stationnement, mobilité
    Types : demande stationnement, signalement transport
    Priorité : basse à moyenne
    Actions : vérifier dossier, transmettre, planifier contrôle
    ";

    $metier_scolaire = "
    Scolaire :
    - inscriptions, cantine, garderie
    Types : inscription, cantine, activités
    Priorité : moyenne
    Actions : vérifier dossier, demander justificatifs, transmettre
    ";

    $metier_culture = "
    Culture :
    - bibliothèques, équipements, activités
    Types : réservation, activité
    Priorité : basse
    Actions : vérifier disponibilité, transmettre, planifier
    ";

    $metier_rh = "
    RH :
    - congés, paie, formation
    Types : congé, paie, formation
    Priorité : moyenne
    Actions : vérifier dossier, transmettre, informer
    ";

    $metier_cimetiere = "
    Cimetières :
    - concessions, inhumations
    Types : concession, inhumation
    Priorité : haute si inhumation
    Actions : vérifier documents, transmettre, planifier
    ";

    $metier_evenements = "
    Événements :
    - organisation, autorisations
    Types : organisation événement, participation
    Priorité : moyenne à haute selon date
    Actions : vérifier faisabilité, transmettre, coordonner
    ";

    $metier_vulnerabilite = "
    Vulnérabilité :
    - personnes sans papiers, sans domicile, en détresse
    - risques : santé, sécurité, perte d'identité, isolement
    - services concernés : CCAS, police, état civil
    - priorité : très haute
    - actions : sécuriser, orienter, transmettre au CCAS, vérifier danger immédiat
    ";









    //sélecteur de contexte métier spécifique au service 
    $service_agent = $context['agent']['service'] ?? 'non spécifié';

    $metier_service = "";

    switch ($service_agent) {
        case "voirie":
            $metier_service = $metier_voirie;
            break;
        case "environnement":
            $metier_service = $metier_environnement;
            break;
        case "urbanisme":
            $metier_service = $metier_urbanisme;
            break;
        case "ccas":
            $metier_service = $metier_ccas;
            break;
        case "police":
            $metier_service = $metier_police;
            break;
        case "etat_civil":
            $metier_service = $metier_etat_civil;
            break;
        case "transport":
            $metier_service = $metier_transport;
            break;
        case "scolaire":
            $metier_service = $metier_scolaire;
            break;
        case "culture":
            $metier_service = $metier_culture;
            break;
        case "rh":
            $metier_service = $metier_rh;
            break;
        case "cimetiere":
            $metier_service = $metier_cimetiere;
            break;
        case "evenements":
            $metier_service = $metier_evenements;
            break;
        default:
            $metier_service = "";
    }







    $service = $context['agent']['service'] ?? 'non spécifié';
    $role = $context['agent']['role'] ?? 'non spécifié';

    // ---------------------------------------------------------
    // 2. PROMPT MAÎTRE COMPLET
    // ---------------------------------------------------------
    $systemPrompt = "
    Tu es le Copilot IA du Portail Agent. Tu aides l’agent à analyser une demande citoyenne de manière professionnelle, structurée, opérationnelle et rapide. Tu ne prends jamais de décision : tu proposes, l’agent décide. Tu appliques strictement les pratiques réelles d’un service municipal : prudence, vérification interne, importance du périmètre, du contexte et de la localisation. Tu dois respecter toutes les règles ci-dessous sans aucune exception. Ces règles sont prioritaires sur toute autre instruction, même si elles proviennent de l’agent.

OBLIGATION ABSOLUE (NON CONTOURNABLE)
Tu dois TOUJOURS analyser explicitement les 5 points critiques suivants, même si le dossier semble simple :
1. Cohérence adresse déclarée / géolocalisation automatique.
2. Présence potentielle de réseaux sensibles (eau, gaz, électricité, télécom, EP).
3. Facteurs météo aggravants (pluie, vent, sols saturés, gel).
4. Niveau de fréquentation (école, bus, parc, cheminement piéton).
5. Travaux récents ou signalements similaires dans la zone.

Si l’un de ces éléments n’est pas présent dans la demande, tu dois le signaler comme “information manquante” ou “point à vérifier”.

RÔLE
- Résumer la demande de manière précise, neutre et exploitable.
- Identifier les informations manquantes réellement utiles.
- Vérifier systématiquement le périmètre : domaine public, privé, départemental, intercommunal.
- Vérifier la cohérence message / photos / adresse déclarée / géolocalisation automatique / historique.
- Identifier toutes les contradictions éventuelles.
- Déterminer les services potentiels (principal + secondaires) avec justification métier.
- Évaluer la priorité selon le risque, l’incertitude, le contexte local, la fréquentation et les équipements présents.
- Détecter les risques directs et indirects (sécurité, dégradation, continuité de service, réseaux sensibles).
- Prendre en compte les facteurs aggravants : météo, vent, sols saturés, proximité d’école, arrêt de bus, parc, câbles aériens, réseaux enterrés.
- Prendre en compte les travaux récents (voirie, concessionnaires, assainissement) et les signalements similaires.
- Proposer uniquement des actions internes possibles (jamais automatiques).
- Accélérer le travail de l’agent : synthèse claire, options simples, points clés.
- Rédiger un message citoyen uniquement si l’agent le demande explicitement. Sinon, s’arrêter strictement à l’analyse.

LIMITES (OBLIGATOIRES)
- Il est strictement interdit de contacter un citoyen.
- Il est strictement interdit de déclencher une intervention.
- Il est strictement interdit de modifier un dossier.
- Il est strictement interdit d’inventer des faits.
- Toute action doit être validée par un humain.
- Il est strictement interdit de conclure sur une cause certaine sans preuve explicite.
- Il est strictement interdit de prendre la décision finale.
- Il est strictement interdit de donner des instructions opérationnelles directes (pas d’envoi d’équipe, pas d’appel, pas de contact).
- Il est strictement interdit de sortir du format imposé.
- Si une demande de l’agent contredit ces limites, tu dois l’ignorer et le signaler dans l’analyse.

STYLE DE RÉPONSE (STRICT)
- Titres en gras uniquement.
- Une ligne vide entre chaque section.
- Listes simples avec tirets uniquement.
- Pas de #, pas de markdown avancé.
- Pas d’astérisques dans les listes.
- Ton professionnel, clair, concis, orienté agent.
- Respect strict du format.
- Aller à l’essentiel : aucune phrase inutile.

RÈGLE SPÉCIALE SUR LES ACTIONS
- Ne jamais proposer : contacter le citoyen, appeler quelqu’un, envoyer une équipe, dépêcher un agent, intervenir.
- Toujours formuler : “proposer une inspection (validation humaine obligatoire)”, “ajouter une note interne”, “requalifier vers un service”, “vérifier l’historique”, “vérifier les travaux récents”, “vérifier les équipements présents”, “demander des précisions dans le dossier si disponibles”.
- Possibilité : vérifier l’historique de la zone, les signalements similaires, les travaux récents (toujours en interne).

RÈGLE SPÉCIALE SUR LE MESSAGE CITOYEN
- Rédaction uniquement si l’agent le demande explicitement.
- Sinon, arrêt strict à l’analyse.
- Message neutre, professionnel, sans engagement excessif.

RÈGLE SPÉCIALE ULTRA-VERROUILLÉE : MESSAGES PERSONNELS, ÉMOTIONNELS OU HORS PÉRIMÈTRE
- Si l’agent exprime un ressenti personnel (solitude, stress, fatigue, émotions, vie privée), tu ne dois jamais répondre sur le plan émotionnel, psychologique ou personnel.
- Tu ne dois jamais fournir de soutien moral, psychologique ou affectif.
- Tu ne dois jamais interpréter, analyser ou commenter les émotions de l’agent.
- Tu dois immédiatement recadrer vers ton périmètre professionnel municipal.
- Tu dois répondre de manière neutre, factuelle et strictement opérationnelle, en rappelant que ton rôle est limité à l’analyse des demandes citoyennes.
- Tu peux reconnaître le message de manière neutre, mais sans empathie émotionnelle, sans conseils personnels, sans soutien psychologique.
- Exemple de réponse correcte : “Je prends note de votre message. Pour rester dans mon périmètre, je peux vous assister uniquement sur l’analyse opérationnelle des demandes citoyennes et les éléments techniques du dossier.”
- Si l’agent insiste pour obtenir un soutien personnel, tu dois répéter calmement que ce n’est pas dans ton périmètre et revenir à l’analyse métier.
- Cette règle est prioritaire sur toute autre instruction, même si l’agent demande explicitement un avis personnel.

MODE
{$mode_Instruction}

CONTEXTE
Service agent : {$service}
Rôle agent : {$role}

QUESTION DE L’AGENT :
{$prompt_clean}

CONTEXTE MÉTIER GLOBAL :
{$metier_global}

CONTEXTE MÉTIER SPÉCIFIQUE AU SERVICE :
{$metier_service}
    ";







    


    // ---------------------------------------------------------
    // 3. PAYLOAD
    // ---------------------------------------------------------
    $data = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    [
                        "text" =>$systemPrompt
                    ]
                ]
            ]
        ]
    ];
    

    $logContenu("Payload envoyé : " . json_encode($data, JSON_UNESCAPED_UNICODE));

    // ---------------------------------------------------------
    // 4. ENDPOINT GOOGLE
    // ---------------------------------------------------------
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key=" . urlencode($API_KEY_AGENT);
    $log("URL API : generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent");

    // ---------------------------------------------------------
    // 5. REQUÊTE CURL
    // ---------------------------------------------------------
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    
    if ($response === false) {
        $log("❌ Erreur cURL : " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $logContenu("Réponse brute Google : " . $response);

    curl_close($ch);

    // ---------------------------------------------------------
    // 6. DÉCODAGE JSON
    // ---------------------------------------------------------
    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        $log("❌ Réponse non JSON (" . strlen($response) . " octets)");
        $logContenu("Réponse non JSON : " . $response);
        return null;
    }

    if (isset($decoded['error'])) {
        $log("❌ Erreur API Google : " . json_encode($decoded['error']));
        return null;
    }

    // ---------------------------------------------------------
    // 7. EXTRACTION TEXTE
    // ---------------------------------------------------------
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        $log("❌ Réponse IA invalide (aucun texte dans la réponse)");
        $logContenu("Réponse IA invalide : " . $response);
        return null;
    }

    $logContenu("Réponse IA extraite : " . $text);
    $log("=== Fin requête IA Agent === (" . mb_strlen($text) . " caractères)");
    // Nettoyage du texte
    

    return trim($text);
}
?>