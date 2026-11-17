<?php
require_once __DIR__ . '/../Config/app.php';   // MAIL_FROM, MAIL_FROM_NAME (fichier .env)

/**
 * Envoie un email HTML. En local, Papercut intercepte les envois.
 */
function sendMail($toEmail, $subject, $htmlBody) {

    // Encodage + HTML
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Expéditeur : configuration centrale (le nom est encodé pour les accents)
    $headers .= "From: " . mb_encode_mimeheader(MAIL_FROM_NAME, 'UTF-8') . " <" . MAIL_FROM . ">\r\n";

    // Pour éviter les spams (même en local)
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Sujet encodé (accents) ; envoi (Papercut intercepte automatiquement)
    return mail($toEmail, mb_encode_mimeheader($subject, 'UTF-8'), $htmlBody, $headers);
}
