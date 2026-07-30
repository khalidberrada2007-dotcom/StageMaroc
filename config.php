<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Créez une copie de config.example.php nommée config.local.php
// et définissez-y vos identifiants de connexion à la base de données.
$localConfig = __DIR__ . '/config.local.php';
if (!file_exists($localConfig)) {
    die("Missing local configuration. Copy config.example.php to config.local.php and set database credentials.");
}

include $localConfig;

/* Port par défaut si non défini dans config.local.php */
if (!isset($port)) {
    $port = 3306;
}

/* Vérification des variables attendues */
if (!isset($host, $user, $pass, $db)) {
    die("Local configuration invalid: please set \$host, \$user, \$pass and \$db in config.local.php");
}

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