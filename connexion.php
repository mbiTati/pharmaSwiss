<?php
// =============================================================================
// PHARMASWISS - connexion.php
// Gestion centralisée de la connexion à MySQL
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmaswiss');
define('DB_CHARSET', 'utf8mb4');

/**
 * Tente une connexion PDO avec les identifiants fournis.
 */
function tenter_connexion_mysql($username, $password) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère une connexion PDO à partir des identifiants en session.
 */
function get_pdo() {
    if (!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])) {
        header('Location: login.php?msg=session_expiree');
        exit;
    }
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            $_SESSION['db_user'],
            $_SESSION['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        session_destroy();
        header('Location: login.php?msg=erreur_connexion');
        exit;
    }
}

function exiger_authentification() {
    if (!isset($_SESSION['db_user'])) {
        header('Location: login.php');
        exit;
    }
}

function get_role_utilisateur() {
    if (!isset($_SESSION['db_user'])) return 'inconnu';
    $user = strtolower($_SESSION['db_user']);
    if (strpos($user, 'administrateur') !== false || $user === 'root') return 'administrateur';
    if (strpos($user, 'titulaire') !== false) return 'pharmacien_titulaire';
    if (strpos($user, 'assistant') !== false) return 'assistant';
    return 'utilisateur';
}

function h($texte) {
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}
