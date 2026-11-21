<?php
/* =========================================================
   E-mails envoyés aux citoyens : confirmation d'adresse et mot de passe oublié
   ---------------------------------------------------------
   - Jeton aléatoire envoyé dans le lien ; seule son empreinte SHA-256 est en base
     (si la base fuit, les liens ne sont pas réutilisables).
   - Envoi par sendMail() (HTML), comme les e-mails des agents ; en local,
     Papercut intercepte les messages.
   ========================================================= */
require_once __DIR__ . '/../Config/app.php';   // url_app(), NOM_COMMUNE
require_once __DIR__ . '/sendMail.php';

/** Crée un jeton : [jeton envoyé par e-mail, empreinte stockée en base]. */
function nouveauJetonCitoyen(): array
{
    $jeton = bin2hex(random_bytes(32));
    return [$jeton, hash('sha256', $jeton)];
}

/** Empreinte d'un jeton reçu dans un lien (null si le format est invalide). */
function empreinteJeton(string $jeton): ?string
{
    return preg_match('/^[a-f0-9]{64}$/', $jeton) ? hash('sha256', $jeton) : null;
}

/**
 * Gabarit HTML commun : titre, texte, gros bouton, lien de secours.
 * Styles en ligne : les messageries ignorent les feuilles de style.
 */
function gabaritEmailBouton(string $prenom, string $titre, string $texte, string $libelleBouton, string $lien, string $mention): string
{
    $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $commune = $e(NOM_COMMUNE);

    return '<!DOCTYPE html><html lang="fr"><body style="margin:0;padding:0;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif;color:#1f2a37;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f4f7;padding:32px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:14px;border:1px solid #e3e8ef;">'
        . '<tr><td style="background:#003b8e;color:#ffffff;padding:18px 28px;border-radius:14px 14px 0 0;font-size:16px;font-weight:bold;">Mairie de ' . $commune . '</td></tr>'
        . '<tr><td style="padding:28px;">'
        . '<h1 style="margin:0 0 14px;font-size:22px;color:#0d1b2a;">' . $e($titre) . '</h1>'
        . '<p style="margin:0 0 10px;font-size:15px;line-height:1.6;">Bonjour ' . $e($prenom) . ',</p>'
        . '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;">' . $e($texte) . '</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;"><tr><td style="border-radius:10px;background:#003b8e;">'
        . '<a href="' . $e($lien) . '" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;">' . $e($libelleBouton) . '</a>'
        . '</td></tr></table>'
        . '<p style="margin:0 0 8px;font-size:13px;color:#5b6573;line-height:1.5;">' . $e($mention) . '</p>'
        . '<p style="margin:0;font-size:12px;color:#5b6573;line-height:1.5;">Le bouton ne fonctionne pas ? Copiez ce lien dans votre navigateur :<br>'
        . '<a href="' . $e($lien) . '" style="color:#003b8e;word-break:break-all;">' . $e($lien) . '</a></p>'
        . '</td></tr></table>'
        . '<p style="font-size:12px;color:#8a94a3;margin:16px 0 0;">Message automatique, merci de ne pas y répondre.</p>'
        . '</td></tr></table></body></html>';
}

/** E-mail « Confirmer mon adresse e-mail » envoyé à l'inscription. */
function envoyerEmailConfirmation(string $email, string $prenom, string $jeton): bool
{
    $lien = url_app('Frontend/verifier_email.php?token=' . urlencode($jeton));
    $html = gabaritEmailBouton(
        $prenom,
        'Confirmez votre adresse e-mail',
        'Merci pour votre inscription sur le site de la mairie. Pour activer votre espace citoyen, confirmez votre adresse e-mail en cliquant sur le bouton ci-dessous.',
        'Confirmer mon adresse e-mail',
        $lien,
        "Ce lien est valable 24 heures. Si vous n'êtes pas à l'origine de cette inscription, ignorez cet e-mail : aucun compte ne sera activé."
    );
    return envoyerEmailCitoyen($email, 'Confirmez votre adresse e-mail - Mairie de ' . NOM_COMMUNE, $html);
}

/** E-mail « Choisir un nouveau mot de passe » (mot de passe oublié). */
function envoyerEmailReinitialisation(string $email, string $prenom, string $jeton): bool
{
    $lien = url_app('Frontend/nouveau_mot_de_passe_citoyen.php?token=' . urlencode($jeton));
    $html = gabaritEmailBouton(
        $prenom,
        'Réinitialisez votre mot de passe',
        'Une demande de nouveau mot de passe a été faite pour votre espace citoyen. Cliquez sur le bouton ci-dessous pour en choisir un.',
        'Choisir un nouveau mot de passe',
        $lien,
        "Ce lien est valable 1 heure et ne peut servir qu'une fois. Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail : votre mot de passe reste inchangé."
    );
    return envoyerEmailCitoyen($email, 'Nouveau mot de passe - Mairie de ' . NOM_COMMUNE, $html);
}

/** Envoi ; en local sans serveur SMTP, l'échec est journalisé (sans l'adresse : RGPD). */
function envoyerEmailCitoyen(string $email, string $sujet, string $html): bool
{
    $envoye = @sendMail($email, $sujet, $html);
    if (!$envoye) {
        error_log('E-mail citoyen non envoyé (serveur SMTP indisponible) : ' . $sujet);
    }
    return (bool) $envoye;
}
