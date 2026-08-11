<?php
// ============================================
// FICHIER : logout.php
// RÔLE : Déconnexion
// ============================================

require_once 'config/session.php';

// Détruire la session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>