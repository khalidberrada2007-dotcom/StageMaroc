<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : réservé aux entreprises
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'entreprise') {
    header("Location: index.php");
    exit();
}

$entreprise_id = intval($_SESSION['user_id']);
$flash = '';

// Action accepter / refuser une candidature
if (isset($_GET['action']) && in_array($_GET['action'], ['accepter', 'refuser']) && isset($_GET['id'])) {
    $candidature_id = intval($_GET['id']);
    $nouveau_statut = $_GET['action'] === 'accepter' ? 'acceptee' : 'refusee';

    // Sécurité : la candidature doit appartenir à une offre de CETTE entreprise
    $stmt = $conn->prepare("
        UPDATE candidatures c
        INNER JOIN offres o ON o.id = c.offre_id
        SET c.statut = ?
        WHERE c.id = ? AND o.entreprise_id = ?
    ");
    $stmt->bind_param("sii", $nouveau_statut, $candidature_id, $entreprise_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $flash = $_GET['action'] === 'accepter'
            ? ['type' => 'success', 'text' => "Candidature acceptée. L'étudiant sera informé via son profil."]
            : ['type' => 'error', 'text' => 'Candidature refusée.'];
    } else {
        $flash = ['type' => 'error', 'text' => 'Impossible de traiter cette candidature.'];
    }
    $stmt->close();

    // Redirection PRG pour éviter la re-soumission
    $qs = $flash ? 'msg=' . urlencode($flash['text']) . '&type=' . urlencode($flash['type']) : '';
    header("Location: candidatures.php?" . $qs);
    exit();
}

// Filtrer par offre (optionnel)
$offre_filter = isset($_GET['offre']) ? intval($_GET['offre']) : 0;

// Liste des offres de l'entreprise (pour le filtre)
$offres = $conn->prepare("SELECT id, titre FROM offres WHERE entreprise_id = ? ORDER BY date_pub DESC");
$offres->bind_param("i", $entreprise_id);
$offres->execute();
$offres_result = $offres->get_result();
$offres->close();

// Candidatures des offres de l'entreprise
$sql = "
    SELECT c.id AS candidature_id, c.statut, c.date_candidature,
           o.id AS offre_id, o.titre AS offre_titre,
           u.prenom, u.nom, u.email, u.telephone, u.ville, u.cv
    FROM candidatures c
    INNER JOIN offres o ON o.id = c.offre_id
    INNER JOIN users u ON u.id = c.etudiant_id
    WHERE o.entreprise_id = ?
";

if ($offre_filter > 0) {
    $sql .= " AND o.id = ?";
}

$sql .= " ORDER BY c.date_candidature DESC";

if ($offre_filter > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $entreprise_id, $offre_filter);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $entreprise_id);
}

$stmt->execute();
$candidatures = $stmt->get_result();
$stmt->close();

$page_actuelle = basename($_SERVER['PHP_SELF']);
function nav_active($page, $page_actuelle){
    return $page === $page_actuelle ? ' class="active" aria-current="page"' : '';
}

$statut_badges = [
    'en_attente' => ['En attente', 'var(--gold); color: var(--brand-dark);'],
    'acceptee'   => ['Acceptée', 'var(--success-bg); color: var(--success);'],
    'refusee'    => ['Refusée', 'var(--danger-bg); color: var(--danger);'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatures reçues — StageMaroc</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<a href="#contenu" class="skip-link">Aller au contenu</a>

<header class="site-header">
    <div class="logo">
        <span class="logo-mark"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1>StageMaroc</h1>
        <span class="logo-tag">Espace Entreprise</span>
    </div>
    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Ouvrir le menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="primary-nav">
        <a href="index.php"<?= nav_active('index.php', $page_actuelle) ?>>Accueil</a>
        <a href="poster_offre.php"<?= nav_active('poster_offre.php', $page_actuelle) ?>>Publier une offre</a>
        <a href="candidatures.php"<?= nav_active('candidatures.php', $page_actuelle) ?>>Candidatures</a>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>><i class="fa-solid fa-user"></i> Mon Profil</a>
        <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
    </nav>
</header>

<main id="contenu" class="container" style="padding-top: 3.5rem;">
    <h2><i class="fa-solid fa-inbox"></i> Candidatures reçues</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="<?= ($_GET['type'] ?? 'success') === 'success' ? 'success' : 'error' ?>">
            <i class="fa-solid <?= ($_GET['type'] ?? 'success') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Filtre par offre -->
    <form method="GET" class="card reveal is-visible" style="flex-direction: row; gap: 1rem; flex-wrap: wrap; max-width: 100%; align-items: flex-end;">
        <div style="flex: 2; min-width: 250px;">
            <label for="offre">Filtrer par offre</label>
            <select id="offre" name="offre">
                <option value="">Toutes mes offres</option>
                <?php while ($o = $offres_result->fetch_assoc()): ?>
                    <option value="<?= $o['id'] ?>" <?= $offre_filter === (int)$o['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['titre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-sm" style="margin-top: 0; height: 48px; align-self: flex-end;">Filtrer</button>
        <?php if ($offre_filter > 0): ?>
            <a href="candidatures.php" class="btn btn-sm btn-muted" style="margin-top: 0; height: 48px; align-self: flex-end;">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </form>

    <?php if ($candidatures->num_rows > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            <?php while ($c = $candidatures->fetch_assoc()): ?>
                <?php [$badge_label, $badge_style] = $statut_badges[$c['statut']] ?? ['Inconnu', 'var(--line); color: var(--muted);']; ?>
                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; color: var(--brand);">
                                <i class="fa-solid fa-user-graduate"></i> <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                            </h3>
                            <span class="badge" style="background: <?= $badge_style ?>;"><?= $badge_label ?></span>
                        </div>

                        <p style="font-size: .9rem;"><i class="fa-solid fa-briefcase"></i> <b><?= htmlspecialchars($c['offre_titre']) ?></b></p>
                        <p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($c['email']) ?></p>
                        <p><i class="fa-solid fa-phone"></i> <?= !empty($c['telephone']) ? htmlspecialchars($c['telephone']) : 'Non renseigné' ?></p>
                        <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($c['ville']) ?></p>
                        <p style="font-size: .82rem; color: var(--muted); margin-top: .5rem;">
                            <i class="fa-solid fa-calendar-days"></i> Candidature du <?= date('d/m/Y H:i', strtotime($c['date_candidature'])) ?>
                        </p>

                        <?php if (!empty($c['cv'])): ?>
                            <p style="margin-top: .6rem;">
                                <a href="<?= htmlspecialchars($c['cv']) ?>" target="_blank" class="btn btn-gold btn-sm">
                                    <i class="fa-solid fa-file-pdf"></i> Voir le CV
                                </a>
                            </p>
                        <?php else: ?>
                            <p style="font-size: .82rem; color: var(--muted); font-style: italic; margin-top: .6rem;">
                                Aucun CV téléversé par l'étudiant.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 1rem; display: flex; gap: .5rem; flex-wrap: wrap;">
                        <?php if ($c['statut'] === 'en_attente'): ?>
                            <a class="btn btn-success btn-sm btn-block confirm-action" href="candidatures.php?action=accepter&id=<?= $c['candidature_id'] ?>" data-msg="Accepter cette candidature ? L'étudiant sera informé via son profil.">
                                <i class="fa-solid fa-check"></i> Accepter
                            </a>
                            <a class="btn btn-danger btn-sm btn-block confirm-action" href="candidatures.php?action=refuser&id=<?= $c['candidature_id'] ?>" data-msg="Refuser cette candidature ?">
                                <i class="fa-solid fa-xmark"></i> Refuser
                            </a>
                        <?php else: ?>
                            <span class="btn btn-muted btn-sm btn-block" style="cursor: not-allowed;">
                                <i class="fa-solid fa-lock"></i> Traitée
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 3rem 2rem;">
            <i class="fa-solid fa-inbox" style="font-size: 2rem; color: var(--muted); margin-bottom: .8rem; display: block;"></i>
            <p class="empty" style="border: none; padding: 0;">Aucune candidature reçue pour le moment. Les demandes de postulation des étudiants apparaîtront ici.</p>
        </div>
    <?php endif; ?>
</main>

<footer>
    <span class="footer-mark">StageMaroc</span>
    <p>© <?= date('Y'); ?> StageMaroc — Espace Entreprise</p>
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

