<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erreur = "";

// Gérer le flash message (ex: après réinitialisation du mot de passe)
$flash = getFlash();

// === Renvoi du lien d'activation (depuis le lien "Renvoyer l'email") ===
if (isset($_GET['resend_verification']) && $_GET['resend_verification'] === '1' && isset($_GET['email'])) {
    $resendEmail = cleanInput($_GET['email']);
    
    // Vérifier que l'utilisateur existe et n'est PAS vérifié
    $stmt = $conn->prepare("SELECT id, nom, prenom, nom_entreprise, role, email_verified, last_verification_sent_at FROM users WHERE email = ?");
    if (!$stmt) {
        error_log('Erreur préparation SQL resend verification : ' . $conn->error);
        $erreur = "Erreur serveur. Veuillez réessayer plus tard.";
    } else {
        $stmt->bind_param('s', $resendEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && $user['email_verified'] == 0) {
            // Vérifier le délai (60s)
            $canResend = true;
            if (!empty($user['last_verification_sent_at'])) {
                $lastSent = strtotime($user['last_verification_sent_at']);
                $diff = time() - $lastSent;
                if ($diff < 60) {
                    $canResend = false;
                    $wait = 60 - $diff;
                    $erreur = "Veuillez patienter $wait seconde(s) avant de renvoyer l'e-mail d'activation.";
                }
            }
            
            if ($canResend) {
                $userName = ($user['role'] === 'entreprise') ? $user['nom_entreprise'] : ($user['prenom'] . ' ' . $user['nom']);
                $token = generateSecureToken();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $now = date('Y-m-d H:i:s');
                
                // Mettre à jour le token
                $stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_expires = ?, last_verification_sent_at = ? WHERE id = ?");
                if (!$stmt) {
                    error_log('Erreur préparation SQL update token : ' . $conn->error);
                    $erreur = "Erreur serveur. Veuillez réessayer plus tard.";
                } else {
                    $stmt->bind_param('sssi', $token, $expiresAt, $now, $user['id']);
                    if (!$stmt->execute()) {
                        error_log('Erreur exécution SQL update token : ' . $stmt->error);
                        $erreur = "Erreur serveur. Veuillez réessayer plus tard.";
                    } else {
                        // Envoyer l'e-mail
                        $sent = sendVerificationEmail($resendEmail, $userName, $token);
                        
                        if ($sent) {
                            $erreur = ''; // Effacer l'erreur précédente
                            // Créer un message de succès
                            echo '<div class="success" style="max-width: 450px; margin: 1rem auto;"><i class="fa-solid fa-circle-check"></i> Un nouvel e-mail d\'activation a été envoyé. Vérifiez votre boîte de réception.</div>';
                        } else {
                            error_log('Échec envoi e-mail vérification à ' . $resendEmail);
                            $erreur = "Erreur lors de l'envoi de l'e-mail. Veuillez réessayer plus tard.";
                        }
                    }
                    $stmt->close();
                }
            }
        } else {
            $erreur = "Compte introuvable ou déjà vérifié.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    if (!empty($email) && !empty($password)) {
        
        $query = "SELECT id, nom, prenom, nom_entreprise, mot_de_passe, role, email_verified FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Vérification du mot de passe (hashed)
                if (password_verify($password, $user['mot_de_passe'])) {
                    
                    // Vérifier si l'e-mail est confirmé
                    if ((int)$user['email_verified'] === 0) {
                        $erreur = "Votre adresse e-mail n'a pas encore été vérifiée. 
                                   <a href='connexion.php?resend_verification=1&email=" . urlencode($email) . "' 
                                      style='color: var(--brand); font-weight: 600; text-decoration: underline;'>
                                      Renvoyer l'e-mail d'activation
                                   </a>";
                    } else {
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];

                        if ($user['role'] === 'entreprise') {
                            $_SESSION['entreprise_id'] = $user['id'];
                            unset($_SESSION['etudiant_id']);
                            $_SESSION['nom_affiche'] = $user['nom_entreprise'];
                        } else {
                            $_SESSION['etudiant_id'] = $user['id'];
                            unset($_SESSION['entreprise_id']);
                            $_SESSION['nom_affiche'] = $user['prenom'] . " " . $user['nom'];
                        }

                        // Redirection
                        if ($user['role'] === 'admin') {
                            header("Location: admin_validation.php");
                            exit();
                        } else {
                            header("Location: index.php");
                            exit();
                        }
                    }
                } else {
                    $erreur = "Mot de passe incorrect.";
                }
            } else {
                $erreur = "Aucun compte trouvé avec cet e-mail.";
            }
            $stmt->close();
        } else {
            error_log('SQL prepare failed in connexion.php: ' . $conn->error);
            $erreur = "Erreur serveur. Réessayez plus tard.";
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<main class="container" style="max-width: 450px; margin-top: 5rem;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Connexion</h2>
        
<?php if (!empty($erreur)): ?>
            <div class="error" style="background: #fce8e6; color: #c5221f; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= $erreur ?>
            </div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
            <div style="margin-bottom: 1rem;">
                <label for="email">Adresse Email</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 0.7rem; margin-top: 0.3rem;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 0.7rem; margin-top: 0.3rem;">
            </div>
            
            <button type="submit" class="btn" style="width: 100%; justify-content: center; padding: 0.8rem;">Se connecter</button>
        </form>

        <p style="text-align: center; margin-top: 0.8rem; font-size: 0.85rem;">
            <a href="mdp_oublie.php" style="color: var(--muted); text-decoration: underline;">
                <i class="fa-solid fa-key"></i> Mot de passe oublié ?
            </a>
        </p>
        
        <p style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem;">
            Vous n'avez pas de compte ? <a href="inscription.php">Inscrivez-vous</a>
        </p>
    </div>
</main>

</body>
</html>