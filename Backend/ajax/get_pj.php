<?php
/* =========================================================
   Documents du dossier d'une demande (portail agent)
   ---------------------------------------------------------
   Classés en trois catégories :
   - recus    : déposés par le citoyen (agent_id NULL)
   - envoyes  : envoyés au citoyen par l'agent (visible_citoyen = 1)
   - internes : documents de travail de la mairie (visible_citoyen = 0)
   ========================================================= */
require_once __DIR__ . '/../Config/session_init.php';
require_once __DIR__ . '/../Middleware/auth.php';
$agent_id = exiger_agent(); // endpoint réservé aux agents connectés
verifier_csrf();            // POST : jeton CSRF obligatoire

require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Fonctions/historique_demande.php';   // dates en français

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$demande_id = (int) ($_GET['demande_id'] ?? 0);
$vide = ["count" => 0, "count_citoyen" => 0, "recus" => [], "envoyes" => [], "internes" => []];
if ($demande_id <= 0) {
    echo json_encode($vide);
    exit;
}

$stmt = $pdo->prepare("
    SELECT f.id, f.agent_id, f.nom_fichier, f.type, f.taille, f.date_upload, f.visible_citoyen, f.message_id,
           a.prenom, a.nom
    FROM fichiers_demandes f
    LEFT JOIN agents a ON a.id = f.agent_id
    WHERE f.demande_id = ?
    ORDER BY f.date_upload DESC, f.id DESC
");
$stmt->execute([$demande_id]);

$resultat = $vide;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
    $t = strtotime($f['date_upload']);
    $categorie = $f['agent_id'] === null ? 'recus' : ((int) $f['visible_citoyen'] === 1 ? 'envoyes' : 'internes');
    $resultat[$categorie][] = [
        "id"         => (int) $f["id"],
        "nom"        => $f["nom_fichier"],
        "url"        => "../Backend/ajax/download_pj.php?id=" . (int) $f["id"],
        "type"       => $f["type"],
        "taille"     => (int) $f["taille"],
        "date"       => jourLongFr($t) . ' à ' . date('H\hi', $t),
        "auteur"     => $categorie === 'recus' ? 'Citoyen' : trim(($f['prenom'] ?? '') . ' ' . ($f['nom'] ?? '')),
        "message_id" => $f["message_id"] !== null ? (int) $f["message_id"] : null,
        // Seuls les documents internes peuvent être supprimés : un document
        // reçu ou envoyé fait partie de l'échange avec le citoyen
        "supprimable" => $categorie === 'internes',
    ];
}
$resultat["count_citoyen"] = count($resultat["recus"]);
$resultat["count"] = count($resultat["recus"]) + count($resultat["envoyes"]) + count($resultat["internes"]);

echo json_encode($resultat, JSON_UNESCAPED_UNICODE);
