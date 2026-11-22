<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';



// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: connexion_citoyen.php");
    exit;
}

/* Retour au formulaire avec un message rouge dans la carte (et l'adresse déjà saisie) */
function erreurConnexion(string $message, string $email = ''): void
{
    $_SESSION['login_error'] = $message;
    $_SESSION['login_email_citoyen'] = $email;
    header("Location: connexion_citoyen.php");
    exit;
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    erreurConnexion("La page a expiré. Merci de réessayer.");
}

// Nettoyage des données
$emailSaisi = trim((string) ($_POST['email_citoyen'] ?? ''));
$email_citoyen = filter_var($emailSaisi, FILTER_VALIDATE_EMAIL);
$password = (string) ($_POST['password'] ?? '');

if ($emailSaisi === '' || $password === '') {
    erreurConnexion("Veuillez remplir tous les champs.", $emailSaisi);
}
if (!$email_citoyen) {
    erreurConnexion("L'adresse e-mail n'est pas valide.", $emailSaisi);
}

// Prévention bruteforce (5 tentatives / 10 minutes)
if (!isset($_SESSION['login_attempts_citoyen'])) {
    $_SESSION['login_attempts_citoyen'] = 0;
    $_SESSION['first_attempt_time_citoyen'] = time();
}

if ($_SESSION['login_attempts_citoyen'] >= 5) {
    if (time() - $_SESSION['first_attempt_time_citoyen'] < 600) {
        erreurConnexion("Trop de tentatives. Réessayez dans quelques minutes.", $emailSaisi);
    }
    $_SESSION['login_attempts_citoyen'] = 0;
    $_SESSION['first_attempt_time_citoyen'] = time();
}

// Requête SQL sécurisée
$stmt = $pdo->prepare("
    SELECT id, nom_citoyen, prenom_citoyen, email_citoyen, password, email_verifie
    FROM citoyens
    WHERE email_citoyen = ?
    LIMIT 1
");
$stmt->execute([$email_citoyen]);
$citoyen = $stmt->fetch(PDO::FETCH_ASSOC);

/* Anti-énumération : on ne doit pas pouvoir deviner quelles adresses ont un compte.
   - un seul message pour « adresse inconnue » et « mauvais mot de passe » ;
   - le mot de passe est TOUJOURS vérifié, avec un vrai hachage bcrypt (coût 10) d'un
     mot de passe aléatoire si l'adresse est inconnue ou le compte sans mot de passe :
     le temps de réponse est le même dans tous les cas ;
   - « adresse non confirmée » n'est dit qu'après le bon mot de passe. */
const HACHAGE_FACTICE = '$2y$10$MzBlAb1zTuclB1UeZGAwqeZ7X/7Fja/XD5btCq.wEPCkh5pK/Fcga';

$hachage = ($citoyen && !empty($citoyen['password'])) ? (string) $citoyen['password'] : HACHAGE_FACTICE;
$motDePasseCorrect = password_verify($password, $hachage) && $hachage !== HACHAGE_FACTICE;

if (!$citoyen || !$motDePasseCorrect) {
    $_SESSION['login_attempts_citoyen']++;
    erreurConnexion("Adresse e-mail ou mot de passe incorrect.", $emailSaisi);
}

// Compte pas encore confirmé : il faut d'abord cliquer sur le bouton reçu par e-mail
if ((int) $citoyen['email_verifie'] !== 1) {
    erreurConnexion("Votre adresse e-mail n'est pas encore confirmée. "
        . "Cliquez sur le bouton « Confirmer mon adresse e-mail » reçu après votre inscription.", $emailSaisi);
}

// Connexion réussie : remise à zéro du compteur
$_SESSION['login_attempts_citoyen'] = 0;
unset($_SESSION['login_email_citoyen']);

// Regénération de l’ID de session
session_regenerate_id(true);

// Stockage des infos citoyen
$_SESSION['citoyen_id'] = $citoyen['id'];
$_SESSION['nom_citoyen'] = $citoyen['nom_citoyen'];
$_SESSION['prenom_citoyen'] = $citoyen['prenom_citoyen'];
$_SESSION['email_citoyen'] = $citoyen['email_citoyen'];

// Retour sur la page demandée avant la connexion (sinon le tableau de bord)
require_once __DIR__ . '/../Backend/Fonctions/session_citoyen.php';
$retour = retourCitoyenValide($_SESSION['retour_apres_connexion'] ?? null);
unset($_SESSION['retour_apres_connexion']);
header('Location: ' . ($retour ?? 'Portail_citoyen.php'));
exit();
