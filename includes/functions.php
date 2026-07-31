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
 * Génère un token sécurisé hexadécimal (SHA-256).
 *
 * @param int $bytes Nombre d'octets aléatoires (défaut: 32 → 64 caractères hex)
 * @return string Token hexadécimal
 */
function generateSecureToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
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

/**
 * Vérifie le délai minimum entre deux envois d'e-mails (anti-spam).
 * Stocke le timestamp du dernier envoi en session.
 *
 * @param string $key Clé de session unique (ex: 'last_reset_email', 'last_verification_email')
 * @param int $delaySeconds Délai minimum en secondes (défaut: 60)
 * @return bool True si l'envoi est autorisé
 */
function canResendEmail(string $key, int $delaySeconds = 60): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $last = $_SESSION[$key] ?? 0;
    return (time() - $last) >= $delaySeconds;
}

/**
 * Enregistre l'horodatage du dernier envoi d'e-mail.
 *
 * @param string $key Clé de session
 */
function markEmailSent(string $key): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION[$key] = time();
}

/**
 * Génère le message d'erreur pour une tentative de renvoi trop rapide.
 *
 * @param int $delaySeconds Délai en secondes
 * @return string Message d'erreur
 */
function getResendCooldownMessage(int $delaySeconds = 60): string
{
    return "Veuillez patienter $delaySeconds secondes avant de renvoyer un e-mail.";
}

