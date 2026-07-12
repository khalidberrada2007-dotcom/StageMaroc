<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($user_id === 0) {
    header("Location: connexion.php");
    exit();
}

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

    <div class="card reveal is-visible" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <?php if ($_SESSION['role'] === 'etudiant'): ?>
            <div>
                <h3 style="color: var(--brand); font-size: 1.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-user-graduate"></i> Informations Personnelles</h3>
                <p><b>Prénom :</b> <?= htmlspecialchars($user['prenom']) ?></p>
                <p><b>Nom :</b> <?= htmlspecialchars($user['nom']) ?></p>
            </div>
            <hr style="border: 0; height: 1px; background: var(--line);">
            <div>
                <p><b><i class="fa-solid fa-file-pdf" style="color: var(--terracotta);"></i> Mon CV :</b> 
                    <?php if(!empty($user['cv'])): ?>
                        <a href="<?= htmlspecialchars($user['cv']) ?>" target="_blank" class="badge" style="background: var(--brand-light); color: var(--brand-600); text-decoration: underline;">Voir le CV PDF</a>
                    <?php else: ?>
                        <span style="color: var(--muted); font-style: italic;">Aucun CV téléversé</span>
                    <?php endif; ?>
                </p>
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