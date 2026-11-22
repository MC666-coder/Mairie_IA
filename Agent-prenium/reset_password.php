<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';

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
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 0;
}
$_SESSION['reset_attempts']++;

if ($_SESSION['reset_attempts'] > 10) {
    pageMessageAgent('Trop de tentatives', "Vous avez fait trop d’essais.\nPatientez quelques minutes avant de réessayer.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   3) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, reset_expiration FROM agents WHERE token_reset = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    pageMessageAgent('Lien invalide', "Ce lien n’est pas valide ou a déjà été utilisé.
Vérifiez que vous avez copié l’adresse en entier depuis l’e-mail.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   4) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['reset_expiration']) < time()) {
    pageMessageAgent('Lien expiré', "Ce lien de réinitialisation a expiré (il est valable 24 heures).
Refaites une demande pour en recevoir un nouveau.", 'mot_de_passe_oublie.php', 'Refaire une demande', 'erreur');
}

/* ---------------------------------------------------------
   5) Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_reset'])) {
    $_SESSION['csrf_reset'] = bin2hex(random_bytes(32));
}
?>
<?= debutAuthAgent('Nouveau mot de passe') ?>

            <?= retourAuth('connexion_agent.php', 'Retour à la connexion') ?>
            <?= enteteAuth('cadenas', 'Nouveau mot de passe', 'Il remplacera votre ancien mot de passe.') ?>
            <?= messageAuth('reset_erreur') ?>

            <form action="traitement_reset_password.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_reset"
                       value="<?= htmlspecialchars($_SESSION['csrf_reset'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <?= champAuth('password', 'Mot de passe', 'password', 'cadenas', '', '',
                              'Votre mot de passe doit contenir :', listeReglesMotDePasse('password')) ?>
                <?= champAuth('password_confirm', 'Confirmer le mot de passe', 'password', 'cadenas', '', '', '',
                              '<p class="champ-auth-erreur" id="message-confirmation" hidden>Les deux mots de passe ne correspondent pas.</p>') ?>

                <button type="submit" class="btn-submit">Enregistrer le mot de passe</button>
            </form>

<?= finAuthAgent(['mot_de_passe_visible.js', 'regles_mot_de_passe.js']) ?>
