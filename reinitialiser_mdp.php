<?php
/**
 * Étape 3 — Mot de passe oublié : réinitialisation du mot de passe
 * 
 * L'utilisateur peut définir un nouveau mot de passe après validation du code.
 * Le nouveau mot de passe est hashé avec password_hash() avant d'être enregistré.
 * Le code est invalidé après utilisation.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Vérifier que le code a été validé (provenant de l'étape 2)
if (!isset($_SESSION['reset_code_valid']) || $_SESSION['reset_code_valid'] !== true) {
    header('Location: mdp_oublie.php');
    exit();
}

$email = $_SESSION['reset_email'] ?? '';
$codeId = $_SESSION['reset_code_id'] ?? 0;

if (empty($email) || empty($codeId)) {
    header('Location: mdp_oublie.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier que le code est toujours valide (non utilisé, non expiré)
        $stmt = $conn->prepare("SELECT id, expires_at FROM password_resets WHERE id = ? AND used = 0");
        $stmt->bind_param('i', $codeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $resetRow = $result->fetch_assoc();
        $stmt->close();

        if (!$resetRow) {
            $error = 'Ce code a déjà été utilisé ou est invalide. Veuillez recommencer le processus.';
        } elseif (strtotime($resetRow['expires_at']) < time()) {
            $error = 'Ce code a expiré. Veuillez recommencer le processus.';
        } else {
            // Hacher le nouveau mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe dans la table users
            $stmt = $conn->prepare("UPDATE users SET mot_de_passe = ? WHERE email = ?");
            $stmt->bind_param('ss', $hashedPassword, $email);
            $stmt->execute();
            $stmt->close();

            // Invalider le code utilisé
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $codeId);
            $stmt->execute();
            $stmt->close();

            // Nettoyer la session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code_valid']);
            unset($_SESSION['reset_code_id']);
            unset($_SESSION['reset_demo_code']);

            // Rediriger vers la connexion avec un message de succès
            $_SESSION['flash'] = [
                'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
                'type' => 'success'
            ];
            header('Location: connexion.php');
            exit();
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
    <title>Nouveau mot de passe — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        .reset-step { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .reset-step span { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; }
        .reset-step .active { background: var(--brand); color: #fff; }
        .reset-step .done { background: var(--success); color: #fff; }
        .reset-step .line { width: 40px; height: 1px; align-self: center; background: var(--line); }
        .reset-step .line.done { background: var(--success); }
    </style>
</head>
<body>

<main class="container" style="max-width: 480px; margin-top: 4rem;">
    <div class="card" style="padding: 2rem;">
        <div class="reset-step">
            <span class="done">✓</span>
            <span class="line done"></span>
            <span class="done">✓</span>
            <span class="line done"></span>
            <span class="active">3</span>
        </div>

        <h2 style="text-align: center; margin-bottom: 0.5rem; font-size: 1.4rem;">Nouveau mot de passe</h2>
        <p style="text-align: center; color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Choisissez un nouveau mot de passe sécurisé pour votre compte.
        </p>

        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reinitialiser_mdp.php">
            <div style="margin-bottom: 1rem;">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" placeholder="6 caractères minimum" required
                       minlength="6" style="width: 100%; padding: 0.8rem; margin-top: 0.3rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required
                       minlength="6" style="width: 100%; padding: 0.8rem; margin-top: 0.3rem;">
            </div>

            <button type="submit" name="submit" class="btn" style="width: 100%; justify-content: center; padding: 0.8rem;">
                <i class="fa-solid fa-key"></i> Réinitialiser mon mot de passe
            </button>
        </form>

        <p style="text-align: center; margin-top: 1.2rem; font-size: 0.9rem;">
            <a href="connexion.php" style="color: var(--brand); font-weight: 600;">
                <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
            </a>
        </p>
    </div>
</main>

</body>
</html>
