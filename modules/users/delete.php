<?php
// ============================================
// FICHIER : modules/users/delete.php
// RÔLE : Supprimer un utilisateur
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!aLeRole('admin')) {
    setFlash('danger', 'Accès refusé. Vous devez être administrateur.');
    rediriger('../dashboard/');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Utilisateur non trouvé.');
    rediriger('index.php');
}

// Empêcher de supprimer son propre compte
if ($id == $_SESSION['user_id']) {
    setFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
    rediriger('index.php');
}

// Vérifier si l'utilisateur existe
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Utilisateur non trouvé.');
    rediriger('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Utilisateur supprimé avec succès !');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('index.php');
?>