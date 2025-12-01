<?php
// Exemple simple mais parlant
$heure = date('H');
$message = ($heure < 12) ? "Bonjour et bonne matinée !" : "Bon après-midi !";
session_start();
$nomUtilisateur = $_SESSION['utilisateur'] ?? 'Visiteur';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ma Première App Web</title>
</head>
<body>
    <h1><?= htmlspecialchars($message) ?></h1>
    <p>Bienvenue, <?= htmlspecialchars($nomUtilisateur) ?> !</p>
    <p>Il est actuellement <?= date('H:i') ?>.</p>
</body>
</html>
