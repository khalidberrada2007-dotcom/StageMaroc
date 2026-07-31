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

/* ---------------------------------------------------------------------
 * Migration auto-réparatrice pour la table `candidatures`.
 *
 * Problème : si la table existait déjà avec un ANCIEN schéma (par ex.
 * une colonne `user_id` au lieu de `etudiant_id`), la commande
 * `CREATE TABLE IF NOT EXISTS` ci-dessus ne modifie PAS une table
 * existante. Les requêtes de profil.php / details_offre.php /
 * candidatures.php utilisant `etudiant_id` échouent alors avec
 * "Unknown column 'c.etudiant_id'".
 *
 * Correctif : on vérifie les colonnes réelles de la table et on aligne
 * le schéma automatiquement (renommage de `user_id` si présent, sinon
 * ajout de `etudiant_id`), puis on garantit l'index `idx_etudiant`.
 * --------------------------------------------------------------------- */
try {
    $colsCandidatures = $conn->query("SHOW COLUMNS FROM candidatures");
    if ($colsCandidatures !== false) {
        $hasEtudiantId = false;
        $hasUserId     = false;
        while ($colC = $colsCandidatures->fetch_assoc()) {
            if ($colC['Field'] === 'etudiant_id') { $hasEtudiantId = true; }
            if ($colC['Field'] === 'user_id')     { $hasUserId     = true; }
        }

        if (!$hasEtudiantId) {
            if ($hasUserId) {
                // Ancien schéma : renommer user_id -> etudiant_id (conserve les données)
                $conn->query("ALTER TABLE candidatures CHANGE COLUMN user_id etudiant_id INT NOT NULL");
            } else {
                // Colonne absente : l'ajouter
                $conn->query("ALTER TABLE candidatures ADD COLUMN etudiant_id INT NOT NULL AFTER id");
            }
        }

        // Garantir l'index sur etudiant_id
        $idxEtudiant = $conn->query("SHOW INDEX FROM candidatures WHERE Key_name = 'idx_etudiant'");
        if ($idxEtudiant === false || $idxEtudiant->num_rows === 0) {
            $conn->query("ALTER TABLE candidatures ADD INDEX idx_etudiant (etudiant_id)");
        }
    }
} catch (Exception $e) {
    error_log('Migration candidatures ignorée : ' . $e->getMessage());
}
?>