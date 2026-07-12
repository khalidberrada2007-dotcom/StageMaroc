<?php
include 'config.php';

// Recherche
$search = "";
$sql = "SELECT o.*, u.nom_entreprise FROM offres o INNER JOIN users u ON o.entreprise_id = u.id WHERE o.statut='valide' AND u.role='entreprise'";

if(isset($_GET['search']) && !empty(trim($_GET['search']))){
    $search = trim($_GET['search']);
    $sql .= " AND (o.titre LIKE ? OR o.ville LIKE ? OR o.domaine LIKE ? OR u.nom_entreprise LIKE ?)";
}

$sql .= " ORDER BY o.date_pub DESC LIMIT 6";
$stmt = $conn->prepare($sql);

if($search != ""){
    $param = "%".$search."%";
    $stmt->bind_param("ssss", $param, $param, $param, $param);
}

$stmt->execute();
$result = $stmt->get_result();

// Statistiques
$totalOffres = $conn->query("SELECT COUNT(*) total FROM offres WHERE statut='valide'")->fetch_assoc()['total'];
$totalEntreprises = $conn->query("SELECT COUNT(*) total FROM users WHERE role='entreprise'")->fetch_assoc()['total'];
$totalEtudiants = $conn->query("SELECT COUNT(*) total FROM users WHERE role='etudiant'")->fetch_assoc()['total'];

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
    <title>StageMaroc — Trouvez votre stage idéal</title>
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
    <a href="recherche_annuaire.php">Annuaire</a>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'entreprise'): ?>
        <a href="poster_offre.php"<?= nav_active('poster_offre.php', $page_actuelle) ?>>Publier une offre</a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['role'])): ?>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>><i class="fa-solid fa-user"></i> Mon Profil (<?= htmlspecialchars($_SESSION['nom_affiche']) ?>)</a>
        <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>    <?php else: ?>
        <a href="connexion.php"<?= nav_active('connexion.php', $page_actuelle) ?>>Connexion</a>
        <a href="inscription.php" class="btn-nav">Inscription</a>
    <?php endif; ?>
</nav>
</header>

<section class="hero zellige-texture">
    <div class="hero-content">
        <span class="eyebrow">Plateforme de stages · Maroc</span>
        <h2>Trouvez votre <em>stage idéal</em></h2>
        <p>Des centaines d'offres publiées par des entreprises marocaines, mises à jour chaque jour.</p>
        <form method="GET">
            <label for="search" class="sr-only">Rechercher un stage</label>
            <input id="search" type="text" name="search" placeholder="Titre, ville, domaine, entreprise…" value="<?= htmlspecialchars($search); ?>">
            <button type="submit"><i class="fa fa-search"></i> Rechercher</button>
        </form>
    </div>
    <div class="hero-motif" aria-hidden="true">
        <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" stroke="#E3A73B" stroke-width="1.4" opacity="0.85">
                <polygon points="100,10 118,70 182,70 130,108 150,170 100,132 50,170 70,108 18,70 82,70" />
                <circle cx="100" cy="100" r="60" stroke-opacity="0.5"/>
                <circle cx="100" cy="100" r="92" stroke-opacity="0.3"/>
            </g>
        </svg>
    </div>
</section>

<section class="stats reveal-stagger">
    <div class="stat reveal" style="--i:0"><h3><?= $totalOffres ?></h3><p>Offres disponibles</p></div>
    <div class="stat reveal" style="--i:1"><h3><?= $totalEntreprises ?></h3><p>Entreprises</p></div>
    <div class="stat reveal" style="--i:2"><h3><?= $totalEtudiants ?></h3><p>Étudiants</p></div>
</section>

<main id="contenu" class="container">
    <h2>Dernières offres</h2>
    <?php if($result->num_rows>0){ ?>
    <div id="offers-grid" class="reveal-stagger">
    <?php $i = 0; while($row=$result->fetch_assoc()){ ?>
    <div class="card reveal" style="--i:<?= $i++ ?>">
        <h3><?= htmlspecialchars($row['titre']); ?></h3>
        <p><b>Entreprise :</b> <?= htmlspecialchars($row['nom_entreprise']); ?></p>
        <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($row['ville']); ?></p>
        <p><span class="badge"><?= htmlspecialchars($row['domaine']); ?></span></p>
        <p>Publié le <?= date('d/m/Y',strtotime($row['date_pub'])); ?></p>
        <a class="btn" href="detail_offre.php?id=<?= $row['id']; ?>">Voir détails</a>
    </div>
    <?php } ?>
    </div>
    <?php } else { ?>
    <p class="empty">Aucune offre ne correspond à votre recherche pour le moment.</p>
    <?php } ?>
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