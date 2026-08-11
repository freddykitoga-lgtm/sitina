<?php
// ============================================
// FICHIER : config/session.php
// RÔLE : Gestion sécurisée des sessions
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // Configuration des paramètres de session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Passer à 1 en HTTPS
    
    session_start();
}

// Régénération de l'ID pour éviter la fixation de session
if (!isset($_SESSION['_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['_regenerated'] = true;
}

// Détection des sessions inactives (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Fonction de vérification de connexion
function estConnecte() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fonction de vérification du rôle
function aLeRole($roleRequis) {
    if (!estConnecte()) return false;
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $roleRequis;
}
?>