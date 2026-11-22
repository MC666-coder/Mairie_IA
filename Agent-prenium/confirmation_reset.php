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
unset($_SESSION['csrf_reset']); // jeton à usage unique : le formulaire est terminé

pageMessageAgent(
    'Mot de passe modifié',
    "Votre nouveau mot de passe est enregistré.
Vous pouvez vous connecter à l’espace agent.",
    'connexion_agent.php',
    'Se connecter'
);
