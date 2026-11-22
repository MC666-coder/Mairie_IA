<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/regles_mot_de_passe.php';
require_once __DIR__ . '/../Backend/Fonctions/email_citoyen.php';

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    session_destroy();
    die("Requête invalide.");
}

// Nettoyage des données
$nom = trim($_POST['nom_citoyen'] ?? '');
$prenom = trim($_POST['prenom_citoyen'] ?? '');
$emailSaisi = trim($_POST['email_citoyen'] ?? '');
$email = filter_var($emailSaisi, FILTER_VALIDATE_EMAIL);
$telephone = preg_replace('/[\s.\-]/', '', trim($_POST['telephone_citoyen'] ?? '')); // « 06 12 34 56 78 » accepté
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

/* Retour au formulaire avec le message, en gardant la saisie (sauf les mots de passe) */
function retourFormulaire(string $message, array $saisie): void
{
    $_SESSION['inscription_error'] = $message;
    $_SESSION['inscription_valeurs'] = $saisie;
    header('Location: inscriptions_citoyen.php');
    exit;
}
$saisie = ['nom' => $nom, 'prenom' => $prenom, 'email' => $emailSaisi, 'telephone' => $telephone];

// Vérification des champs obligatoires
if ($nom === '' || $prenom === '' || $emailSaisi === '' || $telephone === '' || $password === '' || $password_confirm === '') {
    retourFormulaire("Veuillez remplir tous les champs.", $saisie);
}
if (!$email) {
    retourFormulaire("L'adresse e-mail n'est pas valide.", $saisie);
}

// Vérification du téléphone (format FR simple)
if (!preg_match('/^[0-9]{10}$/', $telephone)) {
    retourFormulaire("Numéro de téléphone invalide : 10 chiffres attendus (ex. 0612345678).", $saisie);
}

// Règles du mot de passe (mêmes que les agents : Backend/Fonctions/regles_mot_de_passe.php)
$reglesManquantes = erreursMotDePasse($password);
if ($reglesManquantes) {
    retourFormulaire("Mot de passe trop faible. Il manque : " . implode(', ', $reglesManquantes) . ".", $saisie);
}

// Vérification confirmation mot de passe
if ($password !== $password_confirm) {
    retourFormulaire("Les deux mots de passe ne correspondent pas.", $saisie);
}

// Adresse déjà utilisée ?
$checkEmail = $pdo->prepare("SELECT id, prenom_citoyen, email_verifie, verification_expiration FROM citoyens WHERE email_citoyen = ?");
$checkEmail->execute([$email]);
$existant = $checkEmail->fetch(PDO::FETCH_ASSOC);

// Un compte jamais confirmé dont le lien a expiré peut être recréé (sinon l'adresse resterait bloquée)
$recreable = $existant
    && (int) $existant['email_verifie'] === 0
    && ($existant['verification_expiration'] === null || strtotime($existant['verification_expiration']) < time());

/* Adresse déjà utilisée : on NE le dit PAS sur la page (sinon n'importe qui pourrait
   tester des adresses pour savoir qui a un compte). On répond exactement comme pour
   une nouvelle inscription (« Vérifiez votre boîte mail »), et c'est le propriétaire
   de l'adresse qui est prévenu par e-mail. Le mot de passe est quand même haché pour
   que le temps de réponse soit le même qu'une vraie inscription. */
if ($existant && !$recreable) {
    password_hash($password, PASSWORD_DEFAULT);
    envoyerEmailCompteExistant($email, (string) ($existant['prenom_citoyen'] ?? ''));
    $_SESSION['inscription_email'] = $email;
    header("Location: inscription_envoyee.php");
    exit();
}

// Hash du mot de passe + jeton de confirmation (seule son empreinte est stockée)
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
[$jeton, $empreinte] = nouveauJetonCitoyen();
$expiration = date('Y-m-d H:i:s', time() + 86400); // 24 h

if ($recreable) {
    $requete = $pdo->prepare("
        UPDATE citoyens
        SET nom_citoyen = ?, prenom_citoyen = ?, telephone_citoyen = ?, password = ?,
            email_verifie = 0, token_verification = ?, verification_expiration = ?, created_at = NOW()
        WHERE id = ?
    ");
    $requete->execute([$nom, $prenom, $telephone, $passwordHash, $empreinte, $expiration, $existant['id']]);
} else {
    $requete = $pdo->prepare("
        INSERT INTO citoyens (nom_citoyen, prenom_citoyen, email_citoyen, telephone_citoyen, password,
                              email_verifie, token_verification, verification_expiration, created_at)
        VALUES (?, ?, ?, ?, ?, 0, ?, ?, NOW())
    ");
    $requete->execute([$nom, $prenom, $email, $telephone, $passwordHash, $empreinte, $expiration]);
}

// E-mail avec le bouton « Confirmer mon adresse e-mail »
envoyerEmailConfirmation($email, $prenom, $jeton);

// Pas de connexion automatique : le compte s'active depuis la boîte mail
$_SESSION['inscription_email'] = $email;
header("Location: inscription_envoyee.php");
exit();
