<?php
// ============================================
// FICHIER : modules/enrollments/edit.php
// RÔLE : Modifier une inscription
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Inscription non trouvée.');
    rediriger('index.php');
}

// Récupérer l'inscription
$stmt = $pdo->prepare("
    SELECT e.*, b.first_name, b.last_name, b.code, s.session_code, t.name as formation_name
    FROM enrollments e
    JOIN beneficiaries b ON b.id = e.beneficiary_id
    JOIN sessions s ON s.id = e.session_id
    JOIN trainings t ON t.id = s.training_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlash('danger', 'Inscription non trouvée.');
    rediriger('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_before = isset($_POST['test_before']) && $_POST['test_before'] !== '' ? (float)$_POST['test_before'] : null;
    $test_after = isset($_POST['test_after']) && $_POST['test_after'] !== '' ? (float)$_POST['test_after'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Enrolled';
    $certificate_number = isset($_POST['certificate_number']) ? nettoyer($_POST['certificate_number']) : null;
    $completion_date = isset($_POST['completion_date']) && !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    try {
        $stmt = $pdo->prepare("
            UPDATE enrollments SET
                test_before_score = ?,
                test_after_score = ?,
                status = ?,
                certificate_number = ?,
                completion_date = ?,
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$test_before, $test_after, $status, $certificate_number, $completion_date, $notes, $id]);
        
        setFlash('success', 'Inscription modifiée avec succès !');
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $error = 'Erreur lors de la modification : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier inscription - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-edit"></i> Modifier l'inscription</h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Bénéficiaire :</strong> <?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?> (<?php echo $enrollment['code']; ?>)<br>
                <strong>Formation :</strong> <?php echo $enrollment['formation_name']; ?> - <?php echo $enrollment['session_code']; ?>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Test avant (sur 10)</label>
                        <input type="number" name="test_before" class="form-control" step="0.5" min="0" max="10" 
                               value="<?php echo $enrollment['test_before_score']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Test après (sur 10)</label>
                        <input type="number" name="test_after" class="form-control" step="0.5" min="0" max="10" 
                               value="<?php echo $enrollment['test_after_score']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="Enrolled" <?php echo $enrollment['status'] === 'Enrolled' ? 'selected' : ''; ?>>Inscrit</option>
                            <option value="In Progress" <?php echo $enrollment['status'] === 'In Progress' ? 'selected' : ''; ?>>En cours</option>
                            <option value="Completed" <?php echo $enrollment['status'] === 'Completed' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="Dropped Out" <?php echo $enrollment['status'] === 'Dropped Out' ? 'selected' : ''; ?>>Abandonné</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">N° attestation</label>
                        <input type="text" name="certificate_number" class="form-control" 
                               value="<?php echo htmlspecialchars($enrollment['certificate_number']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de complétion</label>
                        <input type="date" name="completion_date" class="form-control" 
                               value="<?php echo $enrollment['completion_date']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($enrollment['notes']); ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>