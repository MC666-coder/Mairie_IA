<?php
/* L'assistant IA se trouve dans l'espace citoyen (bouton flottant du tableau de bord).
   Cette ancienne adresse y renvoie : accès_chatbot.php ouvre le chat si le citoyen
   est connecté, sinon il propose la connexion. */
header('Location: ' . rawurlencode('accès_chatbot.php')); // « è » encodé dans l'adresse
exit();
