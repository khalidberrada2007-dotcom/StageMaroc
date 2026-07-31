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

// Mes candidatures (étudiant)
$mes_candidatures = null;
if ($role === 'etudiant') {
    $sql_c = "
        SELECT c.id AS candidature_id, c.statut AS candidature_statut, c.date_candidature,
               o.id AS offre_id, o.titre AS offre_titre,
               u.nom_entreprise
        FROM candidatures c
        INNER JOIN offres o ON o.id = c.offre_id
        INNER JOIN users u ON u.id = o.entreprise_id
        WHERE c.etudiant_id = ?
        ORDER BY c.date_candidature DESC
    ";
    $stmt_c = $conn->prepare($sql_c);
    $stmt_c->bind_param("i", $user_id);
    $stmt_c->execute();
    $mes_candidatures = $stmt_c->get_result();
    $stmt_c->close();
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
                        <a href="<?= htmlspecialchars($user['cv']) ?>" target="_blank" class="badge" style="text-decoration: underline;">Voir le CV PDF</a>
                    <?php else: ?>
                        <span style="color: var(--muted); font-style: italic;">Aucun CV téléversé</span>
                    <?php endif; ?>
                </p>
            </div>
            <hr style="border: 0; height: 1px; background: var(--line);">
            <div>
                <h3 style="color: var(--brand); font-size: 1.3rem; margin-bottom: 1rem;"><i class="fa-solid fa-paper-plane"></i> Mes candidatures</h3>
                <?php if ($mes_candidatures && $mes_candidatures->num_rows > 0): ?>
                    <?php while ($cand = $mes_candidatures->fetch_assoc()): ?>
                        <?php
                            $cand_badge = 'var(--gold); color: var(--brand-dark);';
                            $cand_label = 'En attente';
                            if ($cand['candidature_statut'] === 'acceptee') { $cand_badge = 'var(--success-bg); color: var(--success);'; $cand_label = 'Acceptée'; }
                            elseif ($cand['candidature_statut'] === 'refusee') { $cand_badge = 'var(--danger-bg); color: var(--danger);'; $cand_label = 'Refusée'; }
                        ?>
                        <div style="border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: .8rem; background: var(--bg);">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap;">
                                <a href="details_offre.php?id=<?= $cand['offre_id'] ?>" style="font-weight: 600; color: var(--brand);">
                                    <?= htmlspecialchars($cand['offre_titre']) ?>
                                </a>
                                <span class="badge" style="background: <?= $cand_badge ?>;"><?= $cand_label ?></span>
                            </div>
                            <p style="font-size: .85rem; color: var(--muted); margin-top: .4rem; margin-bottom: 0;">
                                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($cand['nom_entreprise']) ?>
                                &nbsp;·&nbsp;<i class="fa-solid fa-calendar-days"></i> <?= date('d/m/Y', strtotime($cand['date_candidature'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--muted); font-style: italic; font-size: .9rem;">
                        Vous n'avez pas encore postulé à une offre.
                        <a href="index.php" style="color: var(--brand); font-weight: 600;">Parcourir les offres</a>
                    </p>
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