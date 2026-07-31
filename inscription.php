<?php
include 'config.php';
require_once 'includes/functions.php';

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
    $secteur_autre  = trim($_POST['secteur_autre'] ?? '');
    // Si l'utilisateur a choisi "Autre", on utilise la valeur saisie
    if ($secteur === 'autre') {
        $secteur = $secteur_autre;
    }
    $description    = trim($_POST['description'] ?? '');

    // Le contact d'une entreprise est saisi dans un champ séparé (évite le conflit avec le nom étudiant)
    if ($role === 'entreprise') {
        $nom = trim($_POST['nom_contact'] ?? '');
    }

    $cv_path   = null;
    $logo_path = null;

    // --- Validation des champs obligatoires avec messages spécifiques ---
    $errors = [];

    if (empty($role) || !in_array($role, ['etudiant', 'entreprise'])) {
        $errors[] = "Veuillez sélectionner un type de compte.";
    }

    if ($role === 'etudiant') {
        if (empty($prenom)) { $errors[] = "Le prénom est obligatoire."; }
        if (empty($nom))    { $errors[] = "Le nom est obligatoire."; }
    }

    if ($role === 'entreprise') {
        if (empty($nom))           { $errors[] = "Le nom du responsable est obligatoire."; }
        if (empty($nom_entreprise)) { $errors[] = "Le nom de l'entreprise est obligatoire."; }
    }

    if (empty($ville))    { $errors[] = "La ville est obligatoire."; }
    if (empty($telephone)) { $errors[] = "Le téléphone est obligatoire."; }
    if (empty($email))    { $errors[] = "L'adresse e-mail est obligatoire."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse e-mail invalide.";
    }
    if (empty($password)) { $errors[] = "Le mot de passe est obligatoire."; }
    elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if (empty($confirm))  { $errors[] = "La confirmation du mot de passe est obligatoire."; }
    elseif ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
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
                    (nom, prenom, nom_entreprise, email, mot_de_passe, telephone, ville, secteur, description, logo, cv, role, email_verified)
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                if (!$stmt) {
                    error_log('Erreur préparation SQL inscription : ' . $conn->error);
                    $error = "Une erreur interne est survenue. Veuillez réessayer plus tard.";
                } else {
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
                        $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.
                                    <a href='connexion.php' style='color: inherit; font-weight: 700; text-decoration: underline;'>
                                    Se connecter</a>";
                    } else {
                        error_log('Erreur exécution SQL inscription : ' . $stmt->error);
                        $error = "Une erreur est survenue lors de la création du compte.";
                    }

                    $stmt->close();
                }
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
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'entreprise'): ?>
        <a href="candidatures.php"<?= nav_active('candidatures.php', $page_actuelle) ?>>Candidatures</a>
    <?php endif; ?>
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
<div class="success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
<?php } ?>

<?php if($error!=""){ ?>
<div class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php } ?>

<form method="POST" enctype="multipart/form-data" class="card reveal is-visible" id="form-inscription" novalidate>

<div class="required-legend">
    <span class="required-asterisk">*</span> Champs obligatoires
</div>

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

    <label for="prenom"><span class="required-asterisk">*</span> Prénom</label>
    <input id="prenom" type="text" name="prenom" placeholder="Votre prénom" value="<?= e($_POST['prenom'] ?? '') ?>" required>

    <label for="nom"><span class="required-asterisk">*</span> Nom</label>
    <input id="nom" type="text" name="nom" placeholder="Votre nom" value="<?= e($_POST['nom'] ?? '') ?>" required>

    <label for="cv">CV (PDF, optionnel)</label>
    <input id="cv" type="file" name="cv" accept="application/pdf">

</div>

<!-- Champs entreprise -->
<div id="champ-entreprise" style="display:none;">

    <label for="nom_contact"><span class="required-asterisk">*</span> Nom du responsable</label>
    <input id="nom_contact" type="text" name="nom_contact" placeholder="Nom de la personne de contact" value="<?= e($_POST['nom_contact'] ?? '') ?>" required>

    <label for="nom_entreprise"><span class="required-asterisk">*</span> Nom de l'entreprise</label>
    <input id="nom_entreprise" type="text" name="nom_entreprise" placeholder="Raison sociale" value="<?= e($_POST['nom_entreprise'] ?? '') ?>" required>

        <label for="secteur"><span class="required-asterisk">*</span> Secteur d'activité</label>
    <select id="secteur" name="secteur" required>
        <option value="">Choisir</option>
        <option value="Développement Web"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Développement Web')?' selected':'' ?>>Développement Web</option>
        <option value="Développement Mobile"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Développement Mobile')?' selected':'' ?>>Développement Mobile</option>
        <option value="Cybersécurité"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Cybersécurité')?' selected':'' ?>>Cybersécurité</option>
        <option value="Data Science"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Data Science')?' selected':'' ?>>Data Science</option>
        <option value="Marketing Digital"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Marketing Digital')?' selected':'' ?>>Marketing Digital</option>
        <option value="Finance"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Finance')?' selected':'' ?>>Finance</option>
        <option value="Comptabilité"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Comptabilité')?' selected':'' ?>>Comptabilité</option>
        <option value="Ressources Humaines"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Ressources Humaines')?' selected':'' ?>>Ressources Humaines</option>
        <option value="Commerce"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Commerce')?' selected':'' ?>>Commerce</option>
        <option value="Logistique"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Logistique')?' selected':'' ?>>Logistique</option>
        <option value="Santé"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Santé')?' selected':'' ?>>Santé</option>
        <option value="Éducation"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Éducation')?' selected':'' ?>>Éducation</option>
        <option value="Design"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Design')?' selected':'' ?>>Design</option>
        <option value="Tourisme"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Tourisme')?' selected':'' ?>>Tourisme</option>
        <option value="Réseaux"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Réseaux')?' selected':'' ?>>Réseaux</option>
        <option value="Gestion"<?= (isset($_POST['secteur']) && $_POST['secteur']==='Gestion')?' selected':'' ?>>Gestion</option>
        <option value="autre"<?= (isset($_POST['secteur']) && $_POST['secteur']==='autre')?' selected':'' ?>>Autre (à préciser)</option>
    </select>
    <div id="champ-secteur-autre" style="display:<?= (isset($_POST['secteur']) && $_POST['secteur']==='autre')?'block':'none' ?>; margin-top: .5rem;">
        <label for="secteur_autre">Précisez votre secteur</label>
        <input id="secteur_autre" type="text" name="secteur_autre" placeholder="ex: Agriculture, Industrie..." value="<?= htmlspecialchars($_POST['secteur_autre']??'') ?>">
    </div>

    <label for="description">Description de l'entreprise</label>
    <textarea id="description" name="description" rows="4" placeholder="Quelques mots sur votre entreprise…"><?= e($_POST['description'] ?? '') ?></textarea>

    <label for="logo">Logo (image, optionnel)</label>
    <input id="logo" type="file" name="logo" accept="image/png, image/jpeg, image/webp">

</div>

<label for="ville"><span class="required-asterisk">*</span> Ville</label>
<input id="ville" type="text" name="ville" placeholder="Casablanca" value="<?= e($_POST['ville'] ?? '') ?>" required>

<label for="telephone"><span class="required-asterisk">*</span> Téléphone</label>
<input id="telephone" type="tel" name="telephone" placeholder="06 00 00 00 00" value="<?= e($_POST['telephone'] ?? '') ?>" required>

<label for="email"><span class="required-asterisk">*</span> Email</label>
<input id="email" type="email" name="email" placeholder="vous@exemple.com" value="<?= e($_POST['email'] ?? '') ?>" required>

<label for="password"><span class="required-asterisk">*</span> Mot de passe</label>
<input id="password" type="password" name="password" placeholder="6 caractères minimum" required>

<label for="confirm_password"><span class="required-asterisk">*</span> Confirmer le mot de passe</label>
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

    // Toggle champ secteur "Autre"
    var secteurSelect = document.getElementById('secteur');
    var champSecteurAutre = document.getElementById('champ-secteur-autre');
    if (secteurSelect && champSecteurAutre) {
        function toggleSecteurAutre() {
            champSecteurAutre.style.display = (secteurSelect.value === 'autre') ? 'block' : 'none';
        }
        secteurSelect.addEventListener('change', toggleSecteurAutre);
        toggleSecteurAutre();
    }
})();

// ---- Validation client du formulaire d'inscription ----
(function() {
    var form = document.getElementById('form-inscription');
    if (!form) return;

    // Fonction pour afficher une erreur sous un champ
    function showFieldError(input, message) {
        var parent = input.parentNode;
        var existing = parent.querySelector('.field-error');
        if (existing) existing.remove();

        input.classList.add('input-error');
        var error = document.createElement('div');
        error.className = 'field-error';
        error.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + message;
        parent.appendChild(error);
    }

    function clearFieldError(input) {
        input.classList.remove('input-error');
        var parent = input.parentNode;
        var existing = parent.querySelector('.field-error');
        if (existing) existing.remove();
    }

    form.addEventListener('submit', function(e) {
        var errors = [];
        var role = form.querySelector('input[name="role"]:checked');
        var roleValue = role ? role.value : '';
        
        // Nettoyer les anciennes erreurs
        form.querySelectorAll('.input-error').forEach(function(el) { el.classList.remove('input-error'); });
        form.querySelectorAll('.field-error').forEach(function(el) { el.remove(); });

        if (roleValue === 'etudiant') {
            var prenom = document.getElementById('prenom');
            var nom = document.getElementById('nom');
            if (!prenom.value.trim()) { showFieldError(prenom, 'Le prénom est obligatoire.'); errors.push('prenom'); }
            if (!nom.value.trim()) { showFieldError(nom, 'Le nom est obligatoire.'); errors.push('nom'); }
        }

        if (roleValue === 'entreprise') {
            var nomContact = document.getElementById('nom_contact');
            var nomEntreprise = document.getElementById('nom_entreprise');
            var secteur = document.getElementById('secteur');
            if (!nomContact.value.trim()) { showFieldError(nomContact, 'Le nom du responsable est obligatoire.'); errors.push('nom_contact'); }
            if (!nomEntreprise.value.trim()) { showFieldError(nomEntreprise, 'Le nom de l\'entreprise est obligatoire.'); errors.push('nom_entreprise'); }
            if (!secteur.value) { showFieldError(secteur, 'Le secteur est obligatoire.'); errors.push('secteur'); }
        }

        var ville = document.getElementById('ville');
        var telephone = document.getElementById('telephone');
        var email = document.getElementById('email');
        var password = document.getElementById('password');
        var confirm = document.getElementById('confirm_password');

        if (!ville.value.trim()) { showFieldError(ville, 'La ville est obligatoire.'); errors.push('ville'); }
        if (!telephone.value.trim()) { showFieldError(telephone, 'Le téléphone est obligatoire.'); errors.push('telephone'); }
        if (!email.value.trim()) { showFieldError(email, 'L\'email est obligatoire.'); errors.push('email'); }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) { showFieldError(email, 'Email invalide.'); errors.push('email'); }
        if (!password.value) { showFieldError(password, 'Le mot de passe est obligatoire.'); errors.push('password'); }
        else if (password.value.length < 6) { showFieldError(password, '6 caractères minimum.'); errors.push('password'); }
        if (!confirm.value) { showFieldError(confirm, 'La confirmation est obligatoire.'); errors.push('confirm'); }
        else if (password.value !== confirm.value) { showFieldError(confirm, 'Les mots de passe ne correspondent pas.'); errors.push('confirm'); }

        if (errors.length > 0) {
            e.preventDefault();
            // Scroll to first error
            var firstError = form.querySelector('.input-error');
            if (firstError) firstError.focus();
        }
    });

    // Effacer l'erreur quand l'utilisateur corrige le champ
    form.querySelectorAll('input, select, textarea').forEach(function(input) {
        input.addEventListener('input', function() { clearFieldError(this); });
        input.addEventListener('change', function() { clearFieldError(this); });
    });
})();
</script>

</body>
</html>
