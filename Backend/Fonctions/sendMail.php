<?php

function sendMail($toEmail, $subject, $htmlBody) {

    // Encodage + HTML
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Expéditeur
    $headers .= "From: Espace Citoyen <no-reply@mairie.fr>\r\n";

    // Pour éviter les spams (même en local)
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Envoi (Papercut intercepte automatiquement)
    return mail($toEmail, $subject, $htmlBody, $headers);
}

?>