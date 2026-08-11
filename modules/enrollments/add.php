<?php
// ============================================
// FICHIER : modules/enrollments/add.php
// RÔLE : Ajouter une inscription
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$error = '';

// Récupérer les sessions disponibles
$stmt = $pdo->query("
    SELECT s.*, t.name as formation_name 
    FROM sessions s
    JOIN trainings t ON t.id = s.training_id
    WHERE s.status != 'Terminé' AND s.status != 'Annulé'
    ORDER BY s.start_date DESC
");
$sessions = $stmt->fetchAll();

// Récupérer les bénéficiaires
$stmt = $pdo->query("SELECT * FROM beneficiaries WHERE status = 'Active' ORDER BY first_name");
$beneficiaries = $stmt->fetchAll();

// Si un bénéficiaire est passé en paramètre (depuis view.php)
$selected_beneficiary = isset($_GET['beneficiary_id']) ? (int)$_GET['beneficiary_id'] : 0;
$selected_session = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beneficiary_id = isset($_POST['beneficiary_id']) ? (int)$_POST['beneficiary_id'] : 0;
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $test_before = isset($_POST['test_before']) && $_POST['test_before'] !== '' ? (float)$_POST['test_before'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Enrolled';
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    if ($beneficiary_id <= 0 || $session_id <= 0) {
        $error = 'Veuillez sélectionner un bénéficiaire et une session.';
    } else {
        try {
            // Vérifier si le bénéficiaire est déjà inscrit à cette session
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE beneficiary_id = ? AND session_id = ?");
            $stmt->execute([$beneficiary_id, $session_id]);
            if ($stmt->fetch()) {
                $error = 'Ce bénéficiaire est déjà inscrit à cette session.';
            } else {
                // Vérifier la capacité de la session
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $count = $stmt->fetch()['total'];
                
                $stmt = $pdo->prepare("SELECT max_participants FROM sessions WHERE id = ?");
                $stmt->execute([$session_id]);
                $max = $stmt->fetch()['max_participants'];
                
                if ($count >= $max) {
                    $error = 'Cette session est complète (capacité maximale atteinte).';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollments (beneficiary_id, session_id, enrollment_date, test_before_score, status, notes)
                        VALUES (?, ?, CURDATE(), ?, ?, ?)
                    ");
                    $stmt->execute([$beneficiary_id, $session_id, $test_before, $status, $notes]);
                    
                    setFlash('success', 'Inscription ajoutée avec succès !');
                    header('Location: index.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle inscription - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-plus"></i> Nouvelle inscription</h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bénéficiaire *</label>
                        <select name="beneficiary_id" class="form-select" required>
                            <option value="">Sélectionner un bénéficiaire</option>
                            <?php foreach ($beneficiaries as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php echo $selected_beneficiary == $b['id'] ? 'selected' : ''; ?>>
                                    <?php echo $b['first_name'] . ' ' . $b['last_name'] . ' (' . $b['code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Session *</label>
                        <select name="session_id" class="form-select" required>
                            <option value="">Sélectionner une session</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $selected_session == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo $s['formation_name'] . ' - ' . $s['session_code']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Test avant (sur 10)</label>
                        <input type="number" name="test_before" class="form-control" step="0.5" min="0" max="10" placeholder="Ex: 6.5">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="Enrolled">Inscrit</option>
                            <option value="In Progress">En cours</option>
                            <option value="Completed">Terminé</option>
                            <option value="Dropped Out">Abandonné</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer l'inscription
                    </button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>