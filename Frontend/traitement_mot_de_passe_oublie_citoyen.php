<?php
/* =========================================================
   Mot de passe oublié (citoyens) : envoi du lien par e-mail
   - Adresse inconnue ou compte non activé : message d'erreur rouge sur le formulaire.
   - Lien valable 1 h, utilisable une fois ; seule l'empreinte du jeton est stockée.
   ========================================================= */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/email_citoyen.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

// Vérification CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Requête invalide.");
}

// Limite : 5 demandes par tranche de 15 minutes (par session)
$maintenant = time();
if (empty($_SESSION['oubli_debut']) || $maintenant - $_SESSION['oubli_debut'] > 900) {
    $_SESSION['oubli_debut'] = $maintenant;
    $_SESSION['oubli_demandes'] = 0;
}
$_SESSION['oubli_demandes']++;
if ($_SESSION['oubli_demandes'] > 5) {
    $_SESSION['oubli_error'] = "Trop de demandes. Réessayez dans quelques minutes.";
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

$email = filter_var(trim($_POST['email_citoyen'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    $_SESSION['oubli_error'] = "Veuillez entrer une adresse e-mail valide.";
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

$requete = $pdo->prepare("
    SELECT id, prenom_citoyen, email_verifie FROM citoyens
    WHERE email_citoyen = ?
    LIMIT 1
");
$requete->execute([$email]);
$citoyen = $requete->fetch(PDO::FETCH_ASSOC);

// Adresse inconnue : message d'erreur (choix de la mairie : on l'indique clairement au citoyen)
if (!$citoyen) {
    $_SESSION['oubli_error'] = "Aucun compte citoyen n’est associé à cette adresse e-mail. "
        . "Vérifiez l’adresse saisie ou créez un compte.";
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

// Compte jamais confirmé : il faut d'abord cliquer sur le lien reçu à l'inscription
if ((int) $citoyen['email_verifie'] !== 1) {
    $_SESSION['oubli_error'] = "Ce compte n’est pas encore activé. Cliquez d’abord sur le bouton "
        . "« Confirmer mon adresse e-mail » reçu après votre inscription.";
    header('Location: mot_de_passe_oublie_citoyen.php');
    exit;
}

[$jeton, $empreinte] = nouveauJetonCitoyen();
$pdo->prepare("UPDATE citoyens SET token_reset = ?, reset_expiration = ? WHERE id = ?")
    ->execute([$empreinte, date('Y-m-d H:i:s', $maintenant + 3600), $citoyen['id']]);

envoyerEmailReinitialisation($email, (string) $citoyen['prenom_citoyen'], $jeton);

$_SESSION['oubli_success'] = "Un e-mail vient de vous être envoyé. "
    . "Cliquez sur le bouton qu’il contient pour choisir un nouveau mot de passe (lien valable 1 heure).";
header('Location: mot_de_passe_oublie_citoyen.php');
exit;
