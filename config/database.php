<?php
// ============================================
// FICHIER : config/database.php
// RÔLE : Connexion PDO sécurisée à MySQL
// ============================================

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'bd_sitina');
define('DB_USER', 'root');           // Par défaut sous Laragon
define('DB_PASS', '');               // Par défaut sous Laragon (vide)
define('DB_CHARSET', 'utf8mb4');

// Options PDO pour la sécurité et les performances
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Lève des exceptions en cas d'erreur
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Retourne des tableaux associatifs
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Désactive l'émulation des requêtes préparées
    PDO::ATTR_STRINGIFY_FETCHES  => false,                     // Conserve les types de données
];

// Construction du DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    // Création de l'objet PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // (Optionnel) Décommenter pour voir en cas de connexion réussie
    // echo "Connexion à la base de données réussie !";
    
} catch (PDOException $e) {
    // En cas d'échec, afficher un message d'erreur (à désactiver en production)
    die("❌ Erreur de connexion à la base de données : " . $e->getMessage());
}
?>