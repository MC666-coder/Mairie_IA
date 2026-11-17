<?php
/* =========================================================
   Téléchargement d'un document par le citoyen
   ---------------------------------------------------------
   Sur une demande qui lui appartient, le citoyen peut télécharger :
   - les documents qu'il a déposés (agent_id NULL) ;
   - les documents que la mairie lui a envoyés (visible_citoyen = 1),
     par exemple une attestation.
   Jamais les pièces jointes internes. Sinon : « introuvable ».
   ========================================================= */
session_start();
require_once __DIR__ . '/../Config/db.php';

if (!isset($_SESSION['citoyen_id'])) {
    http_response_code(401);
    exit('Vous devez être connecté.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT f.nom_fichier, f.chemin, f.type
                       FROM fichiers_demandes f
                       JOIN demandes d ON d.id = f.demande_id
                       WHERE f.id = ? AND d.citoyen_id = ?
                         AND (f.agent_id IS NULL OR f.visible_citoyen = 1)");
$stmt->execute([$id ?: 0, (int) $_SESSION['citoyen_id']]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

$chemin = $f ? __DIR__ . '/../uploads/demandes/' . basename($f['chemin']) : null;
if (!$f || !is_file($chemin)) {
    http_response_code(404);
    exit('Document introuvable.');
}

// Nom de fichier propre pour l'en-tête (pas de guillemets ni de retour à la ligne)
$nom = preg_replace('/["\r\n\\\\]/', '_', $f['nom_fichier']);
header('Content-Type: ' . ($f['type'] ?: 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $nom . '"; filename*=UTF-8\'\'' . rawurlencode($f['nom_fichier']));
header('Content-Length: ' . filesize($chemin));
readfile($chemin);
