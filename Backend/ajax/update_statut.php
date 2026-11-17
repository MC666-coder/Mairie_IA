<?php
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Config/db.php';
/*/ ajout : fonction notification
require_once __DIR__ . '/../Fonctions/pushnotification.php';
require_once __DIR__ . '/../Fonctions/getCitoyenIdFromDemande.php';
require_once __DIR__ . '/../Fonctions/getEmailCitoyen.php';
require_once __DIR__ . '/../Fonctions/sendMail.php';*/



// Vérification ID demande
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "ID de demande invalide."]);
    exit;
}

$demande_id = intval($_GET['id']);
$nouveau_statut = $_POST['statut'] ?? null;

if (!$nouveau_statut) {
    echo json_encode(["success" => false, "message" => "Statut manquant."]);
    exit;
}

// Liste blanche : seules les valeurs de l'ENUM `demandes.statut` sont acceptées
// (empêche l'injection de HTML/JavaScript dans l'historique — faille XSS)
$statuts_autorises = [
    'nouvelle'   => 'Nouvelle',
    'analyse_ia' => 'Analyse IA',
    'en_cours'   => 'En cours',
    'en_attente' => 'En attente',
    'resolue'    => 'Résolue',
    'cloturee'   => 'Clôturée',
];
if (!is_string($nouveau_statut) || !isset($statuts_autorises[$nouveau_statut])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Statut invalide."]);
    exit;
}

try {
    // Mise à jour du statut
    $update = $pdo->prepare("
        UPDATE demandes 
        SET statut = ?, date_mise_a_jour = NOW()
        WHERE id = ?
    ");
    $update->execute([$nouveau_statut, $demande_id]);

    $newDate = date('Y-m-d H:i:s');
    /* CETTE PARTIE CONCERNE LA NOTIFICATION ET L'ENVOIE D4EMAIL AU CITOYEN APRES LE CHANGEMENT DE STATUT, je dois mette en place le portail citoyen et je reviens ici*/
    /*/ recuperer le citoyen
    $citoyen_id = getCitoyenIdFromDemande($pdo, $demande_id);
    // envoie de la notification
    pushNotifications($pdo, $citoyen_id, $demande_id, 'statut', 'Le statut de votre demande a été mis à jour : ' . $nouveau_statut);
    // Récupérer email citoyen
    $email_citoyen = getEmailCitoyen($pdo, $citoyen_id);

    // Envoyer email
    // Construction du mail en HEREDOC (aucun risque de casser la chaîne)
    // (à la réactivation : require_once __DIR__ . '/../Config/app.php';)
    $lienDemande = url_app("Frontend/details_demande.php?id=" . (int) $demande_id);
    $libelleStatut = $statuts_autorises[$nouveau_statut];
    $body = <<<HTML
    <p>Bonjour,</p>
    <p>Le statut de votre demande a été mis à jour :</p>
    <p><strong>$libelleStatut</strong></p>
    <p><a href="$lienDemande">
        Accéder à ma demande
    </a></p>
    HTML;

    // Envoi de l'email (sécurisé)
    try {
        sendMail($email_citoyen, "Mise à jour de votre demande", $body);
    } catch (Exception $e) {
        error_log("Erreur email : " . $e->getMessage());
    }*/

    // Récupération agent
    $agent = $pdo->prepare("SELECT prenom, nom FROM agents WHERE id = ?");
    $agent->execute([$agent_id]);
    $info = $agent->fetch();

    if (!$info) {
        echo json_encode(["success" => false, "message" => "Agent introuvable."]);
        exit;
    }

    $initiales = mb_strtoupper(
        mb_substr($info['prenom'], 0, 1) .
        mb_substr($info['nom'], 0, 1)
    );
    // Échappement à l'affichage (les noms sont stockés bruts en base)
    $initiales = htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8');
    $auteur    = htmlspecialchars($info['prenom'] . ' ' . $info['nom'], ENT_QUOTES, 'UTF-8');

    // AJOUT DANS L'HISTORIQUE
    $log = $pdo->prepare("
        INSERT INTO historique_demandes (demande_id, agent_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $log->execute([
        $demande_id,
        $agent_id,
        "Changement de statut",
        "Nouveau statut : {$nouveau_statut}"
    ]);

    // HTML pour la timeline historique
    $history_html = "
        <div class='history-item' data-type='status' data-text='Nouveau statut : {$nouveau_statut}'>
            <div class='history-dot history-dot-status'></div>
            <div class='history-content'>
                <div class='history-header'>
                    <div class='history-avatar'>{$initiales}</div>
                    <div class='history-meta'>
                        <span class='history-author'>{$auteur}</span>
                        <span class='history-time'>{$newDate}</span>
                    </div>
                    <span class='history-tag history-tag-status'>Statut</span>
                </div>
                <div class='history-body'>
                    <strong>Changement de statut</strong><br>
                    Nouveau statut : {$nouveau_statut}
                </div>
            </div>
        </div>
    ";

    echo json_encode([
        "success" => true,
        "message" => "Statut mis à jour !",
        "date_mise_a_jour" => $newDate,
        "history_html" => $history_html
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur lors de la mise à jour."
    ]);
}

exit;
