<?php
// ============================================
// FICHIER : includes/functions.php
// RÔLE : Fonctions utilitaires
// ============================================

// --- Nettoyage des entrées ---
function nettoyer($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// --- Génération du code bénéficiaire ---
function genererCodeBeneficiaire($pdo) {
    $annee = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM beneficiaries WHERE code LIKE 'SIT-{$annee}-%'");
    $count = $stmt->fetchColumn() + 1;
    return 'SIT-' . $annee . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// --- Formatage de date ---
function formaterDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') return '-';
    $dt = new DateTime($date);
    return $dt->format($format);
}

// --- Tronquer un texte ---
function tronquer($texte, $longueur = 100) {
    if (strlen($texte) <= $longueur) return $texte;
    return substr($texte, 0, $longueur) . '...';
}

// --- Upload sécurisé d'image ---
function uploaderImage($fichier, $dossier, $prefixe = '') {
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
    }

    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $extensionsAutorisees)) {
        return ['success' => false, 'message' => 'Format non autorisé (JPG, PNG, GIF uniquement)'];
    }

    if ($fichier['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (max 2 Mo)'];
    }

    $nouveauNom = $prefixe . uniqid() . '.' . $extension;
    $chemin = UPLOAD_PATH . $dossier . '/' . $nouveauNom;

    if (!is_dir(UPLOAD_PATH . $dossier)) {
        mkdir(UPLOAD_PATH . $dossier, 0777, true);
    }

    if (move_uploaded_file($fichier['tmp_name'], $chemin)) {
        return ['success' => true, 'nom' => $nouveauNom, 'chemin' => $dossier . '/' . $nouveauNom];
    }

    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
}

// --- Récupérer le nom complet d'un statut ---
function getStatusLabel($status, $type = 'beneficiary') {
    $labels = [
        'beneficiary' => [
            'Active' => 'Actif',
            'Inactive' => 'Inactif',
            'Graduated' => 'Diplômé'
        ],
        'enrollment' => [
            'Enrolled' => 'Inscrit',
            'In Progress' => 'En cours',
            'Completed' => 'Terminé',
            'Dropped Out' => 'Abandonné'
        ]
    ];
    return $labels[$type][$status] ?? $status;
}

// --- Récupérer le nom complet d'un genre ---
function getGenreLabel($genre) {
    return $genre === 'F' ? 'Féminin' : 'Masculin';
}

// --- Redirection sécurisée ---
function rediriger($url) {
    // Si BASE_URL est définie, l'utiliser, sinon utiliser le chemin relatif
    if (defined('BASE_URL')) {
        header('Location: ' . BASE_URL . $url);
    } else {
        header('Location: ' . $url);
    }
    exit;
}

// --- Message flash ---
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// --- Fonction de debug (à désactiver en production) ---
function debug($data) {
    echo '<pre style="background:#f4f4f4;padding:15px;border:1px solid #ddd;border-radius:5px;margin:10px 0;">';
    print_r($data);
    echo '</pre>';
}
?>