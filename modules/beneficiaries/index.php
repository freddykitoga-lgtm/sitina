<?php
// ============================================
// FICHIER : modules/beneficiaries/index.php
// RÔLE : Liste des bénéficiaires
// ============================================

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../config/constants.php';  // <-- AJOUTER CETTE LIGNE

// Récupérer les filtres
$search = isset($_GET['search']) ? nettoyer($_GET['search']) : '';
$status = isset($_GET['status']) ? nettoyer($_GET['status']) : '';
$gender = isset($_GET['gender']) ? nettoyer($_GET['gender']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = PAGINATION_LIMIT;
$offset = ($page - 1) * $limit;

// Construction de la requête
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($gender)) {
    $conditions[] = "gender = ?";
    $params[] = $gender;
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Compter le total
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM beneficiaries $where");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Récupérer les données
$sql = "SELECT * FROM beneficiaries $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$beneficiaires = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bénéficiaires - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-users"></i> Gestion des bénéficiaires</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau bénéficiaire
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
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Actif</option>
                        <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactif</option>
                        <option value="Graduated" <?php echo $status === 'Graduated' ? 'selected' : ''; ?>>Diplômé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="gender" class="form-select">
                        <option value="">Tous les genres</option>
                        <option value="F" <?php echo $gender === 'F' ? 'selected' : ''; ?>>Féminin</option>
                        <option value="M" <?php echo $gender === 'M' ? 'selected' : ''; ?>>Masculin</option>
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
            <?php if (count($beneficiaires) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom complet</th>
                                <th>Genre</th>
                                <th>Âge</th>
                                <th>Village</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beneficiaires as $beneficiaire): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $beneficiaire['code']; ?></span></td>
                                    <td><?php echo $beneficiaire['first_name'] . ' ' . $beneficiaire['last_name']; ?></td>
                                    <td><?php echo getGenreLabel($beneficiaire['gender']); ?></td>
                                    <td>
                                        <?php if ($beneficiaire['birth_date']): ?>
                                            <?php echo date_diff(new DateTime($beneficiaire['birth_date']), new DateTime())->y; ?> ans
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $beneficiaire['village'] ?: '-'; ?></td>
                                    <td>
                                        <span class="badge-statut-<?php echo strtolower($beneficiaire['status']); ?>">
                                            <?php echo getStatusLabel($beneficiaire['status'], 'beneficiary'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $beneficiaire['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $beneficiaire['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $beneficiaire['id']; ?>" class="btn btn-sm btn-danger btn-delete-confirm" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $status; ?>&gender=<?php echo $gender; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted text-center my-4">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Aucun bénéficiaire trouvé.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>