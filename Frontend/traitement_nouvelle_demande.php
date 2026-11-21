<?php
/* ---------------------------------------
   Sécurisation session
---------------------------------------- */

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/demande_citoyen.php'; // types, priorités, longueur du message



// Vérification connexion citoyen
// Session expirée pendant la saisie : retour à la connexion, puis au formulaire
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';
exigerCitoyen('nouvelle_demande.php');

$citoyenId = (int) $_SESSION['citoyen_id'];

/* ---------------------------------------
   En cas d'erreur : retour au formulaire avec un message rouge
   et la saisie conservée (au lieu d'une page blanche)
---------------------------------------- */

function retourFormulaire(string $erreur, array $saisie = []): never
{
    $_SESSION['erreur_demande'] = $erreur;
    $_SESSION['saisie_demande'] = $saisie;
    header('Location: nouvelle_demande.php');
    exit();
}

/* ---------------------------------------
   Vérification méthode POST
---------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nouvelle_demande.php');
    exit();
}

/* ---------------------------------------
   Nettoyage des données
---------------------------------------- */

$service_id   = trim((string) ($_POST['services_id'] ?? ''));
$type_demande = trim((string) ($_POST['type'] ?? ''));
$message      = trim((string) ($_POST['message'] ?? ''));
$priorite     = trim((string) ($_POST['priorite'] ?? ''));

// Saisie renvoyée au formulaire en cas d'erreur (message tronqué à la longueur maximale)
$saisie = [
    'services_id' => $service_id,
    'type'        => $type_demande,
    'message'     => mb_substr($message, 0, MESSAGE_MAX, 'UTF-8'),
    'priorite'    => $priorite,
];

/* ---------------------------------------
   Vérification CSRF
   (jeton absent ou faux : formulaire trop ancien ou envoyé depuis un autre site)
---------------------------------------- */

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // nouveau jeton
    retourFormulaire('Le formulaire a expiré. Vérifiez vos informations puis renvoyez la demande.', $saisie);
}

/* ---------------------------------------
   Validation des champs
---------------------------------------- */

if (!ctype_digit($service_id)) {
    retourFormulaire('Choisissez le service concerné par votre demande.', $saisie);
}

if (!array_key_exists($type_demande, TYPES_DEMANDE)) {
    retourFormulaire('Choisissez le type de demande : plainte, question ou suggestion.', $saisie);
}

$longueur = mb_strlen($message, 'UTF-8');
if ($longueur < MESSAGE_MIN) {
    retourFormulaire('Votre message est trop court : écrivez au moins ' . MESSAGE_MIN . ' caractères.', $saisie);
}
if ($longueur > MESSAGE_MAX) {
    retourFormulaire('Votre message est trop long : ' . MESSAGE_MAX . ' caractères au maximum.', $saisie);
}

if (!array_key_exists($priorite, PRIORITES_DEMANDE)) {
    retourFormulaire('Choisissez une priorité : basse, normale ou haute.', $saisie);
}

/* ---------------------------------------
   Vérification que le service existe
---------------------------------------- */

$checkService = $pdo->prepare("SELECT id FROM services WHERE id = ?");
$checkService->execute([(int) $service_id]);

if ($checkService->fetchColumn() === false) {
    $saisie['services_id'] = '';
    retourFormulaire('Ce service n’existe pas. Choisissez un service dans la liste.', $saisie);
}

/* ---------------------------------------
   Anti-spam simple : 1 demande / 5 sec
   (après la validation : une erreur de saisie ne bloque pas le nouvel essai)
---------------------------------------- */

if (time() - (int) ($_SESSION['last_demande_submit'] ?? 0) < 5) {
    retourFormulaire('Vous venez d’envoyer une demande. Patientez quelques secondes avant d’en envoyer une autre.', $saisie);
}
$_SESSION['last_demande_submit'] = time();

/* ---------------------------------------
   Insertion sécurisée dans `demandes`
   (table commune aux portails citoyen ET agent :
    la demande apparaît immédiatement chez les agents)
---------------------------------------- */

$insert = $pdo->prepare("
    INSERT INTO demandes (citoyen_id, service_id, type_id, message, priorite, statut, canal, created_at)
    VALUES (?, ?, (SELECT id FROM types_demandes WHERE code = ?), ?, ?, 'nouvelle', 'formulaire', NOW())
");

$insert->execute([
    $citoyenId,
    (int) $service_id,
    $type_demande,
    $message,
    $priorite
]);

/* ---------------------------------------
   Redirection propre
---------------------------------------- */

header("Location: Portail_citoyen.php?demande=success");
exit();
