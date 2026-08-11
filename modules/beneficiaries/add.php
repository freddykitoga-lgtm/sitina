<?php
// ============================================
// FICHIER : modules/beneficiaries/add.php
// RÔLE : Ajout d'un bénéficiaire
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$error = '';

// Vérifier que la table beneficiaries existe
try {
    $pdo->query("SELECT 1 FROM beneficiaries LIMIT 1");
} catch (PDOException $e) {
    $error = 'La table beneficiaries n\'existe pas. Veuillez contacter l\'administrateur.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = isset($_POST['first_name']) ? nettoyer($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? nettoyer($_POST['last_name']) : '';
    $birth_date = isset($_POST['birth_date']) && !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $phone = isset($_POST['phone']) ? nettoyer($_POST['phone']) : '';
    $village = isset($_POST['village']) ? nettoyer($_POST['village']) : '';
    $family_status = isset($_POST['family_status']) ? $_POST['family_status'] : 'Single';
    $education_level = isset($_POST['education_level']) ? $_POST['education_level'] : 'None';
    $notes = isset($_POST['notes']) ? nettoyer($_POST['notes']) : '';

    if (empty($first_name) || empty($last_name) || empty($gender)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } else {
        try {
            $code = genererCodeBeneficiaire($pdo);
            
            $stmt = $pdo->prepare("
                INSERT INTO beneficiaries (
                    code, first_name, last_name, birth_date, gender, 
                    phone, village, family_status, education_level, notes,
                    registration_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Active')
            ");
            
            $stmt->execute([
                $code, $first_name, $last_name, $birth_date, $gender,
                $phone, $village, $family_status, $education_level, $notes
            ]);
            
            $id = $pdo->lastInsertId();
            setFlash('success', 'Bénéficiaire ajouté avec succès ! Code : ' . $code);
            
            // Redirection absolue
            header('Location: view.php?id=' . $id);
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
    <title>Ajouter un bénéficiaire - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-plus"></i> Ajouter un bénéficiaire</h4>
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
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Genre *</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <option value="F">Féminin</option>
                            <option value="M">Masculin</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Village</label>
                        <input type="text" name="village" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Situation familiale</label>
                        <select name="family_status" class="form-select">
                            <option value="Single">Célibataire</option>
                            <option value="Maried">Marié(e)</option>
                            <option value="Widow">Veuf(ve)</option>
                            <option value="Head">Chef(fe) de ménage</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Niveau d'éducation</label>
                        <select name="education_level" class="form-select">
                            <option value="None">Jamais scolarisé</option>
                            <option value="Primary_incomplete">Primaire non achevé</option>
                            <option value="Primary_complete">Primaire achevé</option>
                            <option value="Secondary_incomplete">Secondaire non achevé</option>
                        </select>
                    </div>
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