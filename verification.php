<?php
/**
 * Vérification d'adresse e-mail par lien d'activation (token sécurisé)
 * 
 * Reçoit un token dans l'URL (GET), vérifie sa validité, active le compte.
 * Le token est stocké dans la table users (verification_token, verification_expires).
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Lien de vérification invalide. Aucun token fourni.';
} else {
    // Rechercher un utilisateur avec ce token non expiré
    $stmt = $conn->prepare("SELECT id, email, verification_expires, email_verified FROM users WHERE verification_token = ? AND email_verified = 0 LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = 'Lien de vérification invalide ou déjà utilisé.';
    } elseif (empty($user['verification_expires']) || strtotime($user['verification_expires']) < time()) {
        // Token expiré
        $error = 'Ce lien de vérification a expiré. Veuillez vous connecter pour renvoyer un nouveau lien.';
    } else {
        // ✅ Token valide : activer le compte
        $userId = $user['id'];
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL, verification_attempts = 0 WHERE id = ? AND email_verified = 0");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $success = 'Votre adresse e-mail a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.';
        } else {
            $error = 'Une erreur est survenue. Veuillez réessayer ou contacter le support.';
        }
    }
}

$page_actuelle = basename($_SERVER['PHP_SELF']);
function nav_active($page, $page_actuelle) {
    return $page === $page_actuelle ? ' class="active" aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l'adresse e-mail — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<main class="container" style="max-width: 520px; margin-top: 5rem;">
    <div class="card" style="text-align: center; padding: 3rem 2rem;">
        
        <?php if (!empty($success)): ?>
            <div style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2 style="color: var(--success); margin-bottom: 0.8rem;">Email vérifié !</h2>
            <div class="success" style="text-align: left;">
                <i class="fa-solid fa-circle-check"></i> <?= e($success) ?>
            </div>
            <a href="connexion.php" class="btn" style="margin-top: 1.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </a>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="font-size: 3rem; color: var(--terracotta); margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h2 style="color: var(--terracotta); margin-bottom: 0.8rem;">Échec de la vérification</h2>
            <div class="error" style="text-align: left;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="connexion.php" class="btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Se connecter
                </a>
                <a href="connexion.php?resend_verification=1" class="btn btn-gold">
                    <i class="fa-solid fa-envelope"></i> Renvoyer l'email
                </a>
            </div>
        <?php endif; ?>

        <p style="margin-top: 1.5rem; font-size: 0.85rem; color: var(--muted);">
            <a href="index.php" style="color: var(--brand);">← Retour à l'accueil</a>
        </p>
    </div>
</main>

</body>
</html>
