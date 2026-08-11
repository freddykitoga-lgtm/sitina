<?php
// ============================================
// FICHIER : modules/enrollments/delete.php
// RÔLE : Supprimer une inscription
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Inscription non trouvée.');
    rediriger('index.php');
}

// Vérifier si l'inscription existe
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE id = ?");
$stmt->execute([$id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlash('danger', 'Inscription non trouvée.');
    rediriger('index.php');
}

// Vérifier si elle a des présences
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendances WHERE enrollment_id = ?");
$stmt->execute([$id]);
$hasAttendances = $stmt->fetch()['total'] > 0;

if ($hasAttendances) {
    setFlash('danger', 'Impossible de supprimer cette inscription car elle a des présences enregistrées.');
    rediriger('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Inscription supprimée avec succès !');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('index.php');
?>