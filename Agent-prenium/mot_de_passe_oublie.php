<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/auth_agent.php'; // gabarit « écran partagé » (agent.css)


/* ---------------------------------------------------------
   Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ---------------------------------------------------------
   Protection anti-bruteforce
--------------------------------------------------------- */
if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = 0;
}
if ($_SESSION['forgot_attempts'] > 10) {
    pageMessageAgent('Trop de tentatives', "Vous avez fait trop de demandes.\nPatientez quelques minutes avant de réessayer.", 'connexion_agent.php', 'Retour à la connexion', 'erreur');
}

/* ---------------------------------------------------------
   Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_forgot'])) {
    $_SESSION['csrf_forgot'] = bin2hex(random_bytes(32));
}
?>
<?= debutAuthAgent('Mot de passe oublié') ?>

            <?= retourAuth('connexion_agent.php', 'Retour à la connexion') ?>
            <?= enteteAuth('cadenas', 'Mot de passe oublié ?', 'Indiquez votre adresse professionnelle : vous recevrez un lien sécurisé pour en choisir un nouveau.') ?>
            <?= messageAuth('error_forgot') ?>

            <form action="traitement_mot_de_passe_oublie.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_forgot"
                       value="<?= htmlspecialchars($_SESSION['csrf_forgot'], ENT_QUOTES, 'UTF-8') ?>">

                <?= champAuth('email', 'Adresse e-mail professionnelle', 'email', 'courrier') ?>

                <button type="submit" class="btn-submit"><?= icone('envoyer') ?> Envoyer le lien</button>
            </form>

<?= finAuthAgent() ?>
