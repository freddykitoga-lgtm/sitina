<?php
// ============================================
// FICHIER : modules/users/edit.php
// RÔLE : Modifier un utilisateur
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!aLeRole('admin')) {
    setFlash('danger', 'Accès refusé. Vous devez être administrateur.');
    rediriger('../dashboard/');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Utilisateur non trouvé.');
    rediriger('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Utilisateur non trouvé.');
    rediriger('index.php');
}

$error = '';
$roles = [
    'admin' => 'Administrateur',
    'formateur' => 'Formateur',
    'me' => 'M&E (Suivi-Évaluation)',
    'lecture' => 'Lecture seule'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? nettoyer($_POST['username']) : '';
    $email = isset($_POST['email']) ? nettoyer($_POST['email']) : '';
    $full_name = isset($_POST['full_name']) ? nettoyer($_POST['full_name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'lecture';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($username) || empty($email) || empty($full_name)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } else {
        try {
            // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé par un autre compte.';
            } else {
                // Construction de la requête
                if (!empty($password)) {
                    // Si un nouveau mot de passe est fourni
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, password_hash = ?, 
                            full_name = ?, role = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $hash, $full_name, $role, $is_active, $id]);
                } else {
                    // Pas de changement de mot de passe
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, 
                            full_name = ?, role = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $full_name, $role, $is_active, $id]);
                }
                
                setFlash('success', 'Utilisateur modifié avec succès !');
                header('Location: index.php');
                exit;
            }
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
    <title>Modifier utilisateur - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-user-edit"></i> Modifier l'utilisateur</h4>
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
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="Laisser vide pour ne pas changer" minlength="6">
                        <small class="text-muted">Minimum 6 caractères (laisser vide pour conserver l'ancien)</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-select">
                            <?php foreach ($roles as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $user['role'] === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                   <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Compte actif</label>
                        </div>
                    </div>
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