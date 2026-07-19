<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    if (!empty($email) && !empty($password)) {
        
        $query = "SELECT id, nom, prenom, nom_entreprise, mot_de_passe, role FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Vérification du mot de passe (hashed)
                if (password_verify($password, $user['mot_de_passe'])) {
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
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erreur) ?>
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
        
        <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem;">
            Vous n'avez pas de compte ? <a href="inscription.php">Inscrivez-vous</a>
        </p>
    </div>
</main>

</body>
</html>