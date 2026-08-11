<?php
// ============================================
// FICHIER : modules/users/index.php
// RÔLE : Liste des utilisateurs
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

// Récupérer les utilisateurs
$stmt = $pdo->query("
    SELECT id, username, email, full_name, role, is_active, last_login, created_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - SITINA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-role-admin { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-role-formateur { background: #0d6efd; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-role-me { background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-role-lecture { background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-active { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-status-inactive { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-users-cog"></i> Gestion des utilisateurs</h4>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel utilisateur
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <span class="badge-role-<?php echo $user['role']; ?>">
                                            <?php 
                                                $roles = [
                                                    'admin' => 'Administrateur',
                                                    'formateur' => 'Formateur',
                                                    'me' => 'M&E',
                                                    'lecture' => 'Lecture seule'
                                                ];
                                                echo $roles[$user['role']] ?? $user['role'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger btn-delete-confirm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center my-4">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Aucun utilisateur trouvé.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>