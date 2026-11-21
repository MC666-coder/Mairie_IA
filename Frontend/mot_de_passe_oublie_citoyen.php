<?php
/* Mot de passe oublié (citoyens) : saisie de l'adresse e-mail */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Fonctions/page_auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

debutPageAuth('Mot de passe oublié', 'Mot de passe oublié', 'Recevez un lien pour choisir un nouveau mot de passe.');
?>
            <div class="carte-auth">
                <?= retourAuth('connexion_citoyen.php', 'Retour à la connexion') ?>
                <?= enteteAuth('cadenas', 'Mot de passe oublié ?', 'Indiquez l’adresse e-mail de votre compte') ?>
                <?= messageAuth('oubli_error') ?>
                <?= messageAuth('oubli_success', 'success') ?>

                <form action="traitement_mot_de_passe_oublie_citoyen.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= champAuth('email_citoyen', 'Adresse e-mail', 'email', 'courrier') ?>
                    <button type="submit" class="btn-submit">Recevoir le lien</button>
                </form>

            </div>
<?php
finPageAuth();
