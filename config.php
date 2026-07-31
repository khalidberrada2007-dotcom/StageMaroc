<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('MYSQL_ADDON_HOST') ?: 'localhost';
$user = getenv('MYSQL_ADDON_USER') ?: 'root';
$pass = getenv('MYSQL_ADDON_PASSWORD') ?: 'Khalid@Mysql2026';
$db   = getenv('MYSQL_ADDON_DB') ?: 'site_stages';
$port = getenv('MYSQL_ADDON_PORT') ?: 3306;

/* Connexion */
$conn = new mysqli($host, $user, $pass, $db, $port);

/* Vérifier la connexion */
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

/* Encodage UTF-8 */
$conn->set_charset("utf8mb4");

/* Fuseau horaire */
date_default_timezone_set("Africa/Casablanca");
?>