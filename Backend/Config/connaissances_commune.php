<?php
/* =========================================================
   Connaissances de la commune pour l'IA citoyen
   ---------------------------------------------------------
   L'IA d'accueil ne répond QU'À PARTIR de ces informations, toutes
   publiques et tenues à jour par la mairie dans le portail :
     - ia_conn_ville : fiches (horaires, contacts, démarches...)
     - services      : services municipaux et délais moyens
     - travaux       : chantiers en cours et à venir
     - evenements    : événements publiés et visibles des citoyens
     - news          : dernières actualités publiées
   Aucune donnée personnelle n'est lue ici.
   ========================================================= */

/** Taille maximale du texte envoyé à l'IA (maîtrise du coût et du délai). */
const CONNAISSANCES_MAX_CARACTERES = 20000;

/**
 * Construit le texte des connaissances, section par section.
 * Une table vide ou absente est simplement ignorée.
 */
function connaissancesCommune(PDO $pdo): string
{
    $sections = [];

    $lire = function (string $sql) use ($pdo): array {
        try {
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("connaissancesCommune : lecture impossible");
            return [];
        }
    };

    // Fiches d'information de la mairie
    $fiches = $lire("SELECT categorie, titre, contenu FROM ia_conn_ville ORDER BY categorie, titre");
    if ($fiches) {
        $texte = "FICHES DE LA MAIRIE\n";
        foreach ($fiches as $f) {
            $texte .= "[" . ($f['categorie'] ?? 'mairie') . "] " . trim($f['titre']) . " : " . trim($f['contenu']) . "\n";
        }
        $sections[] = $texte;
    }

    // Services municipaux
    $services = $lire("SELECT nom_service, description_service, delai_moyen FROM services WHERE actif = 1 ORDER BY nom_service");
    if ($services) {
        $texte = "SERVICES MUNICIPAUX (utilise ces noms exacts)\n";
        foreach ($services as $s) {
            $delai = $s['delai_moyen'] ? " (délai moyen de traitement : {$s['delai_moyen']} jours)" : '';
            $texte .= "- {$s['nom_service']} : {$s['description_service']}{$delai}\n";
        }
        $sections[] = $texte;
    }

    // Travaux en cours ou à venir
    $travaux = $lire("SELECT titre, rue, date_debut, date_fin, impact, statut FROM travaux
                      WHERE statut IN ('en_cours', 'a_venir') ORDER BY date_debut LIMIT 30");
    if ($travaux) {
        $texte = "TRAVAUX EN COURS OU À VENIR\n";
        foreach ($travaux as $t) {
            $texte .= "- {$t['titre']}, {$t['rue']}, du " . date('d/m/Y', strtotime($t['date_debut']))
                    . " au " . date('d/m/Y', strtotime($t['date_fin'])) . " ({$t['statut']}) : {$t['impact']}\n";
        }
        $sections[] = $texte;
    }

    // Événements publiés et visibles
    $evenements = $lire("SELECT titre, COALESCE(version_courte, description) AS texte, date_debut, date_fin, lieu
                         FROM evenements
                         WHERE statut = 'publie' AND visible_citoyens = 1 AND date_fin >= NOW()
                         ORDER BY date_debut LIMIT 20");
    if ($evenements) {
        $texte = "ÉVÉNEMENTS À VENIR\n";
        foreach ($evenements as $e) {
            $texte .= "- {$e['titre']}, le " . date('d/m/Y à H\hi', strtotime($e['date_debut']))
                    . ($e['lieu'] ? ", {$e['lieu']}" : '') . " : " . mb_substr(trim($e['texte']), 0, 300) . "\n";
        }
        $sections[] = $texte;
    }

    // Dernières actualités
    $news = $lire("SELECT titre, resume, date_publication FROM news
                   WHERE statut = 'publie' ORDER BY date_publication DESC LIMIT 5");
    if ($news) {
        $texte = "DERNIÈRES ACTUALITÉS\n";
        foreach ($news as $n) {
            $texte .= "- " . date('d/m/Y', strtotime($n['date_publication'])) . " : {$n['titre']}. {$n['resume']}\n";
        }
        $sections[] = $texte;
    }

    $resultat = implode("\n", $sections);
    if (mb_strlen($resultat) > CONNAISSANCES_MAX_CARACTERES) {
        $resultat = mb_substr($resultat, 0, CONNAISSANCES_MAX_CARACTERES) . "\n[...]";
    }
    return $resultat;
}
