<?php
// ============================================
// FICHIER : includes/auth.php
// RÔLE : Vérification d'authentification
// ============================================

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    rediriger('login.php');
}

// Vérifier si l'utilisateur a le bon rôle (optionnel)
function verifierRole($roleRequis) {
    if (!aLeRole($roleRequis)) {
        setFlash('danger', 'Accès refusé. Vous n\'avez pas les droits nécessaires.');
        rediriger('modules/dashboard/index.php');
    }
}

// Récupérer les infos de l'utilisateur connecté
function getUtilisateurConnecte($pdo) {
    if (!estConnecte()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>