<?php
/**
 * Étape 1 — Mot de passe oublié : saisie de l'adresse e-mail
 * 
 * Vérifie que l'e-mail existe, génère un code à 6 chiffres,
 * le stocke en session (mode démo — e-mail désactivé) et
 * redirige vers la page de vérification du code.
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $email = cleanInput($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Veuillez saisir votre adresse e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (!emailExists($conn, $email)) {
        // Ne pas révéler si l'email existe ou pas (sécurité)
        $success = 'Si cette adresse e-mail est associée à un compte, vous allez recevoir un code de réinitialisation.';
    } else {
        // Vérifier le délai minimum entre deux envois (60s)
        $stmt = $conn->prepare("SELECT last_sent_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $lastRow = $result->fetch_assoc();
        $stmt->close();

        if ($lastRow && $lastRow['last_sent_at']) {
            $lastSent = strtotime($lastRow['last_sent_at']);
            $diff = time() - $lastSent;
            if ($diff < 60) {
                $wait = 60 - $diff;
                $error = "Veuillez patienter $wait seconde(s) avant de renvoyer un code.";
            }
        }

        if (empty($error)) {
            // Générer un code à 6 chiffres
            $code = generateNumericCode(6);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $now = date('Y-m-d H:i:s');

            // Invalider les anciens codes pour cet email
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();

            // Insérer le nouveau code
            $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expires_at, last_sent_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $email, $code, $expiresAt, $now);
            $stmt->execute();
            $stmt->close();

            // Mode démo (envoi d'e-mail SMTP désactivé) :
            // on stocke le code en session pour l'afficher sur la page suivante.
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_demo_code'] = $code;
            header('Location: verifier_code.php');
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
    <title>Mot de passe oublié — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        .reset-step { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .reset-step span { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; }
        .reset-step .active { background: var(--brand); color: #fff; }
        .reset-step .inactive { background: var(--line); color: var(--muted); }
        .reset-step .line { width: 40px; height: 1px; align-self: center; background: var(--line); }
    </style>
</head>
<body>

<main class="container" style="max-width: 480px; margin-top: 4rem;">
    <div class="card" style="padding: 2rem;">
        <div class="reset-step">
            <span class="active">1</span>
            <span class="line"></span>
            <span class="inactive">2</span>
            <span class="line"></span>
            <span class="inactive">3</span>
        </div>

        <h2 style="text-align: center; margin-bottom: 0.5rem; font-size: 1.4rem;">Mot de passe oublié</h2>
        <p style="text-align: center; color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Saisissez votre adresse e-mail pour recevoir un code de réinitialisation.
        </p>

        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">
                <i class="fa-solid fa-circle-check"></i> <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="mdp_oublie.php">
            <div style="margin-bottom: 1.5rem;">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" placeholder="vous@exemple.com" required
                       value="<?= e($_POST['email'] ?? '') ?>" style="width: 100%; padding: 0.8rem; margin-top: 0.3rem;">
            </div>

            <button type="submit" name="submit" class="btn" style="width: 100%; justify-content: center; padding: 0.8rem;">
                <i class="fa-solid fa-paper-plane"></i> Envoyer le code
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
