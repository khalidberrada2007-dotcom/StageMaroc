<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'ID de l'offre est présent f l-lien
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de l'offre manquant.");
}

$offre_id = intval($_GET['id']);

// Action de validation ou de refus depuis les détails (si c'est l'admin)
if (isset($_GET['action']) && in_array($_GET['action'], ['valider', 'refuser']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $nouveau_statut = $_GET['action'] === 'valider' ? 'valide' : 'refuse';
    $stmt = $conn->prepare("UPDATE offres SET statut = ? WHERE id = ?");
    $stmt->bind_param("si", $nouveau_statut, $offre_id);
    $stmt->execute();
    $stmt->close();
    $param_succes = $_GET['action'] === 'valider' ? 'success=1' : 'refused=1';
    header("Location: admin_validation.php?" . $param_succes);
    exit();
}

// Requête SQL:

$query = "SELECT o.*, u.nom_entreprise, u.email, u.telephone 
          FROM offres o 
          INNER JOIN users u ON o.entreprise_id = u.id 
          WHERE o.id = ?";
          
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $offre_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Offre introuvable dans la base de données (ID: " . $offre_id . ")");
    }

    $offre = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Erreur SQL dans la préparation : " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($offre['titre']) ?> — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<header class="site-header">
    <div class="logo">
        <span class="logo-mark"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1>StageMaroc</h1>
    </div>
    <nav id="primary-nav">
        <a href="index.php">Accueil</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin_validation.php">Validation Offres</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['role'])): ?>
            <a href="profil.php">Mon Profil</a>
            <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container" style="padding-top: 3.5rem; max-width: 800px;">
    <div class="card" style="padding: 2rem; border-top: 4px solid var(--brand);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin-bottom: 0.5rem; color: var(--brand-dark);"><?= htmlspecialchars($offre['titre']) ?></h2>
                <p style="font-size: 1.1rem; color: var(--gold-dark);"><strong><i class="fa-solid fa-building"></i> <?= htmlspecialchars($offre['nom_entreprise']) ?></strong></p>
            </div>
            <span class="badge" style="font-size: 1rem; padding: 0.5rem 1rem;"><?= htmlspecialchars($offre['domaine']) ?></span>
        </div>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #eee;">

        <div style="display: flex; gap: 2rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <p><i class="fa-solid fa-location-dot" style="color: var(--brand);"></i> <strong>Ville :</strong> <?= htmlspecialchars($offre['ville']) ?></p>
            <p><i class="fa-solid fa-calendar-days" style="color: var(--brand);"></i> <strong>Date :</strong> <?= htmlspecialchars($offre['date_pub']) ?></p>
            <p><i class="fa-solid fa-circle-info" style="color: var(--brand);"></i> <strong>Statut :</strong> 
                <?php
                    $statut_labels = [
                        'valide'  => ['En ligne', 'var(--success)'],
                        'refuse'  => ['Refusée', 'var(--danger)'],
                    ];
                    [$statut_label, $statut_color] = $statut_labels[$offre['statut']] ?? ['En attente', 'var(--gold-dark)'];
                ?>
                <span style="font-weight: bold; color: <?= $statut_color ?>;">
                    <?= $statut_label ?>
                </span>
                <?php if (!empty($offre['date_limite']) && strtotime($offre['date_limite']) < strtotime('today')): ?>
                    <span class="badge" style="background: var(--danger-bg); color: var(--danger); margin-left: 0.5rem;">
                        <i class="fa-solid fa-clock"></i> Date limite dépassée
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <h3 style="margin-bottom: 0.8rem;"><i class="fa-solid fa-file-lines"></i> Description</h3>
        <p style="line-height: 1.6; white-space: pre-line; background: #f9f9f9; padding: 1rem; border-radius: 6px;">
            <?= htmlspecialchars($offre['description']) ?>
        </p>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $offre['statut'] !== 'valide' && $offre['statut'] !== 'refuse'): ?>
                <a class="btn" href="details_offre.php?id=<?= $offre['id']; ?>&action=valider" style="background: var(--success);" onclick="return confirm('Valider cette offre ?');">
                    <i class="fa-solid fa-check"></i> Valider l'offre
                </a>
                <a class="btn" href="details_offre.php?id=<?= $offre['id']; ?>&action=refuser" style="background: var(--danger);" onclick="return confirm('Refuser cette offre ? Elle ne sera pas publiée.');">
                    <i class="fa-solid fa-xmark"></i> Refuser l'offre
                </a>
            <?php endif; ?>
            
            <a class="btn" href="admin_validation.php" style="background: #6c757d; color: white;">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</main>
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