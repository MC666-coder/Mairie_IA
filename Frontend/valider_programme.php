<?php
session_start();

//on marque le programme comme vu pour cette session 
$_SESSION['programme_vu'] = true;

//redirection vers la page d'accueil
header('Location: index.php');
exit();
?>