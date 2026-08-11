<?php
// ============================================
// FICHIER : modules/microprojects/add.php
// RÔLE : Ajouter un micro-projet
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$error = '';

// Récupérer les bénéficiaires diplômés ou actifs
$stmt = $pdo->query("
    SELECT * FROM beneficiaries 
    WHERE status = 'Graduated' OR status = 'Active'
    ORDER BY first_name
");
$beneficiaries = $stmt->fetchAll();

// Récupérer les inscriptions complétées pour les bénéficiaires
$beneficiary_id = isset($_GET['beneficiary_id']) ? (int)$_GET['beneficiary_id'] : 0;

// Types de projets
$types = ['Commerce', 'Elevage', 'Agroalimentaire', 'Artisanat', 'Services', 'Autre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beneficiary_id = isset($_POST['beneficiary_id']) ? (int)$_POST['beneficiary_id'] : 0;
    $enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : null;
    $project_name = isset($_POST['project_name']) ? nettoyer($_POST['project_name']) : '';
    $project_type = isset($_POST['project_type']) ? $_POST['project_type'] : '';
    $amount_received = isset($_POST['amount_received']) ? (float)$_POST['amount_received'] : 0;
    $disbursement_date = isset($_POST['disbursement_date']) ? $_POST['disbursement_date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'En attente';
    $support_type = isset($_POST['support_type']) ? nettoyer($_POST['support_type']) : '';
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    if ($beneficiary_id <= 0 || empty($project_name) || empty($project_type) || $amount_received <= 0 || empty($disbursement_date)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO micro_projects (
                    beneficiary_id, enrollment_id, project_name, project_type,
                    amount_received, disbursement_date, status, support_type, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $beneficiary_id, $enrollment_id, $project_name, $project_type,
                $amount_received, $disbursement_date, $status, $support_type, $notes
            ]);
            
            setFlash('success', 'Micro-projet ajouté avec succès !');
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'ajout : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau micro-projet - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-hand-holding-usd"></i> Nouveau micro-projet</h4>
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
                                <option value="<?php echo $b['id']; ?>" <?php echo $beneficiary_id == $b['id'] ? 'selected' : ''; ?>>
                                    <?php echo $b['first_name'] . ' ' . $b['last_name'] . ' (' . $b['code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Inscription associée</label>
                        <select name="enrollment_id" class="form-select">
                            <option value="">Non associé</option>
                            <?php
                            if ($beneficiary_id > 0) {
                                $stmt = $pdo->prepare("
                                    SELECT e.id, s.session_code, t.name as formation_name
                                    FROM enrollments e
                                    JOIN sessions s ON s.id = e.session_id
                                    JOIN trainings t ON t.id = s.training_id
                                    WHERE e.beneficiary_id = ? AND e.status = 'Completed'
                                ");
                                $stmt->execute([$beneficiary_id]);
                                $enrollments = $stmt->fetchAll();
                                foreach ($enrollments as $e) {
                                    echo '<option value="' . $e['id'] . '">' . $e['formation_name'] . ' - ' . $e['session_code'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom du projet *</label>
                        <input type="text" name="project_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type de projet *</label>
                        <select name="project_type" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Montant octroyé *</label>
                        <input type="number" name="amount_received" class="form-control" step="1000" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date d'octroi *</label>
                        <input type="date" name="disbursement_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="En attente">En attente</option>
                            <option value="Lancé">Lancé</option>
                            <option value="En cours">En cours</option>
                            <option value="Terminé">Terminé</option>
                            <option value="En difficulté">En difficulté</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type de soutien reçu</label>
                    <select name="support_type" class="form-select">
                        <option value="">Sélectionner</option>
                        <option value="Plan de gestion">Plan de gestion</option>
                        <option value="Aide à l'achat">Aide à l'achat</option>
                        <option value="Accompagnement marketing">Accompagnement marketing</option>
                        <option value="Formation complémentaire">Formation complémentaire</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>