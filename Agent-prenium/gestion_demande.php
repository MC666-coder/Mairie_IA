<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';

require_once __DIR__ . '/../Backend/Config/db.php';



$erreur = null;
$success = null;

/* ---------------------------------------------------------
   1) Sécurité session
--------------------------------------------------------- */
if (!isset($_SESSION['agent_id'])) {
    header("Location: connexion_agent.php");
    exit;
}

$agent_id = $_SESSION['agent_id'];

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
    echo "<p style='color:red;font-weight:bold;'>$erreur</p>";
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
   7) Récupération des notes internes
--------------------------------------------------------- */
if (!$erreur) {
    $notesQuery = $pdo->prepare("
        SELECT n.*, a.prenom, a.nom
        FROM notes_demandes n
        LEFT JOIN agents a ON n.agent_id = a.id
        WHERE n.demande_id = ?
        ORDER BY n.created_at DESC
    ");
    $notesQuery->execute([$demande_id]);
    $notes = $notesQuery->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   8) Récupération de l'historique
--------------------------------------------------------- */
if (!$erreur) {
    $historiqueQuery = $pdo->prepare("
        SELECT h.*, a.prenom, a.nom
        FROM historique_demandes h
        LEFT JOIN agents a ON h.agent_id = a.id
        WHERE h.demande_id = ?
        ORDER BY h.created_at DESC
    ");
    $historiqueQuery->execute([$demande_id]);
    $historique = $historiqueQuery->fetchAll(PDO::FETCH_ASSOC);
}

/* -------------------------------------------------------------------------
 Fonction qui va detecter le type d'action dans l'historique des actions
--------------------------------------------------------------------------*/
function detectType($action) {
    if (stripos($action, "statut") !== false) return "status";
    if (stripos($action, "assign") !== false) return "assignation";
    if (stripos($action, "note") !== false) return "notes";
    return "other";
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
    <title>Détail de la demande</title>
    <link rel="stylesheet" href="../Frontend/assets/css/gestion_demande.css">
</head>

<body>

<div class="page-wrapper">
    <header id="header-agent">

        <div class="rf-banner">
            <span class="rf-title">République Française</span>
            <span class="rf-subtitle">Liberté • Égalité • Fraternité</span>
        </div>

        <div class="header-main">

            <nav id="nav-agent">
                <a href="dashboard_agent.php" >Tableau de Bord</a>
                <a href="gestion_demandes.php" class="active">Gestion des demandes</a>

                <a href="logout.php" class="nav-btn">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="#eef0f2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                    </span>
                    Déconnexion
                </a>
            </nav>

            <div id="bulle-connexion-agent">
                Identifiant Agent : <?= htmlspecialchars($agent['identifiant_agent']) ?><br>
                Prénom : <?= htmlspecialchars($agent['prenom_agent']) ?><br>
                Nom : <?= htmlspecialchars($agent['nom_agent']) ?><br>
                Rôle : <?= htmlspecialchars($agent['role']) ?><br>
                Service : <?= htmlspecialchars($agent['nom_service']) ?><br>
                Niveau d'accès : <?= htmlspecialchars($agent['niveau_acces']) ?>
            </div>

        </div>
    </header>
    


    <h1>Détail de la demande DEM-<?= htmlspecialchars($demande['id']) ?></h1>

    <!-- affiche des erreurs ou des messages de succès avec AJAX -->
     <div id="alert-container"></div>

    <!-- Informations principales -->
    <section class="demande-info">

        <p><strong>Type :</strong> <?= htmlspecialchars($demande['type_libelle'] ?? 'Non renseigné') ?></p>

        <p><strong>Message :</strong><br>
            <?= nl2br(htmlspecialchars($demande['message'])) ?>
        </p>

        <p><strong>Résumé IA :</strong><br>
            <?= nl2br(htmlspecialchars($demande['resume_ia'] ?? 'Non renseigné')) ?>
        </p>

        <p><strong>Priorité :</strong> <?= htmlspecialchars($demande['priorite']) ?></p>

        <p><strong>Statut actuel :</strong> 
            <span class="statut statut-<?= htmlspecialchars($demande['statut']) ?>">
                <?= htmlspecialchars($demande['statut']) ?>
            </span>
        </p>

        <p><strong>Service concerné :</strong> <?= htmlspecialchars($demande['nom_service']) ?></p>

        <p><strong>Agent assigné :</strong> 
            <?php if ($demande['agent_id']): ?>
                <?= htmlspecialchars($demande['agent_prenom'] . ' ' . $demande['agent_nom']) ?>
            <?php else: ?>
                <em>Aucun agent assigné</em>
            <?php endif; ?>
        </p>

        <p><strong>Citoyen :</strong>
            <?= htmlspecialchars($demande['prenom_citoyen'] . ' ' . $demande['nom_citoyen']) ?>
        </p>

        <p><strong>Email :</strong> <?= htmlspecialchars($demande['email_citoyen']) ?></p>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($demande['telephone_citoyen']) ?></p>

        <p><strong>Soumise le :</strong> <?= htmlspecialchars($demande['created_at']) ?></p>

        <p><strong>Dernière mise à jour :</strong> 
            <span id="date-mise-a-jour"><?= htmlspecialchars($demande['date_mise_a_jour'] ?? 'Non renseigné') ?></span>
        </p>

        <p><strong>Pièces jointes citoyen :</strong>
            <span class="pj-count-badge"><?= $nb_pj_citoyen ?></span>
        </p>

        <div class="demande-card" data-demande-id="<?= $demande['id'] ?>">
            <h3><?= htmlspecialchars($demande['titre']?? 'Suivi des nouveaux messages') ?></h3>
            <span class="badge-nouveaux" id="badge-nouveaux-<?= $demande['id'] ?>"></span>
        </div>


    </section>


    <!-- Bouton d’ouverture -->
    <div class="open-conv-container">
        <button id="open-conversation" data-demande-id="<?= $demande['id'] ?>"class="open-conv-btn">
            Voir les Conversations
        </button>
    </div>

    <!-- Fond flouté -->
    <div id="conversation-overlay" class="conversation-overlay"></div>

    <!-- Fenêtre modale -->
    <div id="conversation-modal" class="conversation-modal">
        <div class="modal-inner">
            <!-- Bouton de fermeture -->
            <button id="close-conversation" class="close-conv-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 6H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </button>






            <!--  INTERFACE EXACTE -->
            <div class="conversation-section elegant-conversation">

                <!-- Timeline des messages + Conversation agent/citoyen -->
                <div class="conversation-section elegant-conversation">

                    <!-- Toolbar : Filtres + Recherche -->
                    <div class="messages-toolbar">
                        <button class="filter-btn active" data-filter="all">Tous</button>
                        <button class="filter-btn" data-filter="citoyen">Citoyen</button>
                        <button class="filter-btn" data-filter="agent">Agent</button>

                        <input type="text" id="messages-search-input" class="messages-search-input" placeholder="Rechercher...">
                    </div>

                    <!-- Zone de messages -->
                    <div id="messages-timeline"
                        class="messages-timeline whatsapp-thread"
                        data-demande-id="<?= $demande_id ?>">
                    </div>

                    <!-- Formulaire d’envoi -->
                    <form id="form-message-agent"
                        class="message-input-bar"
                        data-demande-id="<?= $demande_id ?>">

                        <!-- Aperçu réponse ciblée -->
                        <div id="reply-preview" class="reply-preview" style="display:none;">
                            <div class="reply-preview-text"></div>
                            <button type="button" id="cancel-reply" class="cancel-reply-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 6H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                            </button>

                        </div>

                        <textarea id="message-agent"
                                name="message"
                                placeholder="Écrire un message..."
                                required></textarea>

                        <button type="submit" class="send-btn">
                            <span>Envoyer</span>
                        </button>
                    </form>

                </div>
            </div>

        </div>

            
        

    </div>



    


    <!-- Formulaire de changement de statut -->
    <section class="update-statut">
        <h2>Modifier le statut</h2>

        <form  method="POST" id="form-statut" data-demande-id="<?= $demande_id ?>">
            <label for="statut">Nouveau statut :</label>
            <select name="statut" id="statut" required>
                <option value="nouvelle" <?= $demande['statut'] === 'nouvelle' ? 'selected' : '' ?>>Nouvelle</option>
                <option value="analyse_ia" <?= $demande['statut'] === 'analyse_ia' ? 'selected' : '' ?>>Analyse IA</option>
                <option value="en_cours" <?= $demande['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                <option value="resolue" <?= $demande['statut'] === 'resolue' ? 'selected' : '' ?>>Résolue</option>
                <option value="cloturee" <?= $demande['statut'] === 'cloturee' ? 'selected' : '' ?>>Clôturée</option>
            </select>

            <button type="submit" id="btn-update-statut">Mettre à jour</button>
        </form>
    </section>

    <!-- Assignation d'un agent -->
    <section class="assignation-agent">
        <h2>Assigner la demande à un agent</h2>

        <p><strong>Agent actuellement assigné :</strong>
            <span id="agent-actuel">
                <?php if ($demande['agent_id']): ?>
                    <?php
                        $agentNom = $pdo->prepare("SELECT prenom, nom FROM agents WHERE id = ?");
                        $agentNom->execute([$demande['agent_id']]);
                        $agentInfo = $agentNom->fetch();
                    ?>
                    <?= htmlspecialchars($agentInfo['prenom'] . ' ' . $agentInfo['nom']) ?>
                <?php else: ?>
                    <em>Aucun agent assigné</em>
                <?php endif; ?>
            </span>
        </p>

        <form method="POST" id="form-assignation" data-demande-id="<?= $demande_id ?>">
            <label for="agent_id">Assigner à :</label>
            <select name="agent_id" id="agent_id" required>
                <option value="">-- Sélectionner un agent --</option>
                <?php foreach ($listeAgents as $ag): ?>
                    <option value="<?= $ag['id'] ?>">
                        <?= htmlspecialchars($ag['prenom'] . ' ' . $ag['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="assigner_agent">Assigner</button>
        </form>
    </section>

    <!-- Notes internes -->
    <section class="notes-internes">
        <h2>Notes internes</h2>

        <button id="show-full-notes" class="history-expand-btn">Afficher toutes les notes</button>

        <form method="POST" class="form-note" id="form-note" data-demande-id="<?= $demande_id ?>">
            <label for="note">Ajouter une note :</label>
            <textarea name="note" id="note" rows="4" required></textarea>

            <button type="submit" name="ajouter_note">Ajouter la note</button>
        </form>

        <!-- Barre de filtres / recherche -->
        <div class="notes-toolbar">
            <div class="notes-filters">
                <button class="notes-filter-btn active" data-filter="all">Tous</button>
                <button class="notes-filter-btn" data-filter="month">Mois</button>
                <button class="notes-filter-btn" data-filter="24h">24h</button>
                <button class="notes-filter-btn" data-filter="week">Cette semaine</button>
            </div>
            <div class="notes-search">
                <input type="text" id="notes-search-input" placeholder="Rechercher une note...">
            </div>
        </div>

        <!-- Timeline -->
        <div class="notes-timeline" id="liste-notes">
            <?php if (empty($notes)): ?>
                <p class="notes-empty"><em>Aucune note interne pour le moment.</em></p>
            <?php else: ?>
                <?php
                    // Regroupement par date (YYYY-MM-DD)
                    $grouped = [];
                    foreach ($notes as $n) {
                        $dateKey = substr($n['created_at'], 0, 10);
                        $grouped[$dateKey][] = $n;
                    }

                    // Fonction label humain
                    function humanDateLabel($dateKey) {
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        if ($dateKey === $today) return "Aujourd'hui";
                        if ($dateKey === $yesterday) return "Hier";
                        return $dateKey;
                    }
                ?>

                <?php foreach ($grouped as $dateKey => $notesDuJour): ?>
                    <div class="notes-date-group" data-date-group="<?= htmlspecialchars($dateKey) ?>">
                        <div class="notes-date-separator">
                            <span><?= htmlspecialchars(humanDateLabel($dateKey)) ?></span>
                        </div>

                        <?php foreach ($notesDuJour as $n): ?>
                            <?php
                                $initiales = mb_strtoupper(
                                    mb_substr($n['prenom'], 0, 1) .
                                    mb_substr($n['nom'], 0, 1)
                                );
                            ?>
                            <div class="note-item"
                                data-agent-id="<?= (int)$n['agent_id'] ?>"
                                data-created-at="<?= htmlspecialchars($n['created_at']) ?>"
                                data-text="<?= htmlspecialchars($n['note']) ?>">
                                <div class="note-timeline-dot"></div>
                                <div class="note-content">
                                    <div class="note-header">
                                        <div class="note-avatar"><?= htmlspecialchars($initiales) ?></div>
                                        <div class="note-meta-main">
                                            <span class="note-author">
                                                <?= htmlspecialchars($n['prenom'] . ' ' . $n['nom']) ?>
                                            </span>
                                            <span class="note-date">
                                                <?= htmlspecialchars($n['created_at']) ?>
                                            </span>
                                        </div>
                                        <div class="note-tags">
                                            <span class="note-tag note-tag-info">Note</span>
                                        </div>
                                    </div>
                                    <div class="note-body">
                                        <?= nl2br(htmlspecialchars($n['note'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>


        

        <!-- Pièces jointes -->
        <section class="pieces-jointes">
            <h2>
                Pièces jointes 
                <span id="pj-count" class="pj-count-badge">0</span>
            </h2>

            <!-- FORMULAIRE UPLOAD (AJAX) -->
            <form enctype="multipart/form-data" 
                class="form-upload" 
                data-demande-id="<?= $demande['id'] ?>">
                
                <label for="fichier">Ajouter une pièce jointe :</label>
                <input type="file" name="fichier" id="fichier" required>
                <button type="submit">Téléverser</button>
            </form>

            <input type="text" id="pj-search" placeholder="Rechercher une pièce jointe..." class="pj-search">

            <!-- LISTE DES PIÈCES JOINTES (chargée en AJAX) -->
            <div class="liste-pj" id="liste-pj"data-demande-id="<?= $demande['id'] ?>">
                <p><em>Chargement...</em></p>
            </div>
        </section>



    <!-- Historique des actions -->
    <section class="historique-actions">
        <h2>Historique des actions</h2>

        <button id="show-full-history" class="history-expand-btn">Afficher tout l’historique</button>

        <!-- Barre de filtres -->
        <div class="history-toolbar">
            <div class="history-filters">
                <button class="history-filter-btn active" data-filter="all">Toutes</button>
                <button class="history-filter-btn" data-filter="status">Statuts</button>
                <button class="history-filter-btn" data-filter="assignation">Assignations</button>
                <button class="history-filter-btn" data-filter="notes">Notes internes</button>
            </div>

            <div class="history-search">
                <input type="text" id="history-search-input" placeholder="Rechercher une action...">
            </div>
        </div>

       <?php // Charger tout l’historique complet
        $allQuery = $pdo->prepare("
            SELECT h.*, a.prenom, a.nom
            FROM historique_demandes h
            LEFT JOIN agents a ON h.agent_id = a.id
            WHERE h.demande_id = ?
            ORDER BY h.created_at DESC
        ");
        $allQuery->execute([$demande_id]);
        $all = $allQuery->fetchAll(PDO::FETCH_ASSOC);

        // Séparer par type
        $assignations = array_filter($all, fn($h) => detectType($h['action']) === 'assignation');
        $notes        = array_filter($all, fn($h) => detectType($h['action']) === 'notes');
        $statuts      = array_filter($all, fn($h) => detectType($h['action']) === 'status');
        $autres       = array_filter($all,fn($h)=> detectType($h['action']) === 'other');

        $autres = array_filter($all, fn($h) =>
            stripos($h['action'], 'assign') === false &&
            stripos($h['action'], 'note') === false &&
            stripos($h['action'], 'statut') === false 
        );

        // Prendre les 5 dernières de chaque type
        $assignations = array_slice($assignations, 0, 5);
        $notes = array_slice($notes, 0, 5);
        $statuts = array_slice($statuts, 0, 5);
        $autres = array_slice($autres, 0, 5);

        // Fusionner
        $compact = array_merge($assignations, $notes, $statuts, $autres);

        // Trier par date DESC
        usort($compact, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        // C’est CE tableau qui sera affiché avant le bouton
        $historique = $compact;
        ?>
    

        <?php if (empty($historique)): ?>
            <p><em>Aucune action enregistrée pour cette demande.</em></p>
        <?php else: ?>

            <div class="history-timeline" id="history-timeline">

                <?php
                    // Regroupement par date
                    $grouped = [];
                    foreach ($historique as $h) {
                        $dateKey = substr($h['created_at'], 0, 10);
                        $grouped[$dateKey][] = $h;
                    }

                    function humanDate($date) {
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        if ($date === $today) return "Aujourd'hui";
                        if ($date === $yesterday) return "Hier";
                        return date('d/m/Y', strtotime($date));
                    }
                ?>

                <?php foreach ($grouped as $dateKey => $actions): ?>
                    <div class="history-date-group" data-date="<?= $dateKey ?>">
                        <div class="history-date-separator">
                            <span><?= humanDate($dateKey) ?></span>
                        </div>

                        <?php foreach ($actions as $h): ?>

                            <?php
                                // Détection du type d’action
                                $type = detectType($h['action']);
                                

                                // Initiales
                                $initiales = mb_strtoupper(
                                    mb_substr($h['prenom'], 0, 1) .
                                    mb_substr($h['nom'], 0, 1)
                                );
                            ?>

                            <div class="history-item"
                                data-type="<?= $type ?>"
                                data-text="<?= htmlspecialchars($h['action'] . ' ' . $h['details']) ?>">

                                <div class="history-dot history-dot-<?= $type ?>"></div>

                                <div class="history-content">
                                    <div class="history-header">
                                        <div class="history-avatar"><?= $initiales ?></div>

                                        <div class="history-meta">
                                            <span class="history-author">
                                                <?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?>
                                            </span>
                                            <span class="history-time">
                                                <?= htmlspecialchars($h['created_at']) ?>
                                            </span>
                                        </div>

                                        <span class="history-tag history-tag-<?= $type ?>">
                                            <?= ucfirst($type) ?>
                                        </span>
                                    </div>

                                    <div class="history-body">
                                        <strong><?= htmlspecialchars($h['action']) ?></strong><br>
                                        <?= nl2br(htmlspecialchars($h['details'])) ?>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section>


    <a href="dashboard_agent.php" class="btn-retour">← Retour au tableau de bord</a>

</div>
<script>
    window.CURRENT_AGENT_ID = <?= (int)$_SESSION['agent_id'] ?>;
</script>
<script src="../Frontend/assets/js/ouvrir_notes.js"></script>
<script src="../Frontend/assets/js/ouvrir_historique.js"></script>
<script src="../Frontend/assets/js/ouvrir_conv.js"></script>
<script src="../Frontend/assets/js/demande.js"></script>


</body>
</html>
