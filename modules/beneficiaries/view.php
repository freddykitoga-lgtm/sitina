<?php
// ============================================
// FICHIER : modules/beneficiaries/view.php
// RÔLE : Fiche individuelle d'un bénéficiaire
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
    setFlash('danger', 'Bénéficiaire non trouvé.');
    rediriger('index.php');
}

// Récupération du bénéficiaire
$stmt = $pdo->prepare("SELECT * FROM beneficiaries WHERE id = ?");
$stmt->execute([$id]);
$beneficiaire = $stmt->fetch();

if (!$beneficiaire) {
    setFlash('danger', 'Bénéficiaire non trouvé.');
    rediriger('index.php');
}

// Récupération des inscriptions du bénéficiaire
try {
    $stmt = $pdo->prepare("
        SELECT e.*, t.name as formation_name, s.session_code, s.start_date, s.end_date 
        FROM enrollments e
        LEFT JOIN sessions s ON s.id = e.session_id
        LEFT JOIN trainings t ON t.id = s.training_id
        WHERE e.beneficiary_id = ?
        ORDER BY e.enrollment_date DESC
    ");
    $stmt->execute([$id]);
    $inscriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $inscriptions = [];
}

// Récupération des micro-projets
try {
    $stmt = $pdo->prepare("
        SELECT * FROM micro_projects 
        WHERE beneficiary_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$id]);
    $microProjets = $stmt->fetchAll();
} catch (PDOException $e) {
    $microProjets = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche bénéficiaire - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-header .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto;
        }
        .badge-statut-active { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-inactive { background: #9ca3af; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-graduated { background: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-enrolled { background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-completed { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-statut-dropped { background: #ef4444; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user"></i> Fiche bénéficiaire</h4>
        <div>
            <a href="edit.php?id=<?php echo $beneficiaire['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- En-tête du profil -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>
            <div class="col-md-7">
                <h2><?php echo htmlspecialchars($beneficiaire['first_name'] . ' ' . $beneficiaire['last_name']); ?></h2>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <small><i class="fas fa-id-card"></i> Code : <?php echo $beneficiaire['code']; ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><i class="fas fa-venus-mars"></i> <?php echo $beneficiaire['gender'] === 'F' ? 'Féminin' : 'Masculin'; ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><i class="fas fa-calendar-alt"></i> 
                            <?php 
                            if ($beneficiaire['birth_date'] && $beneficiaire['birth_date'] !== '0000-00-00') {
                                $birth = new DateTime($beneficiaire['birth_date']);
                                $now = new DateTime();
                                $age = $birth->diff($now)->y;
                                echo date('d/m/Y', strtotime($beneficiaire['birth_date'])) . ' (' . $age . ' ans)';
                            } else {
                                echo '-';
                            }
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-end">
                <span class="badge-statut-<?php echo strtolower($beneficiaire['status']); ?>" style="font-size:16px; padding:8px 20px;">
                    <?php echo $beneficiaire['status'] === 'Active' ? 'Actif' : ($beneficiaire['status'] === 'Graduated' ? 'Diplômé' : 'Inactif'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Informations personnelles
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="width:40%">Téléphone</th>
                            <td><?php echo $beneficiaire['phone'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Village</th>
                            <td><?php echo $beneficiaire['village'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Situation familiale</th>
                            <td>
                                <?php
                                $status_family = [
                                    'Single' => 'Célibataire',
                                    'Maried' => 'Marié(e)',
                                    'Widow' => 'Veuf(ve)',
                                    'Head' => 'Chef(fe) de ménage'
                                ];
                                echo $status_family[$beneficiaire['family_status']] ?? $beneficiaire['family_status'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Niveau d'éducation</th>
                            <td>
                                <?php
                                $education = [
                                    'None' => 'Jamais scolarisé',
                                    'Primary_incomplete' => 'Primaire non achevé',
                                    'Primary_complete' => 'Primaire achevé',
                                    'Secondary_incomplete' => 'Secondaire non achevé'
                                ];
                                echo $education[$beneficiaire['education_level']] ?? $beneficiaire['education_level'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Date d'inscription</th>
                            <td><?php echo date('d/m/Y', strtotime($beneficiaire['registration_date'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-sticky-note"></i> Notes
                </div>
                <div class="card-body">
                    <?php echo $beneficiaire['notes'] ? nl2br(htmlspecialchars($beneficiaire['notes'])) : '<p class="text-muted">Aucune note.</p>'; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>