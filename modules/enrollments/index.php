<?php
// ============================================
// FICHIER : modules/enrollments/index.php
// RÔLE : Liste des inscriptions
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Récupérer les filtres
$search = isset($_GET['search']) ? nettoyer($_GET['search']) : '';
$status = isset($_GET['status']) ? nettoyer($_GET['status']) : '';
$formation_id = isset($_GET['formation_id']) ? (int)$_GET['formation_id'] : 0;

// Construction de la requête
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(b.first_name LIKE ? OR b.last_name LIKE ? OR b.code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $conditions[] = "e.status = ?";
    $params[] = $status;
}

if ($formation_id > 0) {
    $conditions[] = "t.id = ?";
    $params[] = $formation_id;
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Récupérer les données
$sql = "
    SELECT e.*, 
           b.first_name, b.last_name, b.code as beneficiary_code,
           s.session_code, t.name as formation_name,
           t.id as formation_id
    FROM enrollments e
    JOIN beneficiaries b ON b.id = e.beneficiary_id
    JOIN sessions s ON s.id = e.session_id
    JOIN trainings t ON t.id = s.training_id
    $where
    ORDER BY e.enrollment_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Récupérer les formations pour le filtre
$stmt = $pdo->query("SELECT id, name FROM trainings ORDER BY name");
$formations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscriptions - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-status-enrolled { background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-in-progress { background: #0d6efd; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-completed { background: #198754; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-dropped-out { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-graduate"></i> Gestion des inscriptions</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle inscription
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-3">
                    <select name="formation_id" class="form-select">
                        <option value="">Toutes les formations</option>
                        <?php foreach ($formations as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $formation_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo $f['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Enrolled" <?php echo $status === 'Enrolled' ? 'selected' : ''; ?>>Inscrit</option>
                        <option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>En cours</option>
                        <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="Dropped Out" <?php echo $status === 'Dropped Out' ? 'selected' : ''; ?>>Abandonné</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="index.php" class="btn btn-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="card">
        <div class="card-body">
            <?php if (count($enrollments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bénéficiaire</th>
                                <th>Formation</th>
                                <th>Session</th>
                                <th>Date d'inscription</th>
                                <th>Test avant</th>
                                <th>Test après</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td>
                                        <a href="../beneficiaries/view.php?id=<?php echo $enrollment['beneficiary_id']; ?>">
                                            <?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?>
                                        </a>
                                        <br><small class="text-muted"><?php echo $enrollment['beneficiary_code']; ?></small>
                                    </td>
                                    <td><?php echo $enrollment['formation_name']; ?></td>
                                    <td><?php echo $enrollment['session_code']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                    <td><?php echo $enrollment['test_before_score'] ?? '-'; ?></td>
                                    <td><?php echo $enrollment['test_after_score'] ?? '-'; ?></td>
                                    <td>
                                        <span class="badge-status-<?php 
                                            echo $enrollment['status'] === 'Enrolled' ? 'enrolled' : 
                                                ($enrollment['status'] === 'In Progress' ? 'in-progress' : 
                                                ($enrollment['status'] === 'Completed' ? 'completed' : 'dropped-out')); 
                                        ?>">
                                            <?php echo $enrollment['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $enrollment['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $enrollment['id']; ?>" class="btn btn-sm btn-danger btn-delete-confirm" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center my-4">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Aucune inscription trouvée.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>