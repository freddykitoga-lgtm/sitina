<?php
// ============================================
// FICHIER : modules/reports/index.php
// RÔLE : Génération de rapports
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Récupérer les statistiques
// Total bénéficiaires
$stmt = $pdo->query("SELECT COUNT(*) as total FROM beneficiaries");
$totalBeneficiaires = $stmt->fetch()['total'] ?? 0;

// Bénéficiaires par genre
$stmt = $pdo->query("SELECT gender, COUNT(*) as total FROM beneficiaries GROUP BY gender");
$beneficiairesParGenre = $stmt->fetchAll();

// Bénéficiaires par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM beneficiaries GROUP BY status");
$beneficiairesParStatut = $stmt->fetchAll();

// Bénéficiaires par formation
$stmt = $pdo->query("
    SELECT t.name, COUNT(e.id) as total 
    FROM trainings t
    LEFT JOIN sessions s ON s.training_id = t.id
    LEFT JOIN enrollments e ON e.session_id = s.id
    GROUP BY t.id
");
$beneficiairesParFormation = $stmt->fetchAll();

// Inscriptions par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM enrollments GROUP BY status");
$inscriptionsParStatut = $stmt->fetchAll();

// Micro-projets par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM micro_projects GROUP BY status");
$microProjetsParStatut = $stmt->fetchAll();

// Total micro-projets et montant total
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(amount_received) as total_amount FROM micro_projects");
$microProjetsStats = $stmt->fetch();

// Derniers bénéficiaires
$stmt = $pdo->query("
    SELECT * FROM beneficiaries 
    ORDER BY created_at DESC 
    LIMIT 10
");
$derniersBeneficiaires = $stmt->fetchAll();

// Dernières inscriptions
$stmt = $pdo->query("
    SELECT e.*, b.first_name, b.last_name, t.name as formation_name
    FROM enrollments e
    JOIN beneficiaries b ON b.id = e.beneficiary_id
    JOIN sessions s ON s.id = e.session_id
    JOIN trainings t ON t.id = s.training_id
    ORDER BY e.created_at DESC
    LIMIT 10
");
$dernieresInscriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - SITINA</title>
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
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .stat-card .stat-icon { font-size: 32px; opacity: 0.7; }
        .stat-card .stat-number { font-size: 24px; font-weight: 700; }
        .stat-card .stat-label { color: #666; font-size: 14px; }
        .stat-card.bg-primary-light { background: linear-gradient(135deg, #e8ecff, #d5dbff); }
        .stat-card.bg-success-light { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .stat-card.bg-warning-light { background: linear-gradient(135deg, #fef3c7, #fde68a); }
        .stat-card.bg-danger-light { background: linear-gradient(135deg, #fee2e2, #fca5a5); }
        .stat-card.bg-info-light { background: linear-gradient(135deg, #cffafe, #a5f3fc); }
        .print-btn { margin-bottom: 20px; }
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .stat-card { border: 1px solid #ddd !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4><i class="fas fa-file-alt"></i> Rapports et statistiques</h4>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer / PDF
        </button>
    </div>

    <!-- Statistiques globales -->
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Bénéficiaires</div>
                        <div class="stat-number"><?php echo $totalBeneficiaires; ?></div>
                    </div>
                    <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-success-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Inscriptions</div>
                        <div class="stat-number"><?php 
                            $total = 0;
                            foreach ($inscriptionsParStatut as $s) $total += $s['total'];
                            echo $total;
                        ?></div>
                    </div>
                    <div class="stat-icon text-success"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-warning-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Micro-projets</div>
                        <div class="stat-number"><?php echo $microProjetsStats['total'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon text-warning"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-danger-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Montant total octroyé</div>
                        <div class="stat-number"><?php echo number_format($microProjetsStats['total_amount'] ?? 0, 0, ',', ' '); ?> FCFA</div>
                    </div>
                    <div class="stat-icon text-danger"><i class="fas fa-coins"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-pie"></i> Bénéficiaires par genre</div>
                <div class="card-body">
                    <canvas id="chartGenre" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-pie"></i> Bénéficiaires par statut</div>
                <div class="card-body">
                    <canvas id="chartStatus" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-bar"></i> Inscriptions par formation</div>
                <div class="card-body">
                    <canvas id="chartFormation" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-doughnut"></i> Micro-projets par statut</div>
                <div class="card-body">
                    <canvas id="chartMicroProjects" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers bénéficiaires -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-plus"></i> Derniers bénéficiaires inscrits</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Genre</th>
                            <th>Village</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniersBeneficiaires as $b): ?>
                            <tr>
                                <td><?php echo $b['code']; ?></td>
                                <td><?php echo $b['first_name'] . ' ' . $b['last_name']; ?></td>
                                <td><?php echo $b['gender'] === 'F' ? 'Féminin' : 'Masculin'; ?></td>
                                <td><?php echo $b['village'] ?: '-'; ?></td>
                                <td><?php echo $b['status']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b['registration_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dernières inscriptions -->
    <div class="card">
        <div class="card-header"><i class="fas fa-clock"></i> Dernières inscriptions</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Bénéficiaire</th>
                            <th>Formation</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresInscriptions as $e): ?>
                            <tr>
                                <td><?php echo $e['first_name'] . ' ' . $e['last_name']; ?></td>
                                <td><?php echo $e['formation_name']; ?></td>
                                <td><?php echo $e['status']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($e['enrollment_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Graphique : Genre
        const genreLabels = <?php 
            $labels = [];
            $data = [];
            foreach ($beneficiairesParGenre as $g) {
                $labels[] = $g['gender'] === 'F' ? 'Féminin' : 'Masculin';
                $data[] = $g['total'];
            }
            echo json_encode($labels);
        ?>;
        const genreData = <?php echo json_encode(array_column($beneficiairesParGenre, 'total')); ?>;
        
        new Chart(document.getElementById('chartGenre'), {
            type: 'pie',
            data: {
                labels: genreLabels,
                datasets: [{
                    data: genreData,
                    backgroundColor: ['#667eea', '#10b981']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Graphique : Statut bénéficiaires
        const statusLabels = <?php 
            $labels = [];
            foreach ($beneficiairesParStatut as $s) {
                $labels[] = $s['status'] === 'Active' ? 'Actif' : ($s['status'] === 'Graduated' ? 'Diplômé' : 'Inactif');
            }
            echo json_encode($labels);
        ?>;
        const statusData = <?php echo json_encode(array_column($beneficiairesParStatut, 'total')); ?>;
        
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#10b981', '#667eea', '#9ca3af']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Graphique : Formations
        const formationLabels = <?php echo json_encode(array_column($beneficiairesParFormation, 'name')); ?>;
        const formationData = <?php echo json_encode(array_column($beneficiairesParFormation, 'total')); ?>;
        
        new Chart(document.getElementById('chartFormation'), {
            type: 'bar',
            data: {
                labels: formationLabels,
                datasets: [{
                    label: 'Inscriptions',
                    data: formationData,
                    backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444'],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Graphique : Micro-projets
        const mpLabels = <?php echo json_encode(array_column($microProjetsParStatut, 'status')); ?>;
        const mpData = <?php echo json_encode(array_column($microProjetsParStatut, 'total')); ?>;
        const mpColors = ['#6c757d', '#0d6efd', '#f59e0b', '#198754', '#dc3545'];
        
        new Chart(document.getElementById('chartMicroProjects'), {
            type: 'pie',
            data: {
                labels: mpLabels,
                datasets: [{
                    data: mpData,
                    backgroundColor: mpColors.slice(0, mpData.length)
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>