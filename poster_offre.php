<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Ila makantx entreprise, rj3o l index.php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'entreprise') {
    header("Location: index.php");
    exit();
}

// Khdem direct b $_SESSION['user_id']
$entreprise_id = $_SESSION['user_id']; 

$success = "";
$error = "";

if (isset($_POST['submit'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $ville = trim($_POST['ville']);
    $domaine = trim($_POST['domaine']);
    $type_stage = trim($_POST['type_stage']);
    $duree = ($_POST['duree'] === 'custom') ? trim($_POST['custom_duree']) : trim($_POST['duree']);    $date_limite = $_POST['date_limite'];

    if (empty($titre) || empty($description) || empty($ville) || empty($domaine) || empty($type_stage) || empty($duree) || empty($date_limite)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $conn->prepare("INSERT INTO offres (titre, description, ville, domaine, type_stage, duree, date_limite, entreprise_id, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
        $stmt->bind_param("sssssssi", $titre, $description, $ville, $domaine, $type_stage, $duree, $date_limite, $entreprise_id);

        if ($stmt->execute()) {
            $success = "Votre offre a été envoyée avec succès et sera validée par l'administrateur.";
        } else {
            $error = "Une erreur est survenue lors de l'insertion : " . $conn->error;
        }
        $stmt->close();
    }
}

// Déclaration darouriya bach ma-itla3ch error f l-menu
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
    <title>Publier une offre — StageMaroc</title>
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
        <a href="poster_offre.php"<?= nav_active('poster_offre.php', $page_actuelle) ?>>Publier une offre</a>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>><i class="fa-solid fa-user"></i> Mon Profil</a>
        <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
    </nav>
</header>

<main id="contenu" class="container" style="padding-top: 3.5rem;">
    <h2>Publier une offre de stage</h2>
    <?php
    if($success!=""){ echo "<div class='success'><i class='fa-solid fa-circle-check'></i> $success</div>"; }
    if($error!=""){ echo "<div class='error'><i class='fa-solid fa-circle-exclamation'></i> $error</div>"; }
    ?>

    <form method="POST" class="card reveal is-visible">
        <label for="titre">Titre</label>
        <input id="titre" type="text" name="titre" placeholder="Développeur Web" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="6" required></textarea>

        <label for="ville">Ville</label>
        <input id="ville" type="text" name="ville" placeholder="Casablanca" required>

        <label for="domaine">Domaine</label>
        <select id="domaine" name="domaine" required>
            <option value="">Choisir</option>
            <option>Développement Web</option>
            <option>Développement Mobile</option>
            <option>Cybersécurité</option>
            <option>Data Science</option>
            <option>Marketing Digital</option>
            <option>Finance</option>
            <option>Réseaux</option>
            <option>Gestion</option>
        </select>

        <label for="type_stage">Type de stage</label>
        <select id="type_stage" name="type_stage" required>
            <option value="">Choisir</option>
            <option>PFE</option>
            <option>PFA</option>
            <option>Initiation</option>
            <option>Observation</option>
        </select>

        <label for="duree">Durée du stage</label>
<select id="duree" name="duree" onchange="toggleCustomDuree(this)" required>
    <option value="">Choisir</option>
    <option value="1 mois">1 mois</option>
    <option value="2 mois">2 mois</option>
    <option value="3 mois">3 mois</option>
    <option value="6 mois">6 mois</option>
    <option value="custom">Autre (Spécifier...)</option>
</select>

<!-- هاد لـ input غيكون مخبي ومكيبانش حتى يختار Autre -->
<div id="custom-duree-container" style="display: none; margin-top: 0.5rem;">
    <input type="text" id="custom_duree" name="custom_duree" placeholder="Ex: 3 semaines, 5 mois...">
</div>

<script>
function toggleCustomDuree(select) {
    const container = document.getElementById('custom-duree-container');
    const input = document.getElementById('custom_duree');
    if (select.value === 'custom') {
        container.style.display = 'block';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

        <label for="date_limite">Date limite</label>
        <input id="date_limite" type="date" name="date_limite" required>

        <button type="submit" name="submit" class="btn">Publier l'offre</button>
    </form>
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