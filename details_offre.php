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

/* ---- Système de candidatures (étudiant) -------------------------------- */
$candidature_status = null;
$candidature_msg = "";

if (isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant') {
    $etudiant_id = intval($_SESSION['user_id']);

    // Vérifier si l'étudiant a déjà postulé à cette offre
    $stmt_c = $conn->prepare("SELECT statut FROM candidatures WHERE offre_id = ? AND etudiant_id = ? LIMIT 1");
    $stmt_c->bind_param("ii", $offre_id, $etudiant_id);
    $stmt_c->execute();
    $res_c = $stmt_c->get_result();
    if ($res_c->num_rows > 0) {
        $candidature_status = $res_c->fetch_assoc()['statut'];
    }
    $stmt_c->close();

    // Action "Postuler"
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postuler'])) {
        if ($candidature_status !== null) {
            $candidature_msg = "Vous avez déjà postulé à cette offre.";
        } elseif ($offre['statut'] !== 'valide') {
            $candidature_msg = "Cette offre n'est plus ouverte aux candidatures.";
        } elseif (!empty($offre['date_limite']) && strtotime($offre['date_limite']) < strtotime('today')) {
            $candidature_msg = "La date limite de candidature est dépassée.";
        } else {
            $stmt_c = $conn->prepare("INSERT INTO candidatures (offre_id, etudiant_id, statut) VALUES (?, ?, 'en_attente')");
            $stmt_c->bind_param("ii", $offre_id, $etudiant_id);
            if ($stmt_c->execute()) {
                $candidature_status = 'en_attente';
                $candidature_msg = "Votre candidature a bien été envoyée à l'entreprise. Vous pouvez suivre son statut dans votre profil.";
            } else {
                $candidature_msg = "Une erreur est survenue lors de l'envoi de votre candidature.";
            }
            $stmt_c->close();
        }
    }
}

$candidature_labels = [
    'en_attente' => 'En attente',
    'acceptee'   => 'Acceptée',
    'refusee'    => 'Refusée',
];
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
        <span class="logo-tag">Étudiants × Entreprises</span>
    </div>
    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Ouvrir le menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="primary-nav">
        <a href="index.php">Accueil</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin_validation.php">Validation Offres</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'entreprise'): ?>
            <a href="candidatures.php">Candidatures</a>
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

        <?php if (!empty($candidature_msg)): ?>
            <div class="<?= strpos($candidature_msg, 'bien été envoyée') !== false ? 'success' : 'error' ?>" style="margin-top: 1.5rem;">
                <i class="fa-solid <?= strpos($candidature_msg, 'bien été envoyée') !== false ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= htmlspecialchars($candidature_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($candidature_status !== null): ?>
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--brand-light); border-radius: var(--radius-sm); display: flex; align-items: center; gap: .8rem; flex-wrap: wrap;">
                <i class="fa-solid fa-paper-plane" style="color: var(--brand); font-size: 1.3rem;"></i>
                <span style="font-weight: 600; color: var(--brand-dark);">Votre candidature :</span>
                <span class="badge" style="background:
                    <?= $candidature_status === 'acceptee' ? 'var(--success-bg); color: var(--success);' : ($candidature_status === 'refusee' ? 'var(--danger-bg); color: var(--danger);' : 'var(--gold); color: var(--brand-dark);') ?>">
                    <?= htmlspecialchars($candidature_labels[$candidature_status] ?? $candidature_status) ?>
                </span>
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant' && $offre['statut'] === 'valide' && $candidature_status === null): ?>
                <?php if (!empty($offre['date_limite']) && strtotime($offre['date_limite']) < strtotime('today')): ?>
                    <span class="btn btn-muted" style="cursor: not-allowed;">
                        <i class="fa-solid fa-lock"></i> Candidatures closes
                    </span>
                <?php else: ?>
                    <form method="POST" style="display: inline; margin: 0;">
                        <button type="submit" name="postuler" class="btn btn-gold confirm-action" data-msg="Confirmer votre candidature pour cette offre ?">
                            <i class="fa-solid fa-paper-plane"></i> Postuler
                        </button>
                    </form>
                <?php endif; ?>
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant' && $candidature_status === null): ?>
                <span class="btn btn-muted" style="cursor: not-allowed;">
                    <i class="fa-solid fa-lock"></i> Offre non disponible
                </span>
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $offre['statut'] !== 'valide' && $offre['statut'] !== 'refuse'): ?>
                <a class="btn confirm-action" href="details_offre.php?id=<?= $offre['id']; ?>&action=valider" data-msg="Valider cette offre ? Elle sera publiée sur le site." style="background: var(--success);">
                    <i class="fa-solid fa-check"></i> Valider l'offre
                </a>
                <a class="btn confirm-action" href="details_offre.php?id=<?= $offre['id']; ?>&action=refuser" data-msg="Refuser cette offre ? Elle ne sera pas publiée." style="background: var(--danger);">
                    <i class="fa-solid fa-xmark"></i> Refuser l'offre
                </a>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a class="btn" href="admin_validation.php" style="background: #6c757d; color: white;">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
            <?php else: ?>
                <a class="btn" href="index.php" style="background: #6c757d; color: white;">
                    <i class="fa-solid fa-arrow-left"></i> Retour aux offres
                </a>
            <?php endif; ?>
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