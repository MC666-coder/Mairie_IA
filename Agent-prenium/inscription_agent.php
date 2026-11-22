<?php
// Sécurisation renforcée des cookies de session
session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Strict'
]);
/* session_init.php : initialisation de la session pour les agents */
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Middleware/auth.php';
require_once __DIR__ . '/../Backend/Fonctions/gabarit_agent.php'; // barre latérale, agent.css
require_once __DIR__ . '/../Backend/Config/db.php';

// Seul un manager ou un admin connecté peut créer un compte agent
$niveau_createur = exiger_niveau(['manager', 'admin']);

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Agent connecté (barre latérale) */
$stmt = $pdo->prepare("
    SELECT a.nom AS nom_agent, a.prenom AS prenom_agent, a.role, a.niveau_acces, s.nom_service AS Nom_service
    FROM agents a LEFT JOIN services s ON a.service_id = s.id
    WHERE a.id = ?
");
$stmt->execute([(int) $_SESSION['agent_id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* Services, rôles et niveaux proposés */
$services = [];
foreach ($pdo->query("SELECT id, nom_service FROM services ORDER BY nom_service") as $service) {
    $services[(int) $service['id']] = $service['nom_service'];
}
$roles = ['agent' => 'Agent', 'responsable' => 'Responsable'];

// Niveau d'accès : ce que la personne pourra faire (Admin seulement si le créateur est admin)
$niveaux = [
    'agent'   => ['Agent',   'Traite les demandes de son service et échange avec les citoyens.', 'utilisateur'],
    'manager' => ['Manager', 'Comme un agent, et peut aussi créer des comptes agents.',          'groupe'],
];
if ($niveau_createur === 'admin') {
    $niveaux['admin'] = ['Administrateur', 'Tous les droits, y compris créer d’autres administrateurs.', 'bouclier'];
}

/* Erreur renvoyée par le traitement et saisie conservée (affichées une seule fois) */
$erreur = (string) ($_SESSION['creation_agent_erreur'] ?? '');
$saisie = array_merge(
    ['nom_agent' => '', 'prenom_agent' => '', 'email_agent' => '', 'service_id' => '', 'role' => 'agent', 'niveau_acces' => 'agent'],
    array_map('strval', (array) ($_SESSION['creation_agent_saisie'] ?? []))
);
unset($_SESSION['creation_agent_erreur'], $_SESSION['creation_agent_saisie']);
if (!isset($niveaux[$saisie['niveau_acces']])) {
    $saisie['niveau_acces'] = 'agent';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un agent - Espace agent</title>
    <?= feuillesAgent() ?>

    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self';
                   img-src 'self' data:;
                   style-src 'self' 'unsafe-inline';
                   script-src 'self';
                   object-src 'none';
                   base-uri 'self';
                   connect-src 'self' https://generativelanguage.googleapis.com;">
</head>

<body>

<?= debutPageAgent('agents', $agent) ?>

<main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">

    <a href="dashboard_agent.php" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 -ml-2 text-sm font-semibold text-encre-500 transition hover:bg-white hover:text-encre-900">
        <?= icone('fleche-gauche') ?> Tableau de bord
    </a>

    <div class="mt-3">
        <h1 class="text-2xl font-extrabold tracking-tight text-encre-900 sm:text-3xl">Créer un agent</h1>
        <p class="mt-1 text-sm text-encre-500">Le nouvel agent recevra un e-mail pour activer son compte et choisir son mot de passe.</p>
    </div>

    <?php if ($erreur !== ''): ?>
        <div class="mt-5 flex items-start gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-600/20" role="alert">
            <?= icone('croix', 'mt-0.5') ?> <?= eAgent($erreur) ?>
        </div>
    <?php endif; ?>

    <form id="form-creer-agent" action="traitement_création_agent.php" method="POST" autocomplete="off"
          class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?= eAgent($_SESSION['csrf_token']) ?>">

        <!-- ============ FORMULAIRE ============ -->
        <div class="carte-agent grid gap-6">

            <!-- Identité -->
            <section class="grid gap-4" aria-labelledby="titre-identite">
                <h2 id="titre-identite" class="etiquette-agent">Identité</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nom_agent" class="text-sm font-semibold text-encre-800">Nom</label>
                        <input type="text" id="nom_agent" name="nom_agent" required minlength="2" maxlength="50"
                               value="<?= eAgent($saisie['nom_agent']) ?>" class="champ-agent mt-1 w-full" placeholder="Durand">
                    </div>
                    <div>
                        <label for="prenom_agent" class="text-sm font-semibold text-encre-800">Prénom</label>
                        <input type="text" id="prenom_agent" name="prenom_agent" required minlength="2" maxlength="50"
                               value="<?= eAgent($saisie['prenom_agent']) ?>" class="champ-agent mt-1 w-full" placeholder="Marie">
                    </div>
                </div>
                <div>
                    <label for="email_agent" class="text-sm font-semibold text-encre-800">Adresse e-mail professionnelle</label>
                    <input type="email" id="email_agent" name="email_agent" required maxlength="150"
                           value="<?= eAgent($saisie['email_agent']) ?>" class="champ-agent mt-1 w-full" placeholder="marie.durand@mairie.fr"
                           aria-describedby="aide-email">
                    <p id="aide-email" class="mt-1 text-xs text-encre-500">Le lien d’activation sera envoyé à cette adresse.</p>
                </div>
            </section>

            <!-- Affectation -->
            <section class="grid gap-4 border-t border-encre-100 pt-5" aria-labelledby="titre-affectation">
                <h2 id="titre-affectation" class="etiquette-agent">Affectation</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="service_id" class="text-sm font-semibold text-encre-800">Service</label>
                        <select id="service_id" name="service_id" required class="champ-agent mt-1 w-full">
                            <option value="">Choisir un service…</option>
                            <?php foreach ($services as $id => $nom): ?>
                                <option value="<?= $id ?>" <?= $saisie['service_id'] === (string) $id ? 'selected' : '' ?>><?= eAgent($nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="role" class="text-sm font-semibold text-encre-800">Rôle</label>
                        <select id="role" name="role" required class="champ-agent mt-1 w-full">
                            <?php foreach ($roles as $cle => $libelle): ?>
                                <option value="<?= $cle ?>" <?= $saisie['role'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Niveau d'accès : cartes qui expliquent les droits -->
            <fieldset class="m-0 min-w-0 border-0 border-t border-encre-100 p-0 pt-5">
                <legend class="etiquette-agent float-left mb-3 w-full">Niveau d’accès</legend>
                <div class="clear-left grid gap-2">
                    <?php foreach ($niveaux as $cle => [$libelle, $description, $icone]): ?>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-encre-50 p-4 ring-1 ring-encre-900/10 transition hover:ring-marque-500/40 has-[:checked]:ring-2 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marque-500 <?= $cle === 'admin'
                                   ? 'has-[:checked]:bg-amber-50 has-[:checked]:ring-amber-500'
                                   : 'has-[:checked]:bg-marque-50 has-[:checked]:ring-marque-600' ?>">
                            <input type="radio" name="niveau_acces" value="<?= $cle ?>" class="sr-only" required
                                   data-libelle="<?= eAgent($libelle) ?>"
                                   <?= $saisie['niveau_acces'] === $cle ? 'checked' : '' ?>>
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-base text-marque-700 ring-1 ring-encre-900/5"><?= icone($icone) ?></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-encre-900"><?= eAgent($libelle) ?></span>
                                <span class="block text-xs text-encre-600"><?= eAgent($description) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($niveaux['admin'])): ?>
                    <p class="mt-2 flex items-start gap-1.5 text-xs text-amber-800"><?= icone('alerte', 'mt-0.5 shrink-0') ?> Réservez le niveau Administrateur aux personnes de confiance : il donne tous les droits.</p>
                <?php endif; ?>
            </fieldset>
        </div>

        <!-- ============ APERÇU (rempli en direct par creer_agent.js) ============ -->
        <aside class="flex flex-col gap-4 lg:sticky lg:top-8" aria-label="Aperçu de la fiche de l’agent">
            <div class="overflow-hidden rounded-2xl bg-encre-900 p-5 text-white shadow-lg">
                <p class="text-xs font-semibold uppercase tracking-wider text-encre-400">Aperçu de la fiche</p>
                <div class="mt-4 flex items-center gap-3">
                    <span id="apercu-initiales" class="grid size-12 shrink-0 place-items-center rounded-full bg-marque-600 text-base font-bold">?</span>
                    <div class="min-w-0">
                        <p id="apercu-nom" class="truncate text-base font-bold" data-vide="Nouvel agent">Nouvel agent</p>
                        <p id="apercu-email" class="truncate text-xs text-encre-400" data-vide="adresse e-mail">adresse e-mail</p>
                    </div>
                </div>
                <dl class="mt-5 grid gap-2 border-t border-white/10 pt-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-encre-400">Service</dt><dd id="apercu-service" class="text-right font-medium" data-vide="—">—</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-encre-400">Rôle</dt><dd id="apercu-role" class="text-right font-medium" data-vide="—">—</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-encre-400">Accès</dt><dd id="apercu-niveau" class="text-right font-medium" data-vide="—">—</dd></div>
                </dl>
            </div>

            <div class="carte-agent p-4! text-sm text-encre-600">
                <p class="flex items-start gap-2"><?= icone('courrier', 'mt-0.5 shrink-0 text-marque-600') ?> Un e-mail d’activation, valable 24 heures, sera envoyé à l’agent.</p>
            </div>

            <button type="submit" class="bouton-agent w-full py-3">
                <?= icone('inscription') ?> Créer l’agent
            </button>
            <a href="dashboard_agent.php" class="text-center text-sm font-semibold text-encre-500 hover:text-encre-800">Annuler</a>
        </aside>
    </form>
</main>

<?= finPageAgent() ?>
<script src="assets/js/creer_agent.js?v=<?= @filemtime(__DIR__ . '/assets/js/creer_agent.js') ?>" defer></script>

</body>
</html>
