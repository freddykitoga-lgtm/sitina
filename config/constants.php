<?php
// ============================================
// FICHIER : config/constants.php
// RÔLE : Constantes globales du projet
// ============================================

// --- Chemins physiques (serveur) ---
define('ROOT_PATH', dirname(__DIR__) . '/');
define('MODULES_PATH', ROOT_PATH . 'modules/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// --- URLs (navigateur) ---
define('BASE_URL', 'http://localhost/sitina/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// --- Paramètres généraux ---
define('APP_NAME', 'SITINA - Gestion');
define('APP_VERSION', '1.0.0');
define('SITE_NAME', 'SITINA ONG');

// --- Limites ---
define('MAX_FILE_SIZE', 2097152); // 2 Mo
define('PAGINATION_LIMIT', 20);

// --- Fuseau horaire ---
date_default_timezone_set('Africa/Lubumbashi');
?>