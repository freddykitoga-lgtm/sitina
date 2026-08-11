<?php
// ============================================
// FICHIER : modules/microprojects/delete.php
// RÔLE : Supprimer un micro-projet
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Micro-projet non trouvé.');
    rediriger('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM micro_projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Micro-projet non trouvé.');
    rediriger('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM micro_projects WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Micro-projet supprimé avec succès !');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('index.php');
?>