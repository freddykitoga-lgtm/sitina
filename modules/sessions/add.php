<?php
// ============================================
// FICHIER : modules/sessions/add.php
// RÔLE : Ajouter une session de formation
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$error = '';

// Récupérer les formations
$stmt = $pdo->query("SELECT id, name FROM trainings ORDER BY name");
$trainings = $stmt->fetchAll();

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
            // Vérifier si le code existe déjà
            $stmt = $pdo->prepare("SELECT id FROM sessions WHERE session_code = ?");
            $stmt->execute([$session_code]);
            if ($stmt->fetch()) {
                $error = 'Ce code de session existe déjà.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (training_id, session_code, start_date, end_date, status, location, max_participants)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$training_id, $session_code, $start_date, $end_date, $status, $location, $max_participants]);
                
                setFlash('success', 'Session créée avec succès ! Code : ' . $session_code);
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la création : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle session - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-plus"></i> Créer une session</h4>
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
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Code session *</label>
                        <input type="text" name="session_code" class="form-control" placeholder="Ex: ALP-2026-01" required>
                        <small class="text-muted">Format: FORMATION-ANNEE-NUMERO (ex: ALP-2026-01)</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de début *</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date de fin *</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Capacité maximale</label>
                        <input type="number" name="max_participants" class="form-control" value="30" min="1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="location" class="form-control" placeholder="Ex: Centre SITINA - Kinshasa">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="Planning">Planification</option>
                            <option value="En cours">En cours</option>
                            <option value="Terminé">Terminé</option>
                            <option value="Annulé">Annulé</option>
                        </select>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer la session
                    </button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>