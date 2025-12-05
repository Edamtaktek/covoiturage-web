<?php
/**
 * Fichier de Configuration Globale - CovoiturageApp
 * Contient les constantes, les fonctions utilitaires et les configurations
 */

// ================================================================
// CONSTANTES GLOBALES
// ================================================================

// URL de base de l'application
define('BASE_URL', 'http://localhost/covoiturage/');
define('SITE_NAME', 'Covoiturage');
define('SITE_DESCRIPTION', 'Plateforme de covoiturage en ligne');

// Informations de session
define('SESSION_TIMEOUT', 3600); // 1 heure
define('SESSION_NAME', 'covoiturage_session');

// Limites d'upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Pagination
define('ITEMS_PER_PAGE', 10);
define('RIDES_PER_PAGE', 12);

// ================================================================
// FONCTIONS D'AUTHENTIFICATION
// ================================================================

/**
 * Verifie si l'utilisateur est connecte
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifie si l'utilisateur est un administrateur
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Redirige si l'utilisateur n'est pas admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php?error=unauthorized');
        exit;
    }
}

/**
 * Recupere l'utilisateur actuel
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'is_admin' => $_SESSION['is_admin'] ?? 0,
        ];
    }
    return null;
}

/**
 * Hache un mot de passe avec BCRYPT
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifie un mot de passe
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genere un token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifie un token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ================================================================
// FONCTIONS DE SANITIZATION ET VALIDATION
// ================================================================

/**
 * Nettoie une chaine de caracteres
 */
function sanitize($string) {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valide une adresse email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un numero de telephone
 */
function validatePhone($phone) {
    // Format: +33612345678 ou 0612345678
    return preg_match('/^(?:(?:\+|00)33|0)[1-9](?:[0-9]{8})$/', preg_replace('/[\s.-]/', '', $phone));
}

/**
 * Valide une URL
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Valide un entier
 */
function validateInteger($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Valide un nombre (float)
 */
function validateFloat($value) {
    return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
}

/**
 * Echappe une chaine pour utilisation SQL
 * ATTENTION: Utiliser les prepared statements a la place!
 */
function escapeSql($string) {
    global $db;
    return $db->real_escape_string($string);
}

// ================================================================
// FONCTIONS DE FORMATAGE
// ================================================================

/**
 * Formate une date au format francais
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    if ($timestamp === false) return 'Date invalide';
    
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Formate une date courte
 */
function formatDateShort($date) {
    $timestamp = strtotime($date);
    if ($timestamp === false) return 'Date invalide';
    
    return date('d/m/Y', $timestamp);
}

/**
 * Formate une heure
 */
function formatTime($time) {
    $timestamp = strtotime($time);
    if ($timestamp === false) return 'Heure invalide';
    
    return date('H:i', $timestamp);
}

/**
 * Formate un prix
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' EUR';
}

/**
 * Formate une distance
 */
function formatDistance($distance) {
    return number_format($distance, 1, ',', ' ') . ' km';
}

/**
 * Formate une duree en minutes vers format lisible
 */
function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $mins . 'min';
    }
    return $mins . ' min';
}

/**
 * Calcule le temps ecoule depuis une date
 * Ex: "Il y a 2 heures"
 */
function timeAgo($date) {
    $timestamp = strtotime($date);
    $now = time();
    $difference = $now - $timestamp;
    
    if ($difference < 60) return 'A l\'instant';
    if ($difference < 3600) return 'Il y a ' . floor($difference / 60) . ' minute(s)';
    if ($difference < 86400) return 'Il y a ' . floor($difference / 3600) . ' heure(s)';
    if ($difference < 2592000) return 'Il y a ' . floor($difference / 86400) . ' jour(s)';
    
    return date('d/m/Y', $timestamp);
}

// ================================================================
// FONCTIONS DE CALCUL
// ================================================================

/**
 * Calcule la distance entre deux coordonnees GPS
 * Formule de Haversine
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    
    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lon = deg2rad($lon2 - $lon1);
    
    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lon / 2) * sin($delta_lon / 2);
    
    $c = 2 * asin(sqrt($a));
    
    return round($earth_radius * $c, 2);
}

/**
 * Calcule le prix total pour une reservation
 */
function calculateTotalPrice($price_per_seat, $seats) {
    return round($price_per_seat * $seats, 2);
}

/**
 * Calcule la duree entre deux dates
 */
function calculateDuration($departure, $arrival) {
    $dep_time = strtotime($departure);
    $arr_time = strtotime($arrival);
    
    if ($dep_time === false || $arr_time === false) return 0;
    
    return ceil(($arr_time - $dep_time) / 60); // en minutes
}

// ================================================================
// FONCTIONS DE PAGINATION
// ================================================================

/**
 * Calcule le nombre de pages
 */
function calculatePages($total_items, $items_per_page = ITEMS_PER_PAGE) {
    return ceil($total_items / $items_per_page);
}

/**
 * Calcule l'offset pour une pagination
 */
function calculateOffset($page, $items_per_page = ITEMS_PER_PAGE) {
    $page = max(1, intval($page));
    return ($page - 1) * $items_per_page;
}

/**
 * Valide un numero de page
 */
function validatePage($page, $total_pages) {
    $page = intval($page);
    return max(1, min($page, max(1, $total_pages)));
}

// ================================================================
// FONCTIONS DE REDIRECTION
// ================================================================

/**
 * Redirige vers une URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Redirige en arriere
 */
function redirectBack($default = BASE_URL) {
    $referer = $_SERVER['HTTP_REFERER'] ?? $default;
    header('Location: ' . $referer);
    exit;
}

// ================================================================
// FONCTIONS DE MESSAGES FLASH
// ================================================================

/**
 * Ajoute un message flash
 */
function setFlash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Recupere et vide les messages flash
 */
function getFlashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ================================================================
// FONCTIONS DE LOGGING
// ================================================================

/**
 * Enregistre un message dans les logs
 */
function logMessage($level, $message) {
    $log_file = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    
    // Creer le dossier logs s'il n'existe pas
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    error_log($log_entry, 3, $log_file);
}

/**
 * Log une erreur
 */
function logError($message) {
    logMessage('ERROR', $message);
}

/**
 * Log une info
 */
function logInfo($message) {
    logMessage('INFO', $message);
}

/**
 * Log un avertissement
 */
function logWarning($message) {
    logMessage('WARNING', $message);
}

// ================================================================
// FONCTIONS UTILITAIRES
// ================================================================

/**
 * Genere un slug a partir d'une chaine
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = preg_replace('~-+~', '-', $text);
    return strtolower(trim($text, '-'));
}

/**
 * Genere un UUID v4
 */
function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Genere un code aleatoire
 */
function generateCode($length = 8) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

/**
 * Verifie si une variable est vide (considerant aussi "0")
 */
function isEmpty($value) {
    return $value === null || $value === '' || (is_array($value) && count($value) === 0);
}

/**
 * Retourne la valeur par defaut si vide
 */
function defaultValue($value, $default = '') {
    return isEmpty($value) ? $default : $value;
}

/**
 * Affiche une valeur pour le debogage
 */
function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

/**
 * Affiche une valeur et arrête l'execution
 */
function dd($var) {
    dump($var);
    exit;
}

// ================================================================
// INITIALISATION DE SESSION
// ================================================================

// Demarrer la session si elle n'est pas deja lancee
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifier le timeout de session
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: ' . BASE_URL . 'pages/login.php?timeout=1');
        exit;
    }
}

// Mettre a jour le timestamp de derniere activite
$_SESSION['last_activity'] = time();

// ================================================================
// CONFIGURATION DU RAPPORT D'ERREURS
// ================================================================

// En production, cacher les erreurs
// En developpement, les afficher
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuration complete!
?>
