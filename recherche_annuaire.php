<?php
include 'config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : "";

$sql = "SELECT id, nom, prenom, nom_entreprise, email, telephone, ville, secteur, role, cv, logo FROM users WHERE 1=1";

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
} else {
    $sql .= " AND role IN ('etudiant', 'entreprise')";
}

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR nom_entreprise LIKE ? OR ville LIKE ? OR secteur LIKE ?)";
}

$stmt = $conn->prepare($sql);

// Dynamically bind params
if (!empty($role_filter) && !empty($search)) {
    $param_search = "%" . $search . "%";
    $stmt->bind_param("ssssss", $role_filter, $param_search, $param_search, $param_search, $param_search, $param_search);
} elseif (!empty($role_filter)) {
    $stmt->bind_param("s", $role_filter);
} elseif (!empty($search)) {
    $param_search = "%" . $search . "%";
    $stmt->bind_param("sssss", $param_search, $param_search, $param_search, $param_search, $param_search);
}

$stmt->execute();
$result = $stmt->get_result();

$page_actuelle = basename($_SERVER['PHP_SELF']);
function nav_active($page, $page_actuelle){
    return $page === $page_actuelle ? ' class="active" aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire des Utilisateurs — StageMaroc</title>
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
        <a href="recherche_annuaire.php"<?= nav_active('recherche_annuaire.php', $page_actuelle) ?>>Annuaire</a>
        <?php if (isset($_SESSION['role'])): ?>
            <a href="profil.php">Mon Profil</a>
            <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main id="contenu" class="container" style="padding-top: 3.5rem;">
    <h2>Annuaire des Étudiants & Entreprises</h2>

    <!-- Formulaire de recherche filtré -->
    <form method="GET" class="card reveal is-visible" style="flex-direction: row; gap: 1rem; flex-wrap: wrap; max-width: 100%; align-items: flex-end;">
        <div style="flex: 2; min-width: 250px;">
            <label for="search">Rechercher par nom, ville, secteur...</label>
            <input id="search" type="text" name="search" placeholder="Nom, ville, mots-clés..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="flex: 1; min-width: 180px;">
            <label for="role_filter">Type de profil</label>
            <select id="role_filter" name="role_filter">
                <option value="">Tous les profils</option>
                <option value="etudiant" <?= $role_filter === 'etudiant' ? 'selected' : '' ?>>Étudiants</option>
                <option value="entreprise" <?= $role_filter === 'entreprise' ? 'selected' : '' ?>>Entreprises</option>
            </select>
        </div>
        <button type="submit" class="btn" style="margin-top: 0; height: 48px; align-self: flex-end;">Filtrer</button>
    </form>

    <div id="offers-grid" style="margin-top: 2rem;">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <?php if($row['role'] === 'etudiant'): ?>
                        <h3 style="color: var(--brand-dark);"><i class="fa-solid fa-user-graduate" style="color: var(--brand);"></i> <?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></h3>
                        <p class="badge" style="background: #eef7f4; color: var(--brand);">Étudiant</p>
                        <p><i class="fa-solid fa-location-dot"></i> Ville : <?= htmlspecialchars($row['ville']) ?></p>
                        <?php if(!empty($row['cv'])): ?>
                            <a href="<?= htmlspecialchars($row['cv']) ?>" target="_blank" class="btn btn-gold btn-sm"><i class="fa-solid fa-file-pdf"></i> Voir CV</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <h3 style="color: var(--brand-dark);"><i class="fa-solid fa-building" style="color: var(--gold);"></i> <?= htmlspecialchars($row['nom_entreprise']) ?></h3>
                        <p class="badge" style="background: #fbf5e8; color: var(--gold-dark);">Entreprise</p>
                        <p><b>Secteur :</b> <?= htmlspecialchars($row['secteur']) ?></p>
                        <p><i class="fa-solid fa-location-dot"></i> Ville : <?= htmlspecialchars($row['ville']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">Aucun profil ne correspond à vos critères de recherche.</p>
        <?php endif; ?>
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