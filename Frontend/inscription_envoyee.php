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
                <?= enteteAuth('courrier', 'Vérifiez votre boîte mail', 'Votre compte est créé, il reste à le confirmer') ?>

                <p class="carte-auth-texte">
                    Nous avons envoyé un e-mail à <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
                    Cliquez sur le bouton <strong>« Confirmer mon adresse e-mail »</strong> qu’il contient pour activer votre compte.
                </p>
                <p class="carte-auth-texte carte-auth-texte-discret">
                    Le lien est valable 24 heures. Rien reçu ? Regardez dans vos courriers indésirables (spams).
                    Si le lien a expiré, vous pourrez vous réinscrire avec la même adresse.
                </p>

                <a class="btn-submit btn-lien" href="connexion_citoyen.php">Aller à la connexion</a>
            </div>
<?php
finPageAuth();
