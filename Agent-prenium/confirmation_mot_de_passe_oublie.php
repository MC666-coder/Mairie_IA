<?php
require_once __DIR__ . '/../Backend/Config/session_init.php';
require_once __DIR__ . '/../Backend/Fonctions/auth_agent.php'; // gabarit « écran partagé » (agent.css)

/* ---------------------------------------------------------
   Sécurité HTTP renforcée (même si page statique)
--------------------------------------------------------- */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* Les compteurs d'essais (anti-abus) ne sont PAS remis à zéro ici :
   n'importe qui peut ouvrir cette page directement, ce qui permettrait
   de contourner la limite. Ils repartent de zéro avec une nouvelle session. */
pageMessageAgent(
    'Vérifiez votre boîte mail',
    "Si l’adresse saisie correspond à un compte agent actif, vous allez recevoir un lien sécurisé pour choisir un nouveau mot de passe.
Ce lien est valable 24 heures. Pensez à regarder dans les courriers indésirables.",
    'connexion_agent.php',
    'Retour à la connexion',
    'info'
);
