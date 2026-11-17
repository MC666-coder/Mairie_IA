<?php
/* =========================================================
   Masquage des données personnelles AVANT envoi à l'IA (RGPD)
   ---------------------------------------------------------
   Principe de minimisation : l'IA n'a besoin que du « quoi » et du
   « où » d'une demande, jamais du « qui ». Tout ce qui permet
   d'identifier un citoyen est remplacé par un marqueur entre crochets.

   On garde volontairement les noms de rues, d'écoles ou de lieux :
   ils sont utiles à l'analyse et ne désignent pas une personne.
   Seul le NUMÉRO de la maison est masqué (« [n°] rue des Lilas »).

   Ce filtre est une protection automatique, pas une garantie : un nom
   écrit sans majuscule ou sans civilité peut passer. Le prompt interdit
   donc aussi à l'IA de répéter une donnée personnelle, et l'agent est
   invité à ne pas en saisir.
   ========================================================= */

function sanitizeAgentMessage(string $message): string
{
    $remplacements = [
        // Adresses email
        '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/u'
            => '[email]',

        // IBAN (FR76 3000 6000 ...)
        '/\b[A-Z]{2}\d{2}(?:\s?[A-Z0-9]{4}){3,7}(?:\s?[A-Z0-9]{1,3})?\b/u'
            => '[iban]',

        // Numéro de sécurité sociale (NIR : 1 85 05 78 006 084 36)
        '/\b[12]\s?\d{2}\s?(?:0[1-9]|1[0-2]|[2-9]\d)\s?(?:\d{2}|2[AB])\s?\d{3}\s?\d{3}(?:\s?\d{2})?\b/u'
            => '[n° sécu]',

        // Téléphones français (06 12 34 56 78, +33 6 12 34 56 78, 06.12.34.56.78)
        '/(?:\+33\s?|\b0)[1-9](?:[\s.\-]?\d{2}){4}\b/u'
            => '[téléphone]',

        // Cartes bancaires (13 à 19 chiffres, avec ou sans espaces)
        '/\b(?:\d[ \-]?){12,18}\d\b/u'
            => '[carte]',

        // Date de naissance (« né le 12/03/1950 », « née en 1948 »)
        '/\bn[ée]e?s?\s+(?:le|en)\s+[\d\/.\-]+(?:\s+\p{L}+\s+\d{4})?/iu'
            => 'né(e) le [date]',

        // Identifiants numériques longs (n° fiscal, n° allocataire...)
        '/\b\d{6,}\b/u'
            => '[identifiant]',

        // Numéro de maison devant une voie : « 12 bis rue... » -> « [n°] rue... »
        '/\b\d{1,4}\s?(?:bis|ter)?,?\s+(?=(?:rue|avenue|av\.?|boulevard|bd\.?|impasse|chemin|route|allée|place|quai|cours|lotissement|résidence)\b)/iu'
            => '[n°] ',

        // Civilité suivie d'un nom : « Mme Durand », « M. Jean Martin »
        '/\b(M\.|Mr\.?|Mme|Mlle|Monsieur|Madame|Mademoiselle|Me|Dr\.?|Docteur)\s+(?:\p{Lu}[\p{L}\'\-]*\.?\s?){1,3}/u'
            => '$1 [nom] ',

        // Présentations : « je m'appelle Paul Leroy », « nommée Sophie »
        // (apostrophe droite ' ou typographique ’)
        '/\b(je\s+m[\'’]\s?appelle|(?:il|elle)\s+s[\'’]\s?appelle|nommée?|prénommée?)\s+(?:\p{Lu}[\p{L}\'’\-]*\s?){1,3}/iu'
            => '$1 [nom] ',

        // Prénom + NOM en capitales : « Jean DUPONT », « Marie-Claire LE GALL »
        '/\b(?!(?:Le|La|Les|Au|Aux|Du|Des|Un|Une|Mon|Ma|Mes|Ce|Cette|Service|Mairie|Ville|Rue|Pour|Avec|Par)\b)\p{Lu}\p{Ll}[\p{L}\-]*\s+\p{Lu}{2,}(?:[\s\-]\p{Lu}{2,})*\b/u'
            => '[nom]',
    ];

    foreach ($remplacements as $motif => $remplacement) {
        $message = preg_replace($motif, $remplacement, $message) ?? $message;
    }

    // Données sensibles (RGPD, article 9) : santé, religion, orientation
    // sexuelle, opinions, condamnations. Le marqueur garde l'information
    // utile (« il y a une fragilité ») sans le détail.
    $sensibles = [
        '[information de santé]' => 'diab[ée]t\p{L}*|cancer\p{L}*|chimioth[ée]rapie|dialys\p{L}*|VIH|s[ée]ropositi\p{L}*|sida|alzheimer|parkinson|[ée]pilep\p{L}*|d[ée]pressi\p{L}*|psychiatri\p{L}*|schizophr\p{L}*|bipolaire|handicap\p{L}*|invalidit[ée]|enceinte|grossesse|hospitalis\p{L}*|insuffisan\p{L}*\s+(?:cardiaque|r[ée]nale|respiratoire)|addiction|alcoolique|toxicomane',
        '[information religieuse]' => 'musulman\p{L}*|juif|juive|chr[ée]tien\p{L}*|catholique|protestant\p{L}*|bouddhiste|ath[ée]e|ramadan',
        '[information personnelle sensible]' => 'homosexuel\p{L}*|lesbienne|gay|transgenre|bisexuel\p{L}*|syndiqu[ée]e?s?|casier\s+judiciaire|condamn[ée]e?s?|sorti\p{L}*\s+de\s+prison',
    ];
    foreach ($sensibles as $marqueur => $termes) {
        $message = preg_replace('/\b(?:' . $termes . ')\b/iu', $marqueur, $message) ?? $message;
    }

    // Nettoyage des espaces multiples
    $message = preg_replace('/[ \t]{2,}/u', ' ', $message) ?? $message;
    return trim($message);
}
