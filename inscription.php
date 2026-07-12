<?php
include 'config.php';

function sendWelcomeEmail($toEmail, $toName)
{
    return false;
}

if (isset($_SESSION['entreprise_id']) || isset($_SESSION['etudiant_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if (isset($_POST['submit'])) {

    $role     = $_POST['role'] ?? '';
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $telephone = trim($_POST['telephone'] ?? '');
    $ville     = trim($_POST['ville'] ?? '');

    // Champs étudiant
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');

    // Champs entreprise
    $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $secteur        = trim($_POST['secteur'] ?? '');
    $description    = trim($_POST['description'] ?? '');

    // Le contact d'une entreprise est saisi dans un champ séparé (évite le conflit avec le nom étudiant)
    if ($role === 'entreprise') {
        $nom = trim($_POST['nom_contact'] ?? '');
    }

    $cv_path   = null;
    $logo_path = null;

    if (
        empty($role) || !in_array($role, ['etudiant', 'entreprise']) ||
        empty($email) || empty($password) || empty($confirm) ||
        ($role === 'etudiant'   && (empty($nom) || empty($prenom))) ||
        ($role === 'entreprise' && (empty($nom) || empty($nom_entreprise)))
    ) {

        $error = "Veuillez remplir tous les champs requis.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Adresse email invalide.";

    } elseif (strlen($password) < 6) {

        $error = "Le mot de passe doit contenir au moins 6 caractères.";

    } elseif ($password !== $confirm) {

        $error = "Les mots de passe ne correspondent pas.";

    } else {

        /* Email déjà utilisé ? */
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Cet email est déjà utilisé.";
            $check->close();

        } else {

            $check->close();

            /* Upload CV (étudiant, optionnel) */
            if ($role === 'etudiant' && isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
                if ($ext === 'pdf' && $_FILES['cv']['size'] <= 5 * 1024 * 1024) {
                    if (!is_dir('uploads/cv')) { mkdir('uploads/cv', 0755, true); }
                    $filename = uniqid('cv_') . '.pdf';
                    if (move_uploaded_file($_FILES['cv']['tmp_name'], 'uploads/cv/' . $filename)) {
                        $cv_path = 'uploads/cv/' . $filename;
                    }
                } else {
                    $error = "Le CV doit être un fichier PDF de 5 Mo maximum.";
                }
            }

            /* Upload logo (entreprise, optionnel) */
            if ($role === 'entreprise' && $error === "" && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['logo']['size'] <= 3 * 1024 * 1024) {
                    if (!is_dir('uploads/logos')) { mkdir('uploads/logos', 0755, true); }
                    $filename = uniqid('logo_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], 'uploads/logos/' . $filename)) {
                        $logo_path = 'uploads/logos/' . $filename;
                    }
                } else {
                    $error = "Le logo doit être une image (jpg, png, webp) de 3 Mo maximum.";
                }
            }

            if ($error === "") {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Pour un étudiant, nom_entreprise/secteur/description/logo restent NULL.
                // Pour une entreprise, prenom/cv restent NULL.
                if ($role === 'etudiant') {
                    $nom_entreprise = null;
                    $secteur = null;
                    $description = null;
                    $logo_path = null;
                } else {
                    $prenom = null;
                    $cv_path = null;
                }

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (nom, prenom, nom_entreprise, email, mot_de_passe, telephone, ville, secteur, description, logo, cv, role)
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssssssssssss",
                    $nom,
                    $prenom,
                    $nom_entreprise,
                    $email,
                    $hash,
                    $telephone,
                    $ville,
                    $secteur,
                    $description,
                    $logo_path,
                    $cv_path,
                    $role
                );

                if ($stmt->execute()) {
                    $mail_name = ($role === 'etudiant') ? "$prenom $nom" : $nom_entreprise;
                    $emailSent = sendWelcomeEmail($email, $mail_name);

                    if ($emailSent) {
                        $success = "Compte créé avec succès. Un email de bienvenue a été envoyé.";
                    } else {
                        $success = "Compte créé avec succès.";
                    }
                }

                $stmt->close();
            }
        }
    }
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

<title>Inscription — StageMaroc</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

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
    <?php if (isset($_SESSION['role'])): ?>
        <a href="profil.php"<?= nav_active('profil.php', $page_actuelle) ?>>Mon Profil</a>
        <a href="deconnexion.php" class="confirm-action" data-msg="Êtes-vous sûr de vouloir vous déconnecter ?">Déconnexion</a>
    <?php else: ?>
        <a href="poster_offre.php"<?= nav_active('poster_offre.php', $page_actuelle) ?>>Publier une offre</a>
        <a href="connexion.php"<?= nav_active('connexion.php', $page_actuelle) ?>>Connexion</a>
        <a href="inscription.php" class="btn-nav"<?= nav_active('inscription.php', $page_actuelle) ?>>Inscription</a>
    <?php endif; ?>
</nav>

</header>

<main id="contenu" class="container" style="padding-top: 3.5rem; max-width: 640px;">

<h2>Créer un compte</h2>

<?php if($success!=""){ ?>
<div class="success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
— <a href="connexion.php" style="color: inherit; font-weight: 700; text-decoration: underline;">Se connecter</a>
</div>
<?php } ?>

?>

<?php if($error!=""){ ?>
<div class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php } ?>

<form method="POST" enctype="multipart/form-data" class="card reveal is-visible" id="form-inscription">

<label>Je suis</label>
<div style="display:flex; gap:.75rem;">
    <label style="display:flex; align-items:center; gap:.4rem; font-family: var(--font-body); text-transform:none; letter-spacing:0; font-size:.95rem; color: var(--ink);">
        <input type="radio" name="role" value="etudiant" checked style="width:auto;"> Étudiant
    </label>
    <label style="display:flex; align-items:center; gap:.4rem; font-family: var(--font-body); text-transform:none; letter-spacing:0; font-size:.95rem; color: var(--ink);">
        <input type="radio" name="role" value="entreprise" style="width:auto;"> Entreprise
    </label>
</div>

<!-- Champs étudiant -->
<div id="champ-etudiant">

    <label for="prenom">Prénom</label>
    <input id="prenom" type="text" name="prenom" placeholder="Votre prénom">

    <label for="nom">Nom</label>
    <input id="nom" type="text" name="nom" placeholder="Votre nom">

    <label for="cv">CV (PDF, optionnel)</label>
    <input id="cv" type="file" name="cv" accept="application/pdf">

</div>

<!-- Champs entreprise -->
<div id="champ-entreprise" style="display:none;">

    <label for="nom_contact">Nom du responsable</label>
    <input id="nom_contact" type="text" name="nom_contact" placeholder="Nom de la personne de contact">

    <label for="nom_entreprise">Nom de l'entreprise</label>
    <input id="nom_entreprise" type="text" name="nom_entreprise" placeholder="Raison sociale">

    <label for="secteur">Secteur d'activité</label>
    <select id="secteur" name="secteur">
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

    <label for="description">Description de l'entreprise</label>
    <textarea id="description" name="description" rows="4" placeholder="Quelques mots sur votre entreprise…"></textarea>

    <label for="logo">Logo (image, optionnel)</label>
    <input id="logo" type="file" name="logo" accept="image/png, image/jpeg, image/webp">

</div>

<label for="ville">Ville</label>
<input id="ville" type="text" name="ville" placeholder="Casablanca">

<label for="telephone">Téléphone</label>
<input id="telephone" type="tel" name="telephone" placeholder="06 00 00 00 00">

<label for="email">Email</label>
<input id="email" type="email" name="email" placeholder="vous@exemple.com" required>

<label for="password">Mot de passe</label>
<input id="password" type="password" name="password" placeholder="6 caractères minimum" required>

<label for="confirm_password">Confirmer le mot de passe</label>
<input id="confirm_password" type="password" name="confirm_password" placeholder="••••••••" required>

<button type="submit" name="submit" class="btn">Créer mon compte</button>

<p style="margin-top: .5rem; font-size: .88rem; color: var(--muted);">
Déjà inscrit ?
<a href="connexion.php" style="color: var(--brand); font-weight: 600;">Se connecter</a>
</p>

</form>

</main>

<footer>
<span class="footer-mark">StageMaroc</span>
<p>© <?= date('Y'); ?> StageMaroc — Projet réalisé dans le cadre d'une formation en développement digital</p>
</footer>

<script src="app.js"></script>
<script>
(function(){
    var radios = document.querySelectorAll('input[name="role"]');
    var champEtudiant = document.getElementById('champ-etudiant');
    var champEntreprise = document.getElementById('champ-entreprise');

    function toggle(){
        var val = document.querySelector('input[name="role"]:checked').value;
        champEtudiant.style.display = (val === 'etudiant') ? 'block' : 'none';
        champEntreprise.style.display = (val === 'entreprise') ? 'block' : 'none';
    }
    radios.forEach(function(r){ r.addEventListener('change', toggle); });
    toggle();
})();
</script>

</body>
</html>