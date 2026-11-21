<?php
/* =========================================================
   Lien « Confirmer mon adresse e-mail » (e-mail envoyé à l'inscription)
   Active le compte puis renvoie vers la connexion avec un message.
   ========================================================= */
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
session_start();
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/email_citoyen.php';
require_once __DIR__ . '/../Backend/Fonctions/page_auth.php';

header('Referrer-Policy: no-referrer'); // le jeton de l'URL ne part pas vers d'autres sites

$empreinte = empreinteJeton((string) ($_GET['token'] ?? ''));
$citoyen = false;

if ($empreinte !== null) {
    $requete = $pdo->prepare("
        SELECT id, verification_expiration FROM citoyens
        WHERE token_verification = ? AND email_verifie = 0
        LIMIT 1
    ");
    $requete->execute([$empreinte]);
    $citoyen = $requete->fetch(PDO::FETCH_ASSOC);
}

if ($citoyen && strtotime($citoyen['verification_expiration']) >= time()) {
    // Compte activé ; le jeton est effacé (lien utilisable une seule fois)
    $pdo->prepare("
        UPDATE citoyens
        SET email_verifie = 1, token_verification = NULL, verification_expiration = NULL
        WHERE id = ?
    ")->execute([$citoyen['id']]);

    unset($_SESSION['inscription_email']);
    $_SESSION['login_success'] = "Adresse e-mail confirmée : votre compte est activé. Vous pouvez vous connecter.";
    header('Location: connexion_citoyen.php');
    exit;
}

// Lien invalide, déjà utilisé ou expiré
debutPageAuth('Lien de confirmation', 'Inscription Citoyen', 'Confirmation de votre adresse e-mail.');
?>
            <div class="carte-auth">
                <?= retourAuth('connexion_citoyen.php', 'Retour à la connexion') ?>
                <?= enteteAuth('alerte', 'Lien invalide ou expiré', 'Ce lien de confirmation ne peut plus être utilisé') ?>
                <p class="carte-auth-texte">
                    Il a peut-être déjà servi (votre compte est alors déjà actif) ou il a dépassé 24 heures.
                    Essayez de vous connecter ; si votre compte n’est pas actif, inscrivez-vous à nouveau avec la même adresse.
                </p>
                <a class="btn-submit btn-lien" href="connexion_citoyen.php">Se connecter</a>
                <p class="carte-auth-pied"><a href="inscriptions_citoyen.php">S’inscrire à nouveau</a></p>
            </div>
<?php
finPageAuth();
