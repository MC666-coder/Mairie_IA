<?php
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';

require_once __DIR__ . '/../Backend/Config/db.php';

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
    die("Lien invalide.");
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
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   3) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, reset_expiration FROM agents WHERE token_reset = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    die("Lien invalide.");
}

/* ---------------------------------------------------------
   4) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['reset_expiration']) < time()) {
    die("Ce lien de réinitialisation a expiré. Veuillez refaire une demande de réinitialisation.");
}

/* ---------------------------------------------------------
   5) Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_reset'])) {
    $_SESSION['csrf_reset'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - Portail Agent</title>
    <link rel="stylesheet" href="assets/css/style.css"/>

    <!-- Content Security Policy stricte -->
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   frame-ancestors 'none';">
</head>

<body>

<div class="page-wrapper">

    <!-- HERO -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Nouveau mot de passe</h1>
            <p>Définissez un nouveau mot de passe sécurisé pour accéder à votre espace agent.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Réinitialisation du mot de passe</h2>

            <div class="form-card">
                <form action="traitement_reset_password.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_reset"
                           value="<?= htmlspecialchars($_SESSION['csrf_reset'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Token -->
                    <input type="hidden" name="token"
                           value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" required minlength="10">

                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="10">

                    <p style="font-size:14px;color:#666;margin-top:10px;">
                        Le mot de passe doit contenir :
                        <br>• 10 caractères minimum
                        <br>• 1 majuscule
                        <br>• 1 minuscule
                        <br>• 1 chiffre
                        <br>• 1 caractère spécial
                    </p>

                    <button type="submit" class="btn-submit">Valider le nouveau mot de passe</button>
                </form>
            </div>

        </section>

    </main>

    <!-- BANDE TRICOLORE -->
    <div class="bande-tricolore">
        <div class="bleu"></div>
        <div class="blanc"></div>
        <div class="rouge"></div>
    </div>

</div>

</body>
</html>
