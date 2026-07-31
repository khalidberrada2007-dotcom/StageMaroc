<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('MYSQL_ADDON_HOST') ?: 'localhost';
$user = getenv('MYSQL_ADDON_USER') ?: 'root';
$pass = getenv('MYSQL_ADDON_PASSWORD') ?: '';
$db   = getenv('MYSQL_ADDON_DB') ?: 'stagemaroc';
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

/* Auto-création de la table candidatures (si elle n'existe pas encore) */
$conn->query("CREATE TABLE IF NOT EXISTS candidatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offre_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    statut ENUM('en_attente','acceptee','refusee') NOT NULL DEFAULT 'en_attente',
    date_candidature DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_candidature (offre_id, etudiant_id),
    KEY idx_offre (offre_id),
    KEY idx_etudiant (etudiant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>