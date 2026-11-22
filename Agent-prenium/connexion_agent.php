<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/formulaire_auth.php';
require_once __DIR__ . '/../Backend/Fonctions/auth_agent.php'; // gabarit « écran partagé » (agent.css)
/* ---------------------------------------------------------
   Génération du token CSRF
--------------------------------------------------------- */
$_SESSION['csrf_login'] = bin2hex(random_bytes(32));



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
// 10 essais, puis 15 minutes d'attente (compteur géré par traitement_connexion_agent.php)
if (!empty($_SESSION['login_debut']) && time() - $_SESSION['login_debut'] > 900) {
    $_SESSION['login_attempts'] = 0;
}
if (($_SESSION['login_attempts'] ?? 0) > 10 && empty($_SESSION['login_error_agent'])) {
    $_SESSION['login_error_agent'] = "Trop de tentatives. Réessayez dans quelques minutes.";
}

// Adresse saisie lors de l'essai précédent (réaffichée dans le champ)
$emailSaisi = (string) ($_SESSION['login_email_agent'] ?? '');
unset($_SESSION['login_email_agent']);


?>
<?= debutAuthAgent('Connexion') ?>

            <?= retourAuth('../Frontend/choix_connexion.php', 'Retour') ?>
            <?= enteteAuth('mallette', 'Connexion', 'Connectez-vous avec vos identifiants professionnels.') ?>
            <?= messageSessionExpiree() ?>
            <?= messageAuth('login_error_agent') ?>

            <form action="traitement_connexion_agent.php" method="POST" autocomplete="off">
                <!-- CSRF -->
                <input type="hidden" name="csrf_login"
                       value="<?= htmlspecialchars($_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8') ?>">

                <?= champAuth('email', 'Adresse e-mail', 'email', 'courrier', '', $emailSaisi) ?>
                <?= champAuth('password', 'Mot de passe', 'password', 'cadenas') ?>
                <a class="lien-auth-discret" href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>

                <button type="submit" class="btn-submit">Se connecter <?= icone('fleche-droite') ?></button>
            </form>

<?= finAuthAgent(['mot_de_passe_visible.js']) ?>
