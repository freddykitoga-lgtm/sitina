<?php
// ============================================
// FICHIER : modules/microprojects/index.php
// RÔLE : Liste des micro-projets
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
$type = isset($_GET['type']) ? nettoyer($_GET['type']) : '';

// Construction de la requête
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(b.first_name LIKE ? OR b.last_name LIKE ? OR mp.project_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $conditions[] = "mp.status = ?";
    $params[] = $status;
}

if (!empty($type)) {
    $conditions[] = "mp.project_type = ?";
    $params[] = $type;
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Récupérer les données
$sql = "
    SELECT mp.*, 
           b.first_name, b.last_name, b.code as beneficiary_code,
           t.name as formation_name
    FROM micro_projects mp
    JOIN beneficiaries b ON b.id = mp.beneficiary_id
    LEFT JOIN enrollments e ON e.id = mp.enrollment_id
    LEFT JOIN sessions s ON s.id = e.session_id
    LEFT JOIN trainings t ON t.id = s.training_id
    $where
    ORDER BY mp.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$microprojects = $stmt->fetchAll();

// Récupérer les types pour le filtre
$types = ['Commerce', 'Elevage', 'Agroalimentaire', 'Artisanat', 'Services', 'Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro-projets - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-status-en-attente { background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-lance { background: #0d6efd; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-en-cours { background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-termine { background: #198754; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-en-difficulte { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-hand-holding-usd"></i> Gestion des micro-projets</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau micro-projet
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="En attente" <?php echo $status === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="Lancé" <?php echo $status === 'Lancé' ? 'selected' : ''; ?>>Lancé</option>
                        <option value="En cours" <?php echo $status === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="Terminé" <?php echo $status === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="En difficulté" <?php echo $status === 'En difficulté' ? 'selected' : ''; ?>>En difficulté</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
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

    <!-- Liste -->
    <div class="card">
        <div class="card-body">
            <?php if (count($microprojects) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bénéficiaire</th>
                                <th>Projet</th>
                                <th>Type</th>
                                <th>Montant</th>
                                <th>Date d'octroi</th>
                                <th>Suivi 3 mois</th>
                                <th>Revenus</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($microprojects as $project): ?>
                                <tr>
                                    <td>
                                        <a href="../beneficiaries/view.php?id=<?php echo $project['beneficiary_id']; ?>">
                                            <?php echo $project['first_name'] . ' ' . $project['last_name']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $project['project_name']; ?></td>
                                    <td><?php echo $project['project_type']; ?></td>
                                    <td><?php echo number_format($project['amount_received'], 0, ',', ' '); ?> FCFA</td>
                                    <td><?php echo date('d/m/Y', strtotime($project['disbursement_date'])); ?></td>
                                    <td>
                                        <?php if ($project['follow_up_date_3months']): ?>
                                            <?php echo date('d/m/Y', strtotime($project['follow_up_date_3months'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($project['income_increased']): ?>
                                            <span class="badge bg-success"><i class="fas fa-arrow-up"></i> Augmentés</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non évalué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-status-<?php 
                                            echo $project['status'] === 'En attente' ? 'en-attente' : 
                                                ($project['status'] === 'Lancé' ? 'lance' : 
                                                ($project['status'] === 'En cours' ? 'en-cours' : 
                                                ($project['status'] === 'Terminé' ? 'termine' : 'en-difficulte'))); 
                                        ?>">
                                            <?php echo $project['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger btn-delete-confirm" title="Supprimer">
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
                    Aucun micro-projet trouvé.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>