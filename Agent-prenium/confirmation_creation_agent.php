<?php
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_agent.php'; // barre latérale, agent.css
require_once __DIR__ . '/../Backend/Config/db.php';

/* ---------------------------------------------------------
   Sécurité HTTP renforcée
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Page du portail : réservée aux managers et admins (comme la création d'agent)
exiger_niveau(['manager', 'admin']);

$stmt = $pdo->prepare("
    SELECT a.nom AS nom_agent, a.prenom AS prenom_agent, a.role, a.niveau_acces, s.nom_service AS Nom_service
    FROM agents a LEFT JOIN services s ON a.service_id = s.id
    WHERE a.id = ?
");
$stmt->execute([(int) $_SESSION['agent_id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent créé - Espace agent</title>
    <?= feuillesAgent() ?>
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';
                   script-src 'self'; object-src 'none'; base-uri 'self';
                   connect-src 'self' https://generativelanguage.googleapis.com;">
</head>

<body>

<?= debutPageAgent('agents', $agent) ?>

<main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-10 lg:py-16">
    <div class="carte-agent p-8! text-center sm:p-10!">
        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-marque-50 text-2xl text-marque-600 ring-1 ring-marque-600/20"><?= icone('valide') ?></span>
        <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-encre-900">L’agent a été créé</h1>
        <p class="mx-auto mt-3 max-w-md text-encre-600">
            Il va recevoir un e-mail pour activer son compte et choisir son mot de passe.
            Le lien est valable 24 heures.
        </p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="dashboard_agent.php" class="bouton-agent px-5 py-3"><?= icone('tableau') ?> Tableau de bord</a>
            <a href="inscription_agent.php" class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-encre-700 ring-1 ring-encre-200 hover:bg-encre-50"><?= icone('inscription') ?> Créer un autre agent</a>
        </div>
    </div>
</main>

<?= finPageAgent() ?>

</body>
</html>
