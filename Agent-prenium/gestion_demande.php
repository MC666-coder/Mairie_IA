<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_agent.php'; // barre latérale, pastilles, assistant IA, agent.css
require_once __DIR__ . '/../Backend/Middleware/auth.php';

/* ---------------------------------------------------------
   1) Sécurité session
--------------------------------------------------------- */
$agent_id = exiger_agent();

require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

$erreur = null;
$success = null;

/* ---------------------------------------------------------
   2) Récupération des infos agent
--------------------------------------------------------- */
try {
    $sql = $pdo->prepare("
        SELECT 
            a.nom AS nom_agent,
            a.prenom AS prenom_agent,
            a.role,
            a.niveau_acces,
            a.identifiant_agent,
            s.nom_service AS nom_service
        FROM agents a
        LEFT JOIN services s ON a.service_id = s.id
        WHERE a.id = ?
    ");
    $sql->execute([$agent_id]);
    $agent = $sql->fetch();

    if (!$agent) {
        session_destroy();
        header("Location: connexion_agent.php");
        exit;
    }
} catch (Exception $e) {
    $erreur = "Erreur interne lors du chargement de votre session.";
}

/* ---------------------------------------------------------
   3) Vérification de l'ID de la demande
--------------------------------------------------------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $erreur = "ID de demande invalide.";
} else {
    $demande_id = intval($_GET['id']);
}

/* ---------------------------------------------------------
   4) Fonction utilitaire : enregistrer une action
--------------------------------------------------------- */
function logAction($pdo, $demande_id, $agent_id, $action, $details = null) {
    $sql = $pdo->prepare("
        INSERT INTO historique_demandes (demande_id, agent_id, action, details)
        VALUES (?, ?, ?, ?)
    ");
    $sql->execute([$demande_id, $agent_id, $action, $details]);
}

/* ---------------------------------------------------------
   5) Récupération de la demande
--------------------------------------------------------- */
if (!$erreur) {
    try {
        $sql = $pdo->prepare("
            SELECT 
                d.*,
                td.libelle AS type_libelle,
                s.nom_service,
                c.prenom_citoyen,
                c.nom_citoyen,
                c.email_citoyen,
                c.telephone_citoyen,
                a.prenom AS agent_prenom,
                a.nom AS agent_nom
            FROM demandes d
            LEFT JOIN types_demandes td ON d.type_id = td.id
            LEFT JOIN services s ON d.service_id = s.id
            LEFT JOIN citoyens c ON d.citoyen_id = c.id
            LEFT JOIN agents a ON d.agent_id = a.id
            WHERE d.id = ?
        ");
        $sql->execute([$demande_id]);
        $demande = $sql->fetch(PDO::FETCH_ASSOC);

        if (!$demande) {
            $erreur = "La demande est introuvable.";
        }

    } catch (Exception $e) {
        $erreur = "Impossible de charger la demande.";
    }
}

/* ---------------------------------------------------------
   STOP SI ERREUR
--------------------------------------------------------- */
if ($erreur) {
    // Pas de page blanche : retour à la liste avec un message rouge
    error_log('gestion_demande : ' . $erreur);
    header('Location: gestion_demandes.php?erreur=demande-introuvable');
    exit;
}

/* ---------------------------------------------------------
   5bis) Nombre de pièces jointes citoyennes
--------------------------------------------------------- */
$pjQuery = $pdo->prepare("
    SELECT COUNT(*) 
    FROM fichiers_demandes 
    WHERE demande_id = ? AND agent_id IS NULL
");
$pjQuery->execute([$demande_id]);
$nb_pj_citoyen = $pjQuery->fetchColumn();




/* ---------------------------------------------------------
   6) Récupération des agents du même service
--------------------------------------------------------- */
if (!$erreur) {
    $agentsService = $pdo->prepare("
        SELECT id, prenom, nom 
        FROM agents 
        WHERE service_id = ?
    ");
    $agentsService->execute([$demande['service_id']]);
    $listeAgents = $agentsService->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   7-8) Notes internes et historique : TOUT est chargé ici.
   Le choix de ce qui est affiché (5 derniers par type, filtres,
   « Afficher tout ») est fait par historique_notes.js.
--------------------------------------------------------- */
require_once __DIR__ . '/../Backend/Fonctions/historique_demande.php';
if (!$erreur) {
    $notes      = notesDemande($pdo, $demande_id);
    $historique = historiqueDemande($pdo, $demande_id);
}


/* ---------------------------------------------------------
   9) Mise à jour du statut (avec AJAX)
--------------------------------------------------------- */


/* ---------------------------------------------------------
   10) Assignation d'un agent (avec AJAX)
--------------------------------------------------------- */


/* ---------------------------------------------------------
   11) Ajout d'une note interne (avec AJAX)
--------------------------------------------------------- */


/* ---------------------------------------------------------
   12) Upload de pièce jointe (agent) (avec AJAX)
--------------------------------------------------------- */


/* ---------------------------------------------------------
   13) Suppression d'une pièce jointe (avec AJAX)
--------------------------------------------------------- */


 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Jeton CSRF lu par demande.js et envoyé avec chaque action (en-tête X-CSRF-Token) -->
    <meta name="csrf-token" content="<?= htmlspecialchars(jeton_csrf(), ENT_QUOTES, 'UTF-8') ?>">
    <title>DEM-<?= (int) $demande['id'] ?> - Espace agent</title>
    <?= feuillesAgent() ?>

    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   connect-src 'self' https://generativelanguage.googleapis.com;">
</head>

<body>

<?= debutPageAgent('demandes', $agent) ?>

<?php
$estAssigne = (int) $demande['agent_id'] === (int) $agent_id;
$citoyen    = trim(($demande['prenom_citoyen'] ?? '') . ' ' . ($demande['nom_citoyen'] ?? '')) ?: 'Citoyen inconnu';
$assigne    = $demande['agent_id'] ? trim(($demande['agent_prenom'] ?? '') . ' ' . ($demande['agent_nom'] ?? '')) : '';
?>

<main class="page-detail-agent mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-10 lg:py-8">

    <!-- Retour à la liste -->
    <a href="gestion_demandes.php" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 -ml-2 text-sm font-semibold text-encre-500 transition hover:bg-white hover:text-encre-900">
        <?= icone('fleche-gauche') ?> Gestion des demandes
    </a>

    <!-- EN-TÊTE DE LA DEMANDE -->
    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="font-mono text-sm font-bold text-encre-500">DEM-<?= (int) $demande['id'] ?></p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-encre-900 sm:text-3xl">
                <?= eAgent($demande['type_libelle'] ?? 'Demande') ?>
                <span class="font-semibold text-encre-400">·</span>
                <span class="font-semibold text-encre-600"><?= eAgent($demande['nom_service'] ?? 'Service non attribué') ?></span>
            </h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-encre-500">
                <span class="inline-flex items-center gap-1.5"><?= icone('horloge') ?> Reçue le <?= eAgent(dateAgent($demande['created_at'])) ?></span>
                <span class="inline-flex items-center gap-1.5"><?= icone('stylo') ?> Mise à jour : <span id="date-mise-a-jour"><?= eAgent($demande['date_mise_a_jour'] ? dateAgent($demande['date_mise_a_jour']) : '—') ?></span></span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- .statut : mise à jour par demande.js après un changement de statut -->
            <span class="statut statut-<?= eAgent($demande['statut']) ?>"><?= eAgent(STATUTS_AGENT[$demande['statut']][0] ?? $demande['statut']) ?></span>
            <span class="rounded-full bg-white px-3 py-1 text-sm ring-1 ring-encre-900/5"><?= textePriorite($demande['priorite']) ?></span>
        </div>
    </div>

    <!-- Messages de réussite / d'erreur des actions (demande.js) -->
    <div id="alert-container" class="notification-agent" aria-live="polite"></div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

        <!-- ============ COLONNE PRINCIPALE : le dossier ============ -->
        <div class="flex min-w-0 flex-col gap-6">

            <!-- Message du citoyen et résumé IA -->
            <section class="carte-agent" aria-labelledby="titre-message">
                <h2 id="titre-message" class="titre-carte"><?= icone('conversation') ?> Message du citoyen</h2>
                <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-encre-800"><?= eAgent($demande['message']) ?></p>
                <?php if (!empty($demande['resume_ia'])): ?>
                    <div class="mt-4 rounded-xl bg-violet-50 p-4 ring-1 ring-violet-600/10">
                        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-700"><?= icone('assistant') ?> Résumé de l’IA</p>
                        <p class="mt-1.5 text-sm whitespace-pre-line text-violet-950"><?= eAgent($demande['resume_ia']) ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CONVERSATION (dans la page : demande.js charge et envoie les messages) -->
            <section id="conversation" class="carte-agent conversation-agent" aria-labelledby="titre-conversation">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="titre-conversation" class="titre-carte"><?= icone('courrier') ?> Conversation avec le citoyen</h2>
                    <!-- Badge « N nouveau(x) » (demande.js) -->
                    <div class="demande-card" data-demande-id="<?= (int) $demande['id'] ?>">
                        <h3 class="sr-only">Nouveaux messages</h3>
                        <span class="badge-nouveaux" id="badge-nouveaux-<?= (int) $demande['id'] ?>"></span>
                    </div>
                </div>

                <!-- Filtres + recherche -->
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <div class="flex gap-1 rounded-xl bg-encre-100 p-1">
                        <button type="button" class="filter-btn active" data-filter="all">Tous</button>
                        <button type="button" class="filter-btn" data-filter="citoyen">Citoyen</button>
                        <button type="button" class="filter-btn" data-filter="agent">Agent</button>
                    </div>
                    <label for="messages-search-input" class="sr-only">Rechercher dans la conversation</label>
                    <input type="search" id="messages-search-input" class="champ-agent min-w-0 flex-1" placeholder="Rechercher dans la conversation…">
                </div>

                <!-- Fil des messages -->
                <div id="messages-timeline" class="messages-timeline mt-3" data-demande-id="<?= (int) $demande_id ?>" role="log" aria-label="Messages"></div>

                <!-- Bouton caché : demande.js « ouvre » la conversation par lui (messages lus, document à envoyer…) -->
                <button type="button" id="open-conversation" data-demande-id="<?= (int) $demande['id'] ?>" hidden>Conversation</button>

                <!-- Envoi d'un message -->
                <form id="form-message-agent" class="mt-3" data-demande-id="<?= (int) $demande_id ?>">
                    <div id="reply-preview" class="reply-preview" style="display:none;">
                        <div class="reply-preview-text"></div>
                        <button type="button" id="cancel-reply" class="cancel-reply-btn" aria-label="Annuler la réponse"><?= icone('fermer') ?></button>
                    </div>

                    <?php if (!$estAssigne): ?>
                        <!-- Règle de la mairie : seul l'agent assigné écrit au citoyen -->
                        <p class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-600/20">
                            <?= icone('cadenas', 'mt-0.5 shrink-0') ?>
                            <span>Seul l’agent assigné à cette demande peut écrire au citoyen. Assignez-vous la demande (colonne de droite) pour lui envoyer un message ou un document.</span>
                        </p>
                    <?php else: ?>
                        <div class="rounded-2xl border border-encre-200 bg-white transition focus-within:border-marque-500 focus-within:ring-4 focus-within:ring-marque-500/15">
                            <label for="message-agent" class="sr-only">Votre message au citoyen</label>
                            <textarea id="message-agent" name="message" rows="3" required
                                      placeholder="Écrire au citoyen (ex. merci de fournir un justificatif de domicile)…"
                                      class="block w-full resize-y rounded-t-2xl border-0 bg-transparent px-4 py-3 text-sm outline-none"></textarea>

                            <!-- Document du dossier prêt à partir (bouton « Envoyer au citoyen » d'un document interne) -->
                            <input type="hidden" name="fichier_id" id="fichier-dossier-id" value="">
                            <div class="mx-3 mb-2 flex items-center justify-between gap-2 rounded-lg bg-marque-50 px-3 py-2 text-sm text-marque-800" id="document-dossier-pret" hidden>
                                <span class="inline-flex min-w-0 items-center gap-1.5"><?= icone('trombone') ?> <span id="document-dossier-nom" class="truncate"></span></span>
                                <button type="button" id="annuler-document-dossier" class="grid size-7 place-items-center rounded-full hover:bg-marque-100" aria-label="Ne pas joindre ce document"><?= icone('fermer') ?></button>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-encre-100 px-3 py-2">
                                <!-- Document envoyé au citoyen (attestation...), visible dans sa conversation -->
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-encre-600 hover:bg-encre-100" id="joindre-nouveau-fichier">
                                    <?= icone('trombone') ?> <span>Joindre un document</span>
                                    <input type="file" name="fichier" id="fichier-pour-citoyen" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="max-w-40 text-xs file:hidden">
                                </label>
                                <button type="submit" class="send-btn inline-flex cursor-pointer items-center gap-2 rounded-xl bg-marque-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-marque-700">
                                    <?= icone('envoyer') ?> <span>Envoyer au citoyen</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </section>

            <!-- DOCUMENTS DU DOSSIER : trois cases (remplies par demande.js via get_pj.php) -->
            <section class="carte-agent pieces-jointes" aria-labelledby="titre-documents">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="titre-documents" class="titre-carte"><?= icone('trombone') ?> Documents du dossier <span id="pj-count" class="pj-count-badge">0</span></h2>
                    <label for="pj-search" class="sr-only">Rechercher un document</label>
                    <input type="search" id="pj-search" placeholder="Rechercher un document…" class="champ-agent w-full sm:w-56">
                </div>

                <div class="liste-pj documents-dossier mt-4 grid gap-3 lg:grid-cols-3" id="liste-pj" data-demande-id="<?= (int) $demande['id'] ?>">
                    <div class="case-dossier">
                        <h3 class="case-dossier-titre"><?= icone('boite-reception') ?> Reçus du citoyen <span class="pj-count-badge" id="pj-count-recus">0</span></h3>
                        <ul class="liste-dossier" id="pj-recus"><li class="dossier-vide">Chargement…</li></ul>
                    </div>
                    <div class="case-dossier">
                        <h3 class="case-dossier-titre"><?= icone('envoyer') ?> Envoyés au citoyen <span class="pj-count-badge" id="pj-count-envoyes">0</span></h3>
                        <ul class="liste-dossier" id="pj-envoyes"><li class="dossier-vide">Chargement…</li></ul>
                    </div>
                    <div class="case-dossier case-interne">
                        <h3 class="case-dossier-titre"><?= icone('cadenas') ?> Internes à la mairie <span class="pj-count-badge" id="pj-count-internes">0</span></h3>
                        <ul class="liste-dossier" id="pj-internes"><li class="dossier-vide">Chargement…</li></ul>
                    </div>
                </div>

                <!-- Ajout d'un document INTERNE (AJAX, upload_pj.php) -->
                <form enctype="multipart/form-data" class="form-upload mt-4 flex flex-col gap-2 rounded-xl border border-dashed border-encre-300 bg-encre-50 p-3 sm:flex-row sm:items-center" data-demande-id="<?= (int) $demande['id'] ?>">
                    <label for="fichier" class="flex items-center gap-1.5 text-sm font-medium text-encre-700"><?= icone('cadenas') ?> Document interne :</label>
                    <input type="file" name="fichier" id="fichier" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" required
                           class="min-w-0 flex-1 text-sm text-encre-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-encre-700 file:ring-1 file:ring-encre-200">
                    <button type="submit" class="cursor-pointer rounded-xl bg-encre-900 px-4 py-2 text-sm font-semibold text-white hover:bg-encre-800">Ajouter au dossier</button>
                </form>
                <p class="mt-2 text-xs text-encre-500">
                    Un document interne n’est jamais visible par le citoyen. Pour lui envoyer un document (attestation…), passez par la conversation.
                </p>
            </section>
        </div>

        <!-- ============ COLONNE DE DROITE : traitement ============ -->
        <aside class="flex min-w-0 flex-col gap-6" aria-label="Traitement de la demande">

            <!-- Statut et assignation -->
            <section class="carte-agent" aria-labelledby="titre-traitement">
                <h2 id="titre-traitement" class="titre-carte"><?= icone('valide') ?> Traitement</h2>

                <form id="form-statut" class="mt-4" data-demande-id="<?= (int) $demande_id ?>">
                    <label for="statut" class="etiquette-agent">Statut</label>
                    <div class="mt-1 flex gap-2">
                        <select name="statut" id="statut" required class="champ-agent min-w-0 flex-1">
                            <?php foreach (STATUTS_AGENT as $cle => [$libelle]): ?>
                                <option value="<?= $cle ?>" <?= $demande['statut'] === $cle ? 'selected' : '' ?>><?= eAgent($libelle) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" id="btn-update-statut" class="bouton-agent">Mettre à jour</button>
                    </div>
                </form>

                <div class="mt-5 border-t border-encre-100 pt-4">
                    <p class="etiquette-agent">Agent assigné</p>
                    <p class="mt-1 flex items-center gap-2 text-sm font-medium text-encre-900">
                        <?= icone('utilisateur', 'text-encre-400') ?>
                        <span id="agent-actuel"><?= $assigne !== '' ? eAgent($assigne) : '<em class="font-normal text-encre-400">Aucun agent assigné</em>' ?></span>
                    </p>
                    <form id="form-assignation" class="mt-3" data-demande-id="<?= (int) $demande_id ?>" data-moi="<?= (int) $agent_id ?>">
                        <label for="agent_id" class="sr-only">Assigner à</label>
                        <div class="flex gap-2">
                            <select name="agent_id" id="agent_id" required class="champ-agent min-w-0 flex-1">
                                <option value="">Choisir un agent…</option>
                                <?php foreach ($listeAgents as $ag): ?>
                                    <option value="<?= (int) $ag['id'] ?>"><?= (int) $ag['id'] === (int) $agent_id ? 'Moi (' . eAgent($ag['prenom'] . ' ' . $ag['nom']) . ')' : eAgent($ag['prenom'] . ' ' . $ag['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assigner_agent" class="bouton-agent">Assigner</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Citoyen -->
            <section class="carte-agent" aria-labelledby="titre-citoyen">
                <h2 id="titre-citoyen" class="titre-carte"><?= icone('utilisateur') ?> Citoyen</h2>
                <p class="mt-3 text-sm font-semibold text-encre-900"><?= eAgent($citoyen) ?></p>
                <ul class="mt-2 grid gap-1.5 text-sm text-encre-600">
                    <?php if (!empty($demande['email_citoyen'])): ?>
                        <li><a href="mailto:<?= eAgent($demande['email_citoyen']) ?>" class="inline-flex items-center gap-1.5 break-all text-marque-700 hover:underline"><?= icone('courrier') ?> <?= eAgent($demande['email_citoyen']) ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($demande['telephone_citoyen'])): ?>
                        <li class="inline-flex items-center gap-1.5"><?= icone('telephone') ?> <?= eAgent($demande['telephone_citoyen']) ?></li>
                    <?php endif; ?>
                    <li class="inline-flex items-center gap-1.5"><?= icone('trombone') ?> Pièces jointes envoyées : <span class="pj-count-badge" id="pj-citoyen-count"><?= (int) $nb_pj_citoyen ?></span></li>
                </ul>
            </section>

            <!-- Notes internes (historique_notes.js) -->
            <section class="carte-agent notes-internes" aria-labelledby="titre-notes">
                <h2 id="titre-notes" class="titre-carte"><?= icone('stylo') ?> Notes internes</h2>

                <form class="form-note mt-3" id="form-note" data-demande-id="<?= (int) $demande_id ?>">
                    <label for="note" class="sr-only">Ajouter une note</label>
                    <textarea name="note" id="note" rows="3" required placeholder="Ajouter une note pour les agents…" class="champ-agent w-full resize-y"></textarea>
                    <button type="submit" name="ajouter_note" class="bouton-agent mt-2 w-full">Ajouter la note</button>
                </form>

                <div class="notes-toolbar mt-4 grid gap-2">
                    <div class="notes-filters filtres-pilules">
                        <button type="button" class="notes-filter-btn active" data-filter="all">Tous</button>
                        <button type="button" class="notes-filter-btn" data-filter="month">Mois</button>
                        <button type="button" class="notes-filter-btn" data-filter="24h">24h</button>
                        <button type="button" class="notes-filter-btn" data-filter="week">Semaine</button>
                    </div>
                    <label for="notes-search-input" class="sr-only">Rechercher une note</label>
                    <input type="search" id="notes-search-input" placeholder="Rechercher une note…" class="champ-agent w-full">
                </div>

                <div class="notes-mois filtres-pilules mt-2" id="notes-mois" role="group" aria-label="Choisir le mois des notes" hidden></div>
                <p class="liste-compteur" id="notes-compteur" aria-live="polite"></p>
                <button type="button" class="btn-reduire" id="reduire-notes-haut" hidden><?= icone('chevron-haut') ?> Réduire les notes</button>

                <div class="notes-timeline" id="liste-notes" data-demande-id="<?= (int) $demande_id ?>">
                    <?= htmlNotes($notes ?? []) ?>
                </div>
                <button type="button" id="show-full-notes" class="history-expand-btn"></button>
            </section>

            <!-- Historique des actions (historique_notes.js) -->
            <section class="carte-agent historique-actions" aria-labelledby="titre-historique">
                <h2 id="titre-historique" class="titre-carte"><?= icone('horloge') ?> Historique</h2>

                <div class="history-toolbar mt-3 grid gap-2">
                    <div class="history-filters filtres-pilules">
                        <button type="button" class="history-filter-btn active" data-filter="all">Toutes</button>
                        <button type="button" class="history-filter-btn" data-filter="status">Statuts</button>
                        <button type="button" class="history-filter-btn" data-filter="assignation">Assignations</button>
                        <button type="button" class="history-filter-btn" data-filter="notes">Notes</button>
                    </div>
                    <label for="history-search-input" class="sr-only">Rechercher une action</label>
                    <input type="search" id="history-search-input" placeholder="Rechercher une action…" class="champ-agent w-full">
                </div>

                <p class="liste-compteur" id="history-compteur" aria-live="polite"></p>
                <button type="button" class="btn-reduire" id="reduire-historique-haut" hidden><?= icone('chevron-haut') ?> Réduire l’historique</button>

                <div class="history-timeline" id="history-timeline" data-demande-id="<?= (int) $demande_id ?>">
                    <?= htmlHistorique($historique ?? []) ?>
                </div>
                <button type="button" id="show-full-history" class="history-expand-btn"></button>
            </section>
        </aside>
    </div>
</main>

<?= finPageAgent() ?>

<script src="../Frontend/assets/js/historique_notes.js?v=<?= @filemtime(__DIR__ . '/../Frontend/assets/js/historique_notes.js') ?>" defer></script>
<script src="../Frontend/assets/js/demande.js?v=<?= @filemtime(__DIR__ . '/../Frontend/assets/js/demande.js') ?>" defer></script>
<script src="assets/js/detail_demande.js?v=<?= @filemtime(__DIR__ . '/assets/js/detail_demande.js') ?>" defer></script>

</body>
</html>
