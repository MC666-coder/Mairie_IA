<?php
require_once __DIR__ . '/../Backend/Config/db.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_public.php';
require_once __DIR__ . '/../Backend/Fonctions/icones.php';

// Désactiver l'affichage brut des erreurs en production (à gérer dans la config globale idéalement)
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// Vérification stricte de l'ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: Actualités.php?error=notfound');
    exit;
}

$id = (int) $_GET['id'];

// Récupérer l’actualité publiée
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND statut = 'publie'");
$stmt->execute([$id]);
$actu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actu) {
    header('Location: Actualités.php?error=notfound');
    exit;
}

// Sécurisation du nom de fichier image
$image = $actu['image'] ?: 'default.jpg';

// On n'autorise que des noms simples (lettres, chiffres, ., _, -)
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $image)) {
    $image = 'default.jpg';
}

// On force le chemin dans le dossier prévu
$imagePath = 'uploads/' . $image;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($actu['titre'], ENT_QUOTES, 'UTF-8') ?> - Commune de L’Aiguillon-sur-Vie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?= feuillesGabarit() ?>

    <!-- Content Security Policy (adaptable selon tes besoins) -->
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

    <?= enTetePublic('actualites') ?>

    <!-- HERO SECTION -->
    <section class="hero-page">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><?= htmlspecialchars($actu['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Publié le <?= htmlspecialchars(date("d/m/Y", strtotime($actu['date_publication'])), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <main class="article-container">

        <!-- Bouton retour -->
        <a href="Actualités.php" class="retour-btn"><?= icone('fleche-gauche') ?> Retour aux actualités</a>

        <!-- Image principale -->
        <div class="article-image"
             style="background-image:url('<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>');">
        </div>

        <!-- Contenu -->
        <div class="article-content">
            <?= nl2br(htmlspecialchars($actu['contenu'], ENT_QUOTES, 'UTF-8')) ?>
        </div>

        <!-- Bouton retour bas de page -->
        <a href="Actualités.php" class="retour-btn bottom"><?= icone('fleche-gauche') ?> Retour aux actualités</a>

    </main>

    <?= piedDePage() ?>

</div>
</body>
</html>
