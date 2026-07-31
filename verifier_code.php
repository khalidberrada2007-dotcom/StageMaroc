<?php
/**
 * Étape 2 — Mot de passe oublié : vérification du code à 6 chiffres
 * 
 * L'utilisateur saisit le code reçu par e-mail.
 * Validation : code valide, non expiré (15 min), max 5 tentatives.
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

// Vérifier qu'on a bien un email en session (provenant de l'étape 1)
$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    header('Location: mdp_oublie.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');

    if (empty($code) || strlen($code) !== 6) {
        $error = 'Veuillez saisir un code valide à 6 chiffres.';
    } else {
        // Récupérer le dernier code non utilisé pour cet email
        $stmt = $conn->prepare("SELECT id, code, attempts, expires_at FROM password_resets WHERE email = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $resetRow = $result->fetch_assoc();
        $stmt->close();

        if (!$resetRow) {
            $error = 'Aucun code valide trouvé. Veuillez demander un nouveau code.';
        } elseif (strtotime($resetRow['expires_at']) < time()) {
            // Code expiré
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $resetRow['id']);
            $stmt->execute();
            $stmt->close();
            $error = 'Ce code a expiré. Veuillez demander un nouveau code.';
        } elseif ($resetRow['attempts'] >= 5) {
            // Trop de tentatives
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $resetRow['id']);
            $stmt->execute();
            $stmt->close();
            $error = 'Trop de tentatives échouées. Ce code a été invalidé. Veuillez demander un nouveau code.';
        } elseif ($code !== $resetRow['code']) {
            // Code incorrect : incrémenter les tentatives
            $newAttempts = $resetRow['attempts'] + 1;
            $remaining = 5 - $newAttempts;
            $stmt = $conn->prepare("UPDATE password_resets SET attempts = ? WHERE id = ?");
            $stmt->bind_param('ii', $newAttempts, $resetRow['id']);
            $stmt->execute();
            $stmt->close();

            if ($remaining <= 0) {
                $error = 'Code invalidé après 5 tentatives échouées. Veuillez demander un nouveau code.';
            } else {
                $error = "Code incorrect. Il vous reste $remaining tentative(s).";
            }
        } else {
            // Code valide ! Rediriger vers la page de réinitialisation
            $_SESSION['reset_code_valid'] = true;
            $_SESSION['reset_code_id'] = $resetRow['id'];
            header('Location: reinitialiser_mdp.php');
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
    <title>Vérifier le code — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        .reset-step { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .reset-step span { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; }
        .reset-step .active { background: var(--brand); color: #fff; }
        .reset-step .inactive { background: var(--line); color: var(--muted); }
        .reset-step .done { background: var(--success); color: #fff; }
        .reset-step .line { width: 40px; height: 1px; align-self: center; background: var(--line); }
        .reset-step .line.done { background: var(--success); }
        .code-input { text-align: center; }
        .code-input input { max-width: 220px; text-align: center; font-size: 1.8rem; letter-spacing: 8px; font-family: var(--font-mono); padding: 0.8rem; }
        .resend-link { text-align: center; margin-top: 1rem; font-size: 0.85rem; }
    </style>
</head>
<body>

<main class="container" style="max-width: 480px; margin-top: 4rem;">
    <div class="card" style="padding: 2rem;">
        <div class="reset-step">
            <span class="done">✓</span>
            <span class="line done"></span>
            <span class="active">2</span>
            <span class="line"></span>
            <span class="inactive">3</span>
        </div>

        <h2 style="text-align: center; margin-bottom: 0.5rem; font-size: 1.4rem;">Vérification</h2>
        <p style="text-align: center; color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Saisissez le code à 6 chiffres envoyé à <strong><?= e($email) ?></strong>
        </p>

        <?php if (!empty($_SESSION['reset_demo_code'])): ?>
            <div class="success" style="text-align: center; margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-info"></i> Mode démo (e-mail désactivé) : votre code est
                <strong style="font-size: 1.4rem; letter-spacing: 4px; font-family: monospace;">
                    <?= e($_SESSION['reset_demo_code']) ?>
                </strong>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="verifier_code.php" class="code-input">
            <div style="margin-bottom: 1.5rem;">
                <label for="code">Code de vérification</label>
                <input type="text" id="code" name="code" placeholder="000000" required
                       maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       style="width: 100%; padding: 0.8rem; margin-top: 0.3rem;">
            </div>

            <button type="submit" name="submit" class="btn" style="width: 100%; justify-content: center; padding: 0.8rem;">
                <i class="fa-solid fa-check"></i> Vérifier le code
            </button>
        </form>

        <div class="resend-link">
            <p style="color: var(--muted); font-size: 0.85rem;">
                Vous n'avez pas reçu le code ?
                <a href="mdp_oublie.php" style="color: var(--brand); font-weight: 600;">Renvoyer un nouveau code</a>
            </p>
        </div>

        <p style="text-align: center; margin-top: 0.8rem; font-size: 0.9rem;">
            <a href="connexion.php" style="color: var(--muted);">
                <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
            </a>
        </p>
    </div>
</main>

<script>
// Auto-submit quand 6 chiffres sont saisis (optionnel, UX améliorée)
(function() {
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
    }
})();
</script>

</body>
</html>
