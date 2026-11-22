<?php
/* Après l'inscription : « Vérifiez votre boîte mail » (le compte s'active avec le bouton de l'e-mail) */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Fonctions/page_auth.php';

$email = $_SESSION['inscription_email'] ?? '';
if ($email === '') {
    header('Location: inscriptions_citoyen.php');
    exit;
}

debutPageAuth('Vérifiez votre boîte mail', 'Inscription Citoyen', 'Plus qu’une étape pour activer votre espace.');
?>
            <div class="carte-auth">
                <?= retourAuth('index.php', 'Accueil') ?>
                <?= enteteAuth('courrier', 'Vérifiez votre boîte mail', 'Il reste une étape avant d’accéder à votre espace') ?>

                <p class="carte-auth-texte">
                    <!-- Texte volontairement identique que l'adresse soit nouvelle ou déjà inscrite
                         (traitement_inscription_citoyen.php) : la page ne révèle pas qui a un compte -->
                    Nous avons envoyé un e-mail à <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
                    Suivez les instructions qu’il contient : le bouton de l’e-mail vous mène à votre espace citoyen.
                </p>
                <p class="carte-auth-texte carte-auth-texte-discret">
                    Le lien est valable 24 heures. Rien reçu ? Regardez dans vos courriers indésirables (spams).
                    Si le lien a expiré, vous pourrez vous réinscrire avec la même adresse.
                </p>

                <a class="btn-submit btn-lien" href="connexion_citoyen.php">Aller à la connexion</a>
            </div>
<?php
finPageAuth();
