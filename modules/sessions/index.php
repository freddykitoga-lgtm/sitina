<?php
// ============================================
// FICHIER : modules/sessions/index.php
// RÔLE : Liste des sessions de formation
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
$training_id = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;

// Construction de la requête
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(s.session_code LIKE ? OR t.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $conditions[] = "s.status = ?";
    $params[] = $status;
}

if ($training_id > 0) {
    $conditions[] = "s.training_id = ?";
    $params[] = $training_id;
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Récupérer les données
$sql = "
    SELECT s.*, t.name as formation_name 
    FROM sessions s
    JOIN trainings t ON t.id = s.training_id
    $where
    ORDER BY s.start_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Récupérer les formations pour le filtre
$stmt = $pdo->query("SELECT id, name FROM trainings ORDER BY name");
$trainings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-status-planning { background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-en-cours { background: #0d6efd; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-termine { background: #198754; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-annule { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-alt"></i> Gestion des sessions</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle session
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-2">
                    <select name="training_id" class="form-select">
                        <option value="">Toutes les formations</option>
                        <?php foreach ($trainings as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $training_id == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo $t['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Planning" <?php echo $status === 'Planning' ? 'selected' : ''; ?>>Planification</option>
                        <option value="En cours" <?php echo $status === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="Terminé" <?php echo $status === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="Annulé" <?php echo $status === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
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
            <?php if (count($sessions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code session</th>
                                <th>Formation</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Participants</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <?php
                                // Compter les inscriptions
                                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE session_id = ?");
                                $stmt->execute([$session['id']]);
                                $count = $stmt->fetch()['total'];
                                ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $session['session_code']; ?></span></td>
                                    <td><?php echo $session['formation_name']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($session['start_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($session['end_date'])); ?></td>
                                    <td><?php echo $count . ' / ' . $session['max_participants']; ?></td>
                                    <td>
                                        <span class="badge-status-<?php 
                                            echo $session['status'] === 'Planning' ? 'planning' : 
                                                ($session['status'] === 'En cours' ? 'en-cours' : 
                                                ($session['status'] === 'Terminé' ? 'termine' : 'annule')); 
                                        ?>">
                                            <?php echo $session['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-danger btn-delete-confirm" title="Supprimer">
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
                    Aucune session trouvée.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>