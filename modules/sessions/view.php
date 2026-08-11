<?php
// ============================================
// FICHIER : modules/sessions/view.php
// RÔLE : Détails d'une session
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
    setFlash('danger', 'Session non trouvée.');
    rediriger('index.php');
}

$stmt = $pdo->prepare("
    SELECT s.*, t.name as formation_name 
    FROM sessions s
    JOIN trainings t ON t.id = s.training_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$session = $stmt->fetch();

if (!$session) {
    setFlash('danger', 'Session non trouvée.');
    rediriger('index.php');
}

// Récupérer les inscriptions
$stmt = $pdo->prepare("
    SELECT e.*, b.first_name, b.last_name, b.code, b.phone
    FROM enrollments e
    JOIN beneficiaries b ON b.id = e.beneficiary_id
    WHERE e.session_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$id]);
$inscriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails session - SITINA</title>
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
        <h4><i class="fas fa-calendar-day"></i> Détails de la session</h4>
        <div>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:30%">Code session</th>
                            <td><strong><?php echo $session['session_code']; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Formation</th>
                            <td><?php echo $session['formation_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Période</th>
                            <td><?php echo date('d/m/Y', strtotime($session['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($session['end_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Durée</th>
                            <td>
                                <?php 
                                $debut = new DateTime($session['start_date']);
                                $fin = new DateTime($session['end_date']);
                                $diff = $debut->diff($fin);
                                echo $diff->days . ' jours (' . ceil($diff->days / 7) . ' semaines)';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Lieu</th>
                            <td><?php echo $session['location'] ?: 'Non spécifié'; ?></td>
                        </tr>
                        <tr>
                            <th>Capacité</th>
                            <td><?php echo count($inscriptions) . ' / ' . $session['max_participants']; ?> participants</td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                <span class="badge-status-<?php 
                                    echo $session['status'] === 'Planning' ? 'planning' : 
                                        ($session['status'] === 'En cours' ? 'en-cours' : 
                                        ($session['status'] === 'Terminé' ? 'termine' : 'annule')); 
                                ?>">
                                    <?php echo $session['status']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Statistiques
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Inscrits</span>
                            <span class="badge bg-primary"><?php echo count($inscriptions); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Places restantes</span>
                            <span class="badge bg-success"><?php echo max(0, $session['max_participants'] - count($inscriptions)); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Diplômés</span>
                            <span class="badge bg-warning">0</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des inscriptions -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users"></i> Participants inscrits</span>
            <a href="../enrollments/add.php?session_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Ajouter un participant
            </a>
        </div>
        <div class="card-body">
            <?php if (count($inscriptions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom complet</th>
                                <th>Téléphone</th>
                                <th>Date d'inscription</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscriptions as $inscription): ?>
                                <tr>
                                    <td><?php echo $inscription['code']; ?></td>
                                    <td><?php echo $inscription['first_name'] . ' ' . $inscription['last_name']; ?></td>
                                    <td><?php echo $inscription['phone'] ?: '-'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($inscription['enrollment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $inscription['status'] === 'Completed' ? 'success' : 
                                                ($inscription['status'] === 'In Progress' ? 'primary' : 
                                                ($inscription['status'] === 'Dropped Out' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo $inscription['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../beneficiaries/view.php?id=<?php echo $inscription['beneficiary_id']; ?>" class="btn btn-sm btn-info" title="Voir bénéficiaire">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center my-3">
                    <i class="fas fa-user-plus fa-2x d-block mb-2"></i>
                    Aucun participant inscrit pour le moment.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>