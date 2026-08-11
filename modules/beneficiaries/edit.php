<?php
// ============================================
// FICHIER : modules/beneficiaries/edit.php
// RÔLE : Modification d'un bénéficiaire
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = isset($_POST['first_name']) ? nettoyer($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? nettoyer($_POST['last_name']) : '';
    $birth_date = isset($_POST['birth_date']) && !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $phone = isset($_POST['phone']) ? nettoyer($_POST['phone']) : '';
    $village = isset($_POST['village']) ? nettoyer($_POST['village']) : '';
    $family_status = isset($_POST['family_status']) ? $_POST['family_status'] : 'Single';
    $education_level = isset($_POST['education_level']) ? $_POST['education_level'] : 'None';
    $status = isset($_POST['status']) ? $_POST['status'] : 'Active';
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    if (empty($first_name) || empty($last_name) || empty($gender)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE beneficiaries SET
                    first_name = ?, last_name = ?, birth_date = ?, gender = ?,
                    phone = ?, village = ?, family_status = ?, education_level = ?,
                    status = ?, notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $first_name, $last_name, $birth_date, $gender,
                $phone, $village, $family_status, $education_level,
                $status, $notes, $id
            ]);
            
            setFlash('success', 'Bénéficiaire modifié avec succès !');
            header('Location: view.php?id=' . $id);
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
    <title>Modifier bénéficiaire - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-edit"></i> Modifier le bénéficiaire</h4>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la fiche
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
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($beneficiaire['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($beneficiaire['last_name']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="birth_date" class="form-control" 
                               value="<?php echo $beneficiaire['birth_date'] && $beneficiaire['birth_date'] !== '0000-00-00' ? $beneficiaire['birth_date'] : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Genre *</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <option value="F" <?php echo $beneficiaire['gender'] === 'F' ? 'selected' : ''; ?>>Féminin</option>
                            <option value="M" <?php echo $beneficiaire['gender'] === 'M' ? 'selected' : ''; ?>>Masculin</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($beneficiaire['phone']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Village</label>
                        <input type="text" name="village" class="form-control" 
                               value="<?php echo htmlspecialchars($beneficiaire['village']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Situation familiale</label>
                        <select name="family_status" class="form-select">
                            <option value="Single" <?php echo $beneficiaire['family_status'] === 'Single' ? 'selected' : ''; ?>>Célibataire</option>
                            <option value="Maried" <?php echo $beneficiaire['family_status'] === 'Maried' ? 'selected' : ''; ?>>Marié(e)</option>
                            <option value="Widow" <?php echo $beneficiaire['family_status'] === 'Widow' ? 'selected' : ''; ?>>Veuf(ve)</option>
                            <option value="Head" <?php echo $beneficiaire['family_status'] === 'Head' ? 'selected' : ''; ?>>Chef(fe) de ménage</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Niveau d'éducation</label>
                        <select name="education_level" class="form-select">
                            <option value="None" <?php echo $beneficiaire['education_level'] === 'None' ? 'selected' : ''; ?>>Jamais scolarisé</option>
                            <option value="Primary_incomplete" <?php echo $beneficiaire['education_level'] === 'Primary_incomplete' ? 'selected' : ''; ?>>Primaire non achevé</option>
                            <option value="Primary_complete" <?php echo $beneficiaire['education_level'] === 'Primary_complete' ? 'selected' : ''; ?>>Primaire achevé</option>
                            <option value="Secondary_incomplete" <?php echo $beneficiaire['education_level'] === 'Secondary_incomplete' ? 'selected' : ''; ?>>Secondaire non achevé</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?php echo $beneficiaire['status'] === 'Active' ? 'selected' : ''; ?>>Actif</option>
                            <option value="Inactive" <?php echo $beneficiaire['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactif</option>
                            <option value="Graduated" <?php echo $beneficiaire['status'] === 'Graduated' ? 'selected' : ''; ?>>Diplômé</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($beneficiaire['notes']); ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>