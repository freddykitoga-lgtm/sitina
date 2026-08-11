<?php
// ============================================
// FICHIER : modules/sessions/delete.php
// RÔLE : Supprimer une session
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Session non trouvée.');
    rediriger('index.php');
}

// Vérifier si la session existe
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$id]);
$session = $stmt->fetch();

if (!$session) {
    setFlash('danger', 'Session non trouvée.');
    rediriger('index.php');
}

// Vérifier s'il y a des inscriptions
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE session_id = ?");
$stmt->execute([$id]);
$hasEnrollments = $stmt->fetch()['total'] > 0;

if ($hasEnrollments) {
    setFlash('danger', 'Impossible de supprimer cette session car elle a des inscriptions. Vous pouvez la marquer comme "Annulé" à la place.');
    rediriger('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Session supprimée avec succès !');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('index.php');
?>