<?php
// ============================================
// FICHIER : modules/dashboard/index.php
// RÔLE : Tableau de bord principal
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Récupération des statistiques avec gestion des erreurs
try {
    // Total bénéficiaires
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM beneficiaries");
    $totalBeneficiaires = $stmt->fetch()['total'] ?? 0;

    // Bénéficiaires actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM beneficiaries WHERE status = 'Active'");
    $actifs = $stmt->fetch()['total'] ?? 0;

    // Bénéficiaires diplômés
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM beneficiaries WHERE status = 'Graduated'");
    $diplomes = $stmt->fetch()['total'] ?? 0;

    // Inscriptions par formation
    $stmt = $pdo->query("
        SELECT t.name, COUNT(e.id) as total 
        FROM trainings t 
        LEFT JOIN sessions s ON s.training_id = t.id
        LEFT JOIN enrollments e ON e.session_id = s.id
        GROUP BY t.id
    ");
    $inscriptionsParFormation = $stmt->fetchAll();

    // Présences du jour
    $aujourdhui = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.beneficiary_id) as total 
        FROM attendances a 
        JOIN enrollments e ON e.id = a.enrollment_id 
        WHERE a.attendance_date = ?
    ");
    $stmt->execute([$aujourdhui]);
    $presencesAujourdhui = $stmt->fetch()['total'] ?? 0;

    // Derniers bénéficiaires inscrits
    $stmt = $pdo->query("
        SELECT * FROM beneficiaries 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $derniersBeneficiaires = $stmt->fetchAll();

    // Inscriptions récentes
    $stmt = $pdo->query("
        SELECT b.first_name, b.last_name, t.name as formation, e.enrollment_date 
        FROM enrollments e 
        JOIN beneficiaries b ON b.id = e.beneficiary_id 
        JOIN sessions s ON s.id = e.session_id 
        JOIN trainings t ON t.id = s.training_id 
        ORDER BY e.enrollment_date DESC 
        LIMIT 5
    ");
    $inscriptionsRecentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // En cas d'erreur SQL, on affiche un message mais on continue
    $error = "Erreur lors du chargement des données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border: none;
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .stat-card .stat-icon {
            font-size: 32px;
            opacity: 0.7;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-card .stat-label {
            color: #666;
            font-size: 14px;
        }
        .stat-card.bg-primary-light { background: linear-gradient(135deg, #e8ecff, #d5dbff); }
        .stat-card.bg-success-light { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .stat-card.bg-warning-light { background: linear-gradient(135deg, #fef3c7, #fde68a); }
        .stat-card.bg-danger-light { background: linear-gradient(135deg, #fee2e2, #fca5a5); }
        
        .badge-statut-active { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-inactive { background: #9ca3af; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-graduated { background: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> SITINA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-chart-pie"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../beneficiaries/index.php">
                            <i class="fas fa-users"></i> Bénéficiaires
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../enrollments/index.php">
                            <i class="fas fa-user-graduate"></i> Inscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../attendances/index.php">
                            <i class="fas fa-clipboard-check"></i> Présences
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../microprojects/index.php">
                            <i class="fas fa-hand-holding-usd"></i> Micro-projets
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name'] ?? 'Utilisateur'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Statistiques -->
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Bénéficiaires</div>
                        <div class="stat-number"><?php echo $totalBeneficiaires; ?></div>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-success-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Actifs</div>
                        <div class="stat-number"><?php echo $actifs; ?></div>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-warning-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Diplômés</div>
                        <div class="stat-number"><?php echo $diplomes; ?></div>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-danger-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Présents aujourd'hui</div>
                        <div class="stat-number"><?php echo $presencesAujourdhui; ?></div>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Inscriptions par formation
                </div>
                <div class="card-body">
                    <canvas id="chartFormations" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Dernières inscriptions -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock"></i> Dernières inscriptions
                </div>
                <div class="card-body">
                    <?php if (!empty($inscriptionsRecentes)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($inscriptionsRecentes as $inscription): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $inscription['first_name'] . ' ' . $inscription['last_name']; ?>
                                    <span class="badge bg-primary"><?php echo $inscription['formation']; ?></span>
                                    <small class="text-muted"><?php echo formaterDate($inscription['enrollment_date']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Aucune inscription récente.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-plus"></i> Derniers bénéficiaires inscrits</span>
                    <a href="../beneficiaries/index.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($derniersBeneficiaires)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom complet</th>
                                        <th>Genre</th>
                                        <th>Village</th>
                                        <th>Statut</th>
                                        <th>Date d'inscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($derniersBeneficiaires as $beneficiaire): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo $beneficiaire['code']; ?></span></td>
                                            <td><?php echo $beneficiaire['first_name'] . ' ' . $beneficiaire['last_name']; ?></td>
                                            <td><?php echo getGenreLabel($beneficiaire['gender']); ?></td>
                                            <td><?php echo $beneficiaire['village'] ?: '-'; ?></td>
                                            <td>
                                                <span class="badge-statut-<?php echo strtolower($beneficiaire['status']); ?>">
                                                    <?php echo getStatusLabel($beneficiaire['status'], 'beneficiary'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formaterDate($beneficiaire['registration_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun bénéficiaire enregistré.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique
        const ctx = document.getElementById('chartFormations').getContext('2d');
        const formationLabels = <?php echo json_encode(array_column($inscriptionsParFormation, 'name')); ?>;
        const formationData = <?php echo json_encode(array_column($inscriptionsParFormation, 'total')); ?>;
        
        if (formationLabels.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: formationLabels,
                    datasets: [{
                        label: 'Nombre d\'inscriptions',
                        data: formationData,
                        backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444'],
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>