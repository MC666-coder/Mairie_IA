<?php
session_start();
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
    die("Lien d’activation invalide.");
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
    die("Trop de tentatives. Réessayez plus tard.");
}

/* ---------------------------------------------------------
   3) Vérification du token en base
--------------------------------------------------------- */
$sql = $pdo->prepare("SELECT id, email, token_expiration FROM agents WHERE token_activation = ?");
$sql->execute([$token]);
$agent = $sql->fetch();

if (!$agent) {
    die("Lien d’activation invalide.");
}

/* ---------------------------------------------------------
   4) Vérification expiration du token
--------------------------------------------------------- */
if (strtotime($agent['token_expiration']) < time()) {
    die("Ce lien d’activation a expiré. Contactez votre responsable.");
}

/* ---------------------------------------------------------
   5) Génération du token CSRF
--------------------------------------------------------- */
if (empty($_SESSION['csrf_token_activation'])) {
    $_SESSION['csrf_token_activation'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte agent</title>
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
            <h1>Activation du compte</h1>
            <p>Définissez votre mot de passe pour accéder à votre espace agent.</p>
        </div>
    </section>

    <main id="content_portail_citoyen">

        <section class="connexion-section">
            <h2 class="section-title">Définir votre mot de passe</h2>

            <div class="form-card">
                <form action="traitement_activation.php" method="POST" autocomplete="off">

                    <!-- CSRF -->
                    <input type="hidden" name="csrf_token_activation"
                           value="<?= htmlspecialchars($_SESSION['csrf_token_activation'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Token -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

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

                    <button type="submit" class="btn-submit">Activer mon compte</button>
                </form>
            </div>

        </section>

    </main>

</div>

</body>
</html>
