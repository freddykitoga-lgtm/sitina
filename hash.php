<?php
// Fichier : hash.php
// Rôle : Générer un hash pour le mot de passe

$password = 'Formateur@2026';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mot de passe : " . $password . "<br>";
echo "Hash : " . $hash . "<br>";
echo "<br>Copiez ce hash dans la base de données :<br>";
echo "UPDATE users SET password_hash = '" . $hash . "' WHERE email = 'formateur@sitina.org';";
?>