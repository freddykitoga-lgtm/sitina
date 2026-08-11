<?php
// ============================================
// FICHIER : modules/sessions/edit.php
// RÔLE : Modifier une session
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

$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$id]);
$session = $stmt->fetch();

if (!$session) {
    setFlash('danger', 'Session non trouvée.');
    rediriger('index.php');
}

// Récupérer les formations
$stmt = $pdo->query("SELECT id, name FROM trainings ORDER BY name");
$trainings = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_id = isset($_POST['training_id']) ? (int)$_POST['training_id'] : 0;
    $session_code = isset($_POST['session_code']) ? strtoupper(nettoyer($_POST['session_code'])) : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'Planning';
    $location = isset($_POST['location']) ? nettoyer($_POST['location']) : '';
    $max_participants = isset($_POST['max_participants']) ? (int)$_POST['max_participants'] : 30;

    if ($training_id <= 0 || empty($session_code) || empty($start_date) || empty($end_date)) {
        $error = 'Veuillez remplir tous les champs obligatoires (*).';
    } elseif ($start_date > $end_date) {
        $error = 'La date de fin doit être postérieure à la date de début.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE sessions SET
                    training_id = ?, session_code = ?, start_date = ?, end_date = ?,
                    status = ?, location = ?, max_participants = ?
                WHERE id = ?
            ");
            $stmt->execute([$training_id, $session_code, $start_date, $end_date, $status, $location, $max_participants, $id]);
            
            setFlash('success', 'Session modifiée avec succès !');
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
    <title>Modifier session - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-edit"></i> Modifier la session</h4>
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
                        <label class="form-label">Formation *</label>
                        <select name="training_id" class="form-select" required>
                            <option value="">Sélectionner une formation</option>
                            <?php foreach ($trainings as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $session['training_id'] == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo $t['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Code session *</label>
                        <input type="text" name="session_code" class="form-control" 
                               value="<?php echo htmlspecialchars($session['session_code']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de début *</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $session['start_date']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de fin *</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $session['end_date']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Capacité maximale</label>
                        <input type="number" name="max_participants" class="form-control" 
                               value="<?php echo $session['max_participants']; ?>" min="1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?php echo htmlspecialchars($session['location']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="Planning" <?php echo $session['status'] === 'Planning' ? 'selected' : ''; ?>>Planification</option>
                            <option value="En cours" <?php echo $session['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="Terminé" <?php echo $session['status'] === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="Annulé" <?php echo $session['status'] === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                        </select>
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