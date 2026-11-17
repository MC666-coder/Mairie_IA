<?php
/* =========================================================
   Conversation mairie <-> citoyen : règles communes
   ---------------------------------------------------------
   Utilisé par Backend/ajax/Ajouter_message_agent.php (agent) et
   Backend/citoyen/messagerie.php (citoyen).

   Règles de la mairie :
   - seul l'AGENT ASSIGNÉ à la demande écrit au citoyen (pour réclamer un
     document, envoyer une attestation...) ;
   - le citoyen ne peut jamais écrire le premier : il RÉPOND à l'agent,
     avec un document et/ou un court message ;
   - plus aucun échange une fois la demande clôturée.
   ========================================================= */

require_once __DIR__ . '/../Config/app.php';
require_once __DIR__ . '/sendMail.php';

const DOC_TAILLE_MAX = 5 * 1024 * 1024;
const DOC_TYPES_AUTORISES = [          // extension => types MIME réels acceptés
    'pdf'  => ['application/pdf'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
];
const DOC_DOSSIER = __DIR__ . '/../uploads/demandes/';

/** Taille maximale réelle : la plus petite entre la nôtre et celle de php.ini. */
function tailleMaxDocument(): int
{
    $v = trim((string) ini_get('upload_max_filesize'));
    $n = (int) $v;
    $php = match (strtolower(substr($v, -1))) { 'g' => $n << 30, 'm' => $n << 20, 'k' => $n << 10, default => $n };
    return min(DOC_TAILLE_MAX, $php ?: DOC_TAILLE_MAX);
}

/**
 * Vérifie un fichier envoyé (sans l'enregistrer).
 * @return array{ok: bool, message?: string, code?: int, nom?: string, extension?: string, type?: string, taille?: int, tmp?: string}
 */
function verifierDocument(?array $fichier): array
{
    if (!$fichier || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'code' => 400, 'message' => 'Aucun fichier reçu.'];
    }
    $max = tailleMaxDocument();
    if (in_array($fichier['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $fichier['size'] > $max) {
        return ['ok' => false, 'code' => 413, 'message' => 'Le fichier est trop lourd (' . round($max / 1048576, 1) . ' Mo maximum).'];
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($fichier['tmp_name'])) {
        return ['ok' => false, 'code' => 400, 'message' => "Le fichier n'a pas pu être envoyé. Merci de réessayer."];
    }
    $nom = mb_substr(basename((string) $fichier['name']), 0, 200);
    $extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
    // Le TYPE RÉEL du contenu est vérifié, pas seulement l'extension
    $type = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
    if (!isset(DOC_TYPES_AUTORISES[$extension]) || !in_array($type, DOC_TYPES_AUTORISES[$extension], true)) {
        return ['ok' => false, 'code' => 415, 'message' => 'Format non autorisé. Formats acceptés : PDF, JPG, PNG, DOCX, XLSX.'];
    }
    return ['ok' => true, 'nom' => $nom, 'extension' => $extension, 'type' => $type,
            'taille' => (int) $fichier['size'], 'tmp' => $fichier['tmp_name']];
}

/**
 * Enregistre un fichier déjà vérifié et le rattache à un message.
 * @param ?int $agentId  null = déposé par le citoyen
 * @return ?int id du fichier, null si l'enregistrement a échoué
 */
function enregistrerDocument(PDO $pdo, int $demandeId, ?int $agentId, array $doc, bool $visibleCitoyen, ?int $messageId): ?int
{
    // Nom aléatoire sur le disque : le nom d'origine n'y est jamais utilisé
    $nomDisque = bin2hex(random_bytes(16)) . '.' . $doc['extension'];
    if (!move_uploaded_file($doc['tmp'], DOC_DOSSIER . $nomDisque)) {
        return null;
    }
    try {
        $pdo->prepare("INSERT INTO fichiers_demandes
                           (demande_id, agent_id, nom_fichier, chemin, type, taille, date_upload, visible_citoyen, message_id)
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)")
            ->execute([$demandeId, $agentId, $doc['nom'], $nomDisque, $doc['type'], $doc['taille'],
                       $visibleCitoyen ? 1 : 0, $messageId]);
        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        @unlink(DOC_DOSSIER . $nomDisque);
        error_log("conversation : enregistrement du document impossible");
        return null;
    }
}

/**
 * Prévient le citoyen par email qu'un message l'attend.
 * RGPD : l'email ne contient PAS le texte du message, seulement un lien.
 */
function notifierCitoyenNouveauMessage(PDO $pdo, int $demandeId): void
{
    try {
        $stmt = $pdo->prepare("SELECT c.email_citoyen, c.prenom_citoyen
                               FROM demandes d JOIN citoyens c ON c.id = d.citoyen_id WHERE d.id = ?");
        $stmt->execute([$demandeId]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c || !filter_var($c['email_citoyen'], FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $lien = url_app('Frontend/details_demande.php?id=' . $demandeId . '#messagerie-citoyen');
        $html = '<p>Bonjour ' . htmlspecialchars($c['prenom_citoyen'] ?? '') . ',</p>'
              . '<p>Vous avez reçu un nouveau message de la mairie de ' . htmlspecialchars(NOM_COMMUNE)
              . ' concernant votre demande n°' . $demandeId . '.</p>'
              . '<p><a href="' . htmlspecialchars($lien) . '">Connectez-vous à votre espace citoyen pour le lire</a>.</p>'
              . '<p>Par sécurité, le contenu du message n’est pas recopié dans cet email.</p>';
        if (!sendMail($c['email_citoyen'], 'Nouveau message de la mairie - demande n°' . $demandeId, $html)) {
            error_log("conversation : email de notification non envoyé (demande $demandeId)");
        }
    } catch (Throwable $e) {
        error_log("conversation : notification impossible (demande $demandeId)");
    }
}
