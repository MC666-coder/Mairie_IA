<?php
/* =========================================================
   Mot de passe oublié (citoyens) : choix du nouveau mot de passe
   Page ouverte depuis le bouton de l'e-mail (?token=...).
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
require_once __DIR__ . '/../Backend/Fonctions/regles_mot_de_passe.php';

header('Referrer-Policy: no-referrer'); // le jeton de l'URL ne part pas vers d'autres sites

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$jeton = (string) ($_GET['token'] ?? '');
$empreinte = empreinteJeton($jeton);
$valide = false;

if ($empreinte !== null) {
    $requete = $pdo->prepare("SELECT reset_expiration FROM citoyens WHERE token_reset = ? LIMIT 1");
    $requete->execute([$empreinte]);
    $ligne = $requete->fetch(PDO::FETCH_ASSOC);
    $valide = $ligne && strtotime($ligne['reset_expiration']) >= time();
}

debutPageAuth('Nouveau mot de passe', 'Mot de passe oublié', 'Choisissez un nouveau mot de passe.');

if (!$valide): ?>
            <div class="carte-auth">
                <?= retourAuth('connexion_citoyen.php', 'Retour à la connexion') ?>
                <?= enteteAuth('alerte', 'Lien invalide ou expiré', 'Ce lien ne peut plus être utilisé') ?>
                <p class="carte-auth-texte">Le lien a peut-être déjà servi ou il a dépassé 1 heure. Faites une nouvelle demande.</p>
                <a class="btn-submit btn-lien" href="mot_de_passe_oublie_citoyen.php">Nouvelle demande</a>
            </div>
<?php else: ?>
            <div class="carte-auth">
                <?= retourAuth('connexion_citoyen.php', 'Retour à la connexion') ?>
                <?= enteteAuth('cadenas', 'Nouveau mot de passe', 'Il remplacera votre ancien mot de passe') ?>
                <?= messageAuth('nouveau_mdp_error') ?>

                <form action="traitement_nouveau_mot_de_passe_citoyen.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($jeton, ENT_QUOTES, 'UTF-8') ?>">

                    <?= champAuth('password', 'Nouveau mot de passe', 'password', 'cadenas', '', '',
                                  'Votre mot de passe doit contenir :', listeReglesMotDePasse('password')) ?>
                    <?= champAuth('password_confirm', 'Confirmer le mot de passe', 'password', 'cadenas', '', '', '',
                                  '<p class="champ-auth-erreur" id="message-confirmation" hidden>Les deux mots de passe ne correspondent pas.</p>') ?>

                    <button type="submit" class="btn-submit">Enregistrer le mot de passe</button>
                </form>
            </div>
<?php endif;

finPageAuth(['mot_de_passe_visible.js', 'regles_mot_de_passe.js']);
