<?php
// ============================================
// FICHIER : modules/attendances/index.php
// RÔLE : Gestion des présences
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$error = '';
$success = '';

// Récupérer les sessions actives
$stmt = $pdo->query("
    SELECT s.*, t.name as formation_name 
    FROM sessions s
    JOIN trainings t ON t.id = s.training_id
    WHERE s.status = 'En cours' OR s.status = 'Planning'
    ORDER BY s.start_date DESC
");
$sessions = $stmt->fetchAll();

// Si une session est sélectionnée
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$enrollments = [];
$attendances = [];

if ($session_id > 0) {
    // Récupérer les inscriptions de la session
    $stmt = $pdo->prepare("
        SELECT e.id as enrollment_id, e.status, b.id as beneficiary_id, b.first_name, b.last_name, b.code
        FROM enrollments e
        JOIN beneficiaries b ON b.id = e.beneficiary_id
        WHERE e.session_id = ? AND e.status != 'Dropped Out'
        ORDER BY b.first_name
    ");
    $stmt->execute([$session_id]);
    $enrollments = $stmt->fetchAll();
    
    // Récupérer les présences du jour
    $stmt = $pdo->prepare("
        SELECT enrollment_id, is_present, remarks 
        FROM attendances 
        WHERE attendance_date = ? AND enrollment_id IN (
            SELECT id FROM enrollments WHERE session_id = ?
        )
    ");
    $stmt->execute([$date, $session_id]);
    $attendances = [];
    while ($row = $stmt->fetch()) {
        $attendances[$row['enrollment_id']] = $row;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $session_id = (int)$_POST['session_id'];
    $date = $_POST['date'] ?? date('Y-m-d');
    
    try {
        // Supprimer les présences existantes pour ce jour
        $stmt = $pdo->prepare("
            DELETE FROM attendances 
            WHERE attendance_date = ? AND enrollment_id IN (
                SELECT id FROM enrollments WHERE session_id = ?
            )
        ");
        $stmt->execute([$date, $session_id]);
        
        // Insérer les nouvelles présences
        foreach ($_POST['presence'] as $enrollment_id => $is_present) {
            $remarks = $_POST['remarks'][$enrollment_id] ?? '';
            $stmt = $pdo->prepare("
                INSERT INTO attendances (enrollment_id, attendance_date, is_present, remarks)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$enrollment_id, $date, $is_present == '1', $remarks]);
        }
        
        setFlash('success', 'Présences enregistrées avec succès pour le ' . date('d/m/Y', strtotime($date)));
        header('Location: index.php?session_id=' . $session_id . '&date=' . $date);
        exit;
        
    } catch (PDOException $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Présences - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-clipboard-check"></i> Gestion des présences</h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Sélection de la session -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <select name="session_id" class="form-select" required>
                        <option value="">Sélectionner une session</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $session_id == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo $s['formation_name'] . ' - ' . $s['session_code']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Charger
                    </button>
                </div>
                <div class="col-md-3">
                    <a href="index.php" class="btn btn-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($session_id > 0 && count($enrollments) > 0): ?>
        <!-- Formulaire de présence -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i> Pointage du <?php echo date('d/m/Y', strtotime($date)); ?>
                <span class="badge bg-primary float-end"><?php echo count($enrollments); ?> inscrits</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Nom complet</th>
                                    <th>Présent</th>
                                    <th>Observations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($enrollments as $enrollment): ?>
                                    <?php 
                                    $present = isset($attendances[$enrollment['enrollment_id']]) ? $attendances[$enrollment['enrollment_id']]['is_present'] : true;
                                    $remark = isset($attendances[$enrollment['enrollment_id']]) ? $attendances[$enrollment['enrollment_id']]['remarks'] : '';
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $enrollment['code']; ?></td>
                                        <td><?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <input type="radio" name="presence[<?php echo $enrollment['enrollment_id']; ?>]" 
                                                       value="1" <?php echo $present ? 'checked' : ''; ?> class="btn-check" id="present_<?php echo $enrollment['enrollment_id']; ?>">
                                                <label class="btn btn-sm btn-success" for="present_<?php echo $enrollment['enrollment_id']; ?>">
                                                    <i class="fas fa-check"></i> Présent
                                                </label>
                                                <input type="radio" name="presence[<?php echo $enrollment['enrollment_id']; ?>]" 
                                                       value="0" <?php echo !$present ? 'checked' : ''; ?> class="btn-check" id="absent_<?php echo $enrollment['enrollment_id']; ?>">
                                                <label class="btn btn-sm btn-danger" for="absent_<?php echo $enrollment['enrollment_id']; ?>">
                                                    <i class="fas fa-times"></i> Absent
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?php echo $enrollment['enrollment_id']; ?>]" 
                                                   class="form-control form-control-sm" placeholder="Observation..." 
                                                   value="<?php echo htmlspecialchars($remark); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" name="save_attendance" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les présences
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($session_id > 0 && count($enrollments) == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Aucun participant inscrit à cette session.
        </div>
    <?php endif; ?>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>