<?php
// ============================================
// FICHIER : modules/beneficiaries/delete.php
// RÔLE : Supprimer un bénéficiaire
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Bénéficiaire non trouvé.');
    rediriger('index.php');
}

// Vérifier si le bénéficiaire existe
$stmt = $pdo->prepare("SELECT * FROM beneficiaries WHERE id = ?");
$stmt->execute([$id]);
$beneficiaire = $stmt->fetch();

if (!$beneficiaire) {
    setFlash('danger', 'Bénéficiaire non trouvé.');
    rediriger('index.php');
}

// Vérifier s'il a des inscriptions (on ne supprime pas s'il a des données liées)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE beneficiary_id = ?");
$stmt->execute([$id]);
$hasEnrollments = $stmt->fetch()['total'] > 0;

if ($hasEnrollments) {
    setFlash('danger', 'Impossible de supprimer ce bénéficiaire car il a des inscriptions. Vous pouvez le désactiver à la place.');
    rediriger('view.php?id=' . $id);
}

try {
    $stmt = $pdo->prepare("DELETE FROM beneficiaries WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Bénéficiaire supprimé avec succès !');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('index.php');
?>