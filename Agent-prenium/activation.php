<?php
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/auth_agent.php'; // gabarit « écran partagé » (agent.css)
require_once __DIR__ . '/../Backend/Fonctions/regles_mot_de_passe.php';

/* ---------------------------------------------------------
   0) Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   1) Vérification du token dans l’URL
--------------------------------------------------------- */
if (!isset($_GET['token']) || strlen($_GET['token']) !== 64) {
    pageMessageAgent('Lien invalide', "Ce lien n’est pas valide ou a déjà été utilisé.
Vérifiez que vous avez copié l’adresse en entier depuis l’e-mail.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

$token = $_GET['token'];

/* ---------------------------------------------------------
   2) Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['activation_attempts'])) {
    $_SESSION['activation_attempts'] = 0;
}
$_SESSION['activation_attempts']++;

if ($_SESSION['activation_attempts'] > 10) {
    pageMessageAgent('Trop de tentatives', "Vous avez fait trop d’essais.\nPatientez quelques minutes avant de réessayer.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   3) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, email, token_expiration FROM agents WHERE token_activation = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    pageMessageAgent('Lien invalide', "Ce lien n’est pas valide ou a déjà été utilisé.
Vérifiez que vous avez copié l’adresse en entier depuis l’e-mail.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   4) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['token_expiration']) < time()) {
    pageMessageAgent('Lien expiré', "Ce lien d’activation a expiré (il est valable 24 heures).
Demandez à votre responsable de vous en renvoyer un.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   5) Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_token_activation'])) {
    $_SESSION['csrf_token_activation'] = bin2hex(random_bytes(32));
}
?>
<?= debutAuthAgent('Activation du compte') ?>

            <?= retourAuth('connexion_agent.php', 'Retour à la connexion') ?>
            <?= enteteAuth('cadenas', 'Activez votre compte', 'Choisissez votre mot de passe pour accéder à l’espace agent.') ?>
            <?= messageAuth('activation_erreur') ?>

            <form action="traitement_activation.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token_activation"
                       value="<?= htmlspecialchars($_SESSION['csrf_token_activation'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <?= champAuth('password', 'Mot de passe', 'password', 'cadenas', '', '',
                              'Votre mot de passe doit contenir :', listeReglesMotDePasse('password')) ?>
                <?= champAuth('password_confirm', 'Confirmer le mot de passe', 'password', 'cadenas', '', '', '',
                              '<p class="champ-auth-erreur" id="message-confirmation" hidden>Les deux mots de passe ne correspondent pas.</p>') ?>

                <button type="submit" class="btn-submit">Activer mon compte</button>
            </form>

<?= finAuthAgent(['mot_de_passe_visible.js', 'regles_mot_de_passe.js']) ?>
