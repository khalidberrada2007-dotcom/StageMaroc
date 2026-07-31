<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($user_id === 0) {
    header("Location: connexion.php");
    exit();
}

/* ---------------------------------------------------------------------
 * Gestion du CV (étudiant) : téléverser / remplacer / supprimer
 * Le CV reste facultatif lors de l'inscription ; l'étudiant peut le
 * gérer à tout moment depuis son profil.
 * --------------------------------------------------------------------- */
if ($role === 'etudiant' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Suppression du CV ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_cv') {
        $stmt = $conn->prepare("SELECT cv FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $old_cv = $row['cv'];
            if (!empty($old_cv) && file_exists($old_cv)) {
                @unlink($old_cv);
            }
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET cv = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        redirectWithFlash('profil.php', 'Votre CV a été supprimé.', 'success');
    }

    // --- Téléversement / remplacement du CV ---
    if (isset($_POST['action']) && $_POST['action'] === 'upload_cv') {

        if (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
            redirectWithFlash('profil.php', 'Aucun fichier sélectionné.', 'error');
        }

        if ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            redirectWithFlash('profil.php', 'Une erreur est survenue lors du téléversement.', 'error');
        }

        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            redirectWithFlash('profil.php', 'Le CV doit être un fichier PDF.', 'error');
        }

        if ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
            redirectWithFlash('profil.php', 'Le CV ne doit pas dépasser 5 Mo.', 'error');
        }

        // Supprimer l'ancien CV avant d'enregistrer le nouveau
        $stmt = $conn->prepare("SELECT cv FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $old_cv = $row['cv'];
            if (!empty($old_cv) && file_exists($old_cv)) {
                @unlink($old_cv);
            }
        }
        $stmt->close();

        if (!is_dir('uploads/cv')) {
            mkdir('uploads/cv', 0755, true);
        }

        $filename = uniqid('cv_') . '.pdf';
        $destination = 'uploads/cv/' . $filename;

        if (move_uploaded_file($_FILES['cv']['tmp_name'], $destination)) {
            $stmt = $conn->prepare("UPDATE users SET cv = ? WHERE id = ?");
            $stmt->bind_param("si", $destination, $user_id);
            $stmt->execute();
            $stmt->close();

            redirectWithFlash('profil.php', 'Votre CV a bien été téléversé.', 'success');
        }

        redirectWithFlash('profil.php', 'Impossible d\'enregistrer le fichier. Réessayez.', 'error');
    }
}

// Récupérer un éventuel message flash
$flash = getFlash();

// Préparer la requête du connecté
$query = "SELECT * FROM users WHERE id = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        die("Utilisateur introuvable.");
    }
    $stmt->close();
} else {
    die("Erreur SQL lors de la préparation : " . $conn->error);
}

$page_actuelle = basename($_SERVER['PHP_SELF']);
function nav_active($page, $page_actuelle){
    return $page === $page_actuelle ? ' class="active" aria-current="page"' : '';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Profil — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<a href="#contenu" class="skip-link">Aller au contenu</a>

<header class="site-header">
    <div class="logo">
        <span class="logo-mark"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1>StageMaroc</h1>
        <span class="logo-tag">Étudiants × Entreprises</span>
    </div>
    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Ouvrir le menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="primary-nav">
        <a href="index.php"<?= nav_active('index.php', $page_actuelle) ?>>Accueil</a>
        <?php if ($_SESSION['role'] === 'entreprise'): ?>
            <a href="poster_offre.php"<?= nav_active('poster_offre.php', $page_actuelle) ?>>Publier une offre</a>
            <a href="candidatures.php"<?= nav_active('candidatures.php', $page_actuelle) ?>>Candidatures</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin_validation.php"<?= nav_active('admin_validation.php', $page_actuelle) ?>>Validation Offres</a>
        <?php endif; ?>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>><i class="fa-solid fa-user"></i> Mon Profil</a>
        <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
    </nav>
</header>

<main id="contenu" class="container" style="padding-top: 3.5rem; max-width: 700px;">
    <h2>Mon Profil (Espace <?= ucfirst($_SESSION['role']) ?>)</h2>

    <?php if ($flash !== null): ?>
        <div class="<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="max-width: 700px;">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="card reveal is-visible" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <?php if ($_SESSION['role'] === 'etudiant'): ?>
            <div>
                <h3 style="color: var(--brand); font-size: 1.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-user-graduate"></i> Informations Personnelles</h3>
                <p><b>Prénom :</b> <?= htmlspecialchars($user['prenom']) ?></p>
                <p><b>Nom :</b> <?= htmlspecialchars($user['nom']) ?></p>
            </div>
            <hr style="border: 0; height: 1px; background: var(--line);">
            <div>
                <h3 style="color: var(--brand); font-size: 1.1rem; margin-bottom: .8rem;"><i class="fa-solid fa-file-pdf" style="color: var(--terracotta);"></i> Mon CV</h3>

                <?php if(!empty($user['cv'])): ?>
                    <p>
                        <i class="fa-solid fa-file-pdf" style="color: var(--terracotta);"></i>
                        <b>CV actuel :</b>
                        <a href="<?= htmlspecialchars($user['cv']) ?>" target="_blank" class="badge" style="text-decoration: underline;">Voir le CV PDF</a>
                    </p>
                <?php else: ?>
                    <p style="color: var(--muted); font-style: italic; margin-bottom: 1rem;">
                        <i class="fa-solid fa-circle-info"></i> Vous n'avez pas encore téléversé de CV.
                        Vous pouvez l'ajouter à tout moment (fichier PDF, 5 Mo maximum).
                    </p>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" action="profil.php" style="display: flex; flex-direction: column; gap: .8rem; margin-top: .5rem;">
                    <input type="hidden" name="action" value="upload_cv">
                    <label for="cv"><?= !empty($user['cv']) ? 'Remplacer mon CV' : 'Choisir un fichier PDF' ?></label>
                    <div style="display: flex; gap: .6rem; flex-wrap: wrap; align-items: center;">
                        <input id="cv" type="file" name="cv" accept="application/pdf" style="flex: 1; min-width: 220px;" required>
                        <button type="submit" class="btn btn-sm" style="margin-top: 0; background: var(--gold); color: var(--brand-dark);">
                            <i class="fa-solid fa-upload"></i> <?= !empty($user['cv']) ? 'Remplacer' : 'Téléverser mon CV' ?>
                        </button>
                    </div>
                </form>

                <?php if(!empty($user['cv'])): ?>
                    <form method="POST" action="profil.php" style="margin-top: .75rem;">
                        <input type="hidden" name="action" value="delete_cv">
                        <button type="submit" class="btn btn-sm btn-danger confirm-action" data-msg="Êtes-vous sûr de vouloir supprimer votre CV ?">
                            <i class="fa-solid fa-trash"></i> Supprimer mon CV
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <div>
                <h3 style="color: var(--brand); font-size: 1.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-user-shield"></i> Compte Administrateur</h3>
                <p><b>Statut :</b> Administrateur Principal</p>
                <p><b>Accès :</b> Total (Modération & Validation)</p>
            </div>

        <?php elseif ($_SESSION['role'] === 'entreprise'): ?>
            <div>
                <h3 style="color: var(--brand); font-size: 1.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-building"></i> Détails de l'Entreprise</h3>
                <?php if(!empty($user['logo'])): ?>
                    <img src="<?= htmlspecialchars($user['logo']) ?>" alt="Logo" style="max-width: 120px; border-radius: var(--radius-sm); margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
                <?php endif; ?>
                <p><b>Raison Sociale :</b> <?= htmlspecialchars($user['nom_entreprise']) ?></p>
                <p><b>Responsable de Contact :</b> <?= htmlspecialchars($user['nom']) ?></p>
                <p><b>Secteur d'activité :</b> <span class="badge"><?= htmlspecialchars($user['secteur']) ?></span></p>
                <p style="margin-top: 0.8rem;"><b>Description :</b></p>
                <p style="background: var(--bg); padding: 1rem; border-radius: var(--radius-sm); color: var(--ink); font-style: italic;">
                    <?= nl2br(htmlspecialchars($user['description'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <hr style="border: 0; height: 1px; background: var(--line);">

        <div>
            <h3 style="color: var(--brand-dark); font-size: 1.2rem; margin-bottom: 0.8rem;"><i class="fa-solid fa-address-book"></i> Coordonnées & Contact</h3>
            <p><i class="fa-solid fa-envelope"></i> <b>Email :</b> <?= htmlspecialchars($user['email']) ?></p>
            <p><i class="fa-solid fa-phone"></i> <b>Téléphone :</b> <?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : 'Non renseigné' ?></p>
            <p><i class="fa-solid fa-location-dot"></i> <b>Ville :</b> <?= htmlspecialchars($user['ville']) ?></p>
        </div>

    </div>
</main>

<footer>
    <span class="footer-mark">StageMaroc</span>
    <p>© <?= date('Y'); ?> StageMaroc — Projet réalisé dans le cadre d'une formation en développement digital</p>
</footer>
<script src="app.js"></script>
<div id="custom-confirm-modal" class="modal-overlay">
    <div class="modal-card">
        <h3><i class="fa-solid fa-circle-question" style="color: var(--brand);"></i> Confirmation</h3>
        <p id="modal-message">Voulez-vous vraiment effectuer cette action ?</p>
        <div class="modal-actions">
            <button id="modal-btn-no" class="btn btn-secondary">Non</button>
            <button id="modal-btn-yes" class="btn">Oui</button>
        </div>
    </div>
</div>
</body>
</html>