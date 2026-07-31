<?php
/**
 * Fonctions utilitaires partagées — StageMaroc
 * Regroupe les fonctions réutilisables pour éviter la duplication de code.
 */

/**
 * Génère un code aléatoire sécurisé à N chiffres.
 *
 * @param int $length Nombre de chiffres (défaut: 6)
 * @return string Code numérique
 */
function generateNumericCode(int $length = 6): string
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= random_int(0, 9);
    }
    return $code;
}

/**
 * Vérifie que l'utilisateur est connecté. Redirige vers connexion.php si non connecté.
 */
function requireLogin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: connexion.php');
        exit();
    }
}

/**
 * Vérifie que l'utilisateur a le rôle spécifié. Redirige si rôle incorrect.
 *
 * @param string $role Role attendu ('admin', 'entreprise', 'etudiant')
 */
function requireRole(string $role): void
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Redirige vers une URL avec un message flash en session.
 *
 * @param string $url     URL de destination
 * @param string $message Message à afficher
 * @param string $type    Type : 'success' ou 'error'
 */
function redirectWithFlash(string $url, string $message, string $type = 'success'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . $url);
    exit();
}

/**
 * Récupère et efface un message flash de la session.
 *
 * @return array|null ['message' => string, 'type' => string] ou null si aucun flash
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Échappe les sorties HTML (encodage UTF-8).
 *
 * @param string|null $value Valeur à échapper
 * @return string Valeur échappée
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si une adresse e-mail existe déjà dans la base.
 *
 * @param mysqli $conn Connexion MySQLi
 * @param string $email Adresse e-mail à vérifier
 * @return bool True si l'email existe déjà
 */
function emailExists(mysqli $conn, string $email): bool
{
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Nettoie et valide une entrée utilisateur.
 *
 * @param string $input Valeur brute
 * @return string Valeur nettoyée
 */
function cleanInput(string $input): string
{
    return trim(strip_tags($input));
}

