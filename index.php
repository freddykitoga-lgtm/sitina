<?php
// ============================================
// FICHIER : index.php (à la racine)
// RÔLE : Redirige vers le dashboard ou login
// ============================================

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

if (estConnecte()) {
    header('Location: modules/dashboard/index.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>