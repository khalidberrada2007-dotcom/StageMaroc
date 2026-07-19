<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Traitement de la validation ou du refus direct
if (isset($_GET['action']) && in_array($_GET['action'], ['valider', 'refuser']) && isset($_GET['id'])) {
    $id_offre = intval($_GET['id']);
    $nouveau_statut = $_GET['action'] === 'valider' ? 'valide' : 'refuse';

    $stmt = $conn->prepare("UPDATE offres SET statut = ? WHERE id = ?");
    $stmt->bind_param("si", $nouveau_statut, $id_offre);

    if ($stmt->execute()) {
        $param_succes = $_GET['action'] === 'valider' ? 'success=1' : 'refused=1';
        header("Location: admin_validation.php?" . $param_succes);
        exit();
    } else {
        die("Erreur lors du traitement : " . $conn->error);
    }
}

// 🛠️ REQUÊTE BLINDÉE :
$result = $conn->query("SELECT * FROM offres WHERE LOWER(statut) LIKE '%attente%' OR statut = '' OR statut IS NULL ORDER BY id DESC");

if (!$result) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

$page_actuelle = basename($_SERVER['PHP_SELF']);
if (!function_exists('nav_active')) {
    function nav_active($page, $page_actuelle){
        return $page === $page_actuelle ? ' class="active" aria-current="page"' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validation des Offres — Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<a href="#contenu" class="skip-link">Aller au contenu</a>

<header class="site-header">
    <div class="logo">
        <span class="logo-mark"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1>StageMaroc</h1>
        <span class="logo-tag">Espace Modérateur</span>
    </div>
    <nav id="primary-nav">
        <a href="index.php"<?= nav_active('index.php', $page_actuelle) ?>>Accueil</a>
        <a href="admin_validation.php"<?= nav_active('admin_validation.php', $page_actuelle) ?>>Validation Offres</a>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>><i class="fa-solid fa-user"></i> Mon Profil</a>
        <a href="deconnexion.php" class="confirm-action btn-nav btn-nav-danger" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
    </nav>
</header>

<main id="contenu" class="container" style="padding-top: 3.5rem;">
    <h2>Validation des offres de stage (Espace Modération)</h2>

    <?php if(isset($_GET['success'])): ?>
        <div class="success"><i class="fa-solid fa-circle-check"></i> L'offre a été validée avec succès et est en ligne !</div>
    <?php endif; ?>

    <?php if(isset($_GET['refused'])): ?>
        <div class="error"><i class="fa-solid fa-circle-xmark"></i> L'offre a été refusée. Elle ne sera pas publiée.</div>
    <?php endif; ?>

    <?php if($result->num_rows > 0): ?>
        <div id="offers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin-bottom: 0.5rem; color: var(--brand);"><?= htmlspecialchars($row['titre']); ?></h3>
                        <p><b>Ville :</b> <?= htmlspecialchars($row['ville']); ?></p>
                        <p><b>Domaine :</b> <span class="badge"><?= htmlspecialchars($row['domaine']); ?></span></p>
                        <p><b>Durée :</b> <?= htmlspecialchars($row['duree']); ?></p>
                        <p style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">
                            Statut actuel : <b style="color: var(--terracotta);"><?= htmlspecialchars($row['statut']); ?></b>
                        </p>
                    </div>
                    
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <!-- Bouton Voir Détails -->
                        <a class="btn btn-block" href="details_offre.php?id=<?= $row['id']; ?>">
                            <i class="fa-solid fa-eye"></i> Détails
                        </a>

                        <!-- Bouton Valider direct -->
                        <a class="btn btn-success btn-block confirm-action" href="admin_validation.php?action=valider&id=<?= $row['id']; ?>" data-msg="Valider cette offre ? Elle sera publiée sur le site.">
                            <i class="fa-solid fa-check"></i> Valider
                        </a>

                        <!-- Bouton Refuser direct -->
                        <a class="btn btn-danger btn-block confirm-action" href="admin_validation.php?action=refuser&id=<?= $row['id']; ?>" data-msg="Refuser cette offre ? Elle ne sera pas publiée.">
                            <i class="fa-solid fa-xmark"></i> Refuser
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 2rem;">
            <p class="empty" style="color: var(--muted); font-style: italic;">Aucune offre en attente de validation pour le moment.</p>
            <p style="font-size: 0.9rem; margin-top: 1rem;">Créez une nouvelle offre avec un compte Entreprise pour tester.</p>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>© <?= date('Y'); ?> StageMaroc — Espace Administration</p>
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