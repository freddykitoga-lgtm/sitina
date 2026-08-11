<?php
// ============================================
// FICHIER : modules/microprojects/edit.php
// RÔLE : Modifier un micro-projet
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
    setFlash('danger', 'Micro-projet non trouvé.');
    rediriger('index.php');
}

$stmt = $pdo->prepare("
    SELECT mp.*, b.first_name, b.last_name, b.code
    FROM micro_projects mp
    JOIN beneficiaries b ON b.id = mp.beneficiary_id
    WHERE mp.id = ?
");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Micro-projet non trouvé.');
    rediriger('index.php');
}

$types = ['Commerce', 'Elevage', 'Agroalimentaire', 'Artisanat', 'Services', 'Autre'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = isset($_POST['project_name']) ? nettoyer($_POST['project_name']) : '';
    $project_type = isset($_POST['project_type']) ? $_POST['project_type'] : '';
    $amount_received = isset($_POST['amount_received']) ? (float)$_POST['amount_received'] : 0;
    $disbursement_date = isset($_POST['disbursement_date']) ? $_POST['disbursement_date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'En attente';
    $support_type = isset($_POST['support_type']) ? nettoyer($_POST['support_type']) : '';
    $follow_up_3months = isset($_POST['follow_up_3months']) && !empty($_POST['follow_up_3months']) ? $_POST['follow_up_3months'] : null;
    $follow_up_6months = isset($_POST['follow_up_6months']) && !empty($_POST['follow_up_6months']) ? $_POST['follow_up_6months'] : null;
    $income_increased = isset($_POST['income_increased']) ? 1 : 0;
    $revenue_impact = isset($_POST['revenue_impact']) ? nettoyer($_POST['revenue_impact']) : '';
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    if (empty($project_name) || empty($project_type) || $amount_received <= 0 || empty($disbursement_date)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE micro_projects SET
                    project_name = ?, project_type = ?, amount_received = ?,
                    disbursement_date = ?, status = ?, support_type = ?,
                    follow_up_date_3months = ?, follow_up_date_6months = ?,
                    income_increased = ?, revenue_impact = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $project_name, $project_type, $amount_received,
                $disbursement_date, $status, $support_type,
                $follow_up_3months, $follow_up_6months,
                $income_increased, $revenue_impact, $notes, $id
            ]);
            
            setFlash('success', 'Micro-projet modifié avec succès !');
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $error = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier micro-projet - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-edit"></i> Modifier le micro-projet</h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Bénéficiaire :</strong> <?php echo $project['first_name'] . ' ' . $project['last_name']; ?> (<?php echo $project['code']; ?>)
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom du projet *</label>
                        <input type="text" name="project_name" class="form-control" 
                               value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type de projet *</label>
                        <select name="project_type" class="form-select" required>
                            <?php foreach ($types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $project['project_type'] === $t ? 'selected' : ''; ?>>
                                    <?php echo $t; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Montant octroyé *</label>
                        <input type="number" name="amount_received" class="form-control" step="1000" min="0" 
                               value="<?php echo $project['amount_received']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date d'octroi *</label>
                        <input type="date" name="disbursement_date" class="form-control" 
                               value="<?php echo $project['disbursement_date']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="En attente" <?php echo $project['status'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="Lancé" <?php echo $project['status'] === 'Lancé' ? 'selected' : ''; ?>>Lancé</option>
                            <option value="En cours" <?php echo $project['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="Terminé" <?php echo $project['status'] === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="En difficulté" <?php echo $project['status'] === 'En difficulté' ? 'selected' : ''; ?>>En difficulté</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type de soutien reçu</label>
                        <select name="support_type" class="form-select">
                            <option value="">Sélectionner</option>
                            <option value="Plan de gestion" <?php echo $project['support_type'] === 'Plan de gestion' ? 'selected' : ''; ?>>Plan de gestion</option>
                            <option value="Aide à l'achat" <?php echo $project['support_type'] === 'Aide à l\'achat' ? 'selected' : ''; ?>>Aide à l'achat</option>
                            <option value="Accompagnement marketing" <?php echo $project['support_type'] === 'Accompagnement marketing' ? 'selected' : ''; ?>>Accompagnement marketing</option>
                            <option value="Formation complémentaire" <?php echo $project['support_type'] === 'Formation complémentaire' ? 'selected' : ''; ?>>Formation complémentaire</option>
                            <option value="Autre" <?php echo $project['support_type'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Suivi à 3 mois</label>
                        <input type="date" name="follow_up_3months" class="form-control" 
                               value="<?php echo $project['follow_up_date_3months']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Suivi à 6 mois</label>
                        <input type="date" name="follow_up_6months" class="form-control" 
                               value="<?php echo $project['follow_up_date_6months']; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="income_increased" class="form-check-input" id="income_increased" 
                                   <?php echo $project['income_increased'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="income_increased">
                                Les revenus ont augmenté
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Impact sur les revenus</label>
                    <textarea name="revenue_impact" class="form-control" rows="2"><?php echo htmlspecialchars($project['revenue_impact']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($project['notes']); ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>