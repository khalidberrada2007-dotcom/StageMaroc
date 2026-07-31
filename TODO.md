# Plan d'Amélioration StageMaroc — Suivi

## ✅ Étape 1: Base de données
- [x] Ajouter colonnes email_verified, verification_token, verification_expires, verification_attempts, last_verification_sent_at à `users`
- [x] Créer table `password_resets` (code 6 chiffres, attempts, expires_at, used, last_sent_at)
- [x] Marquer comptes existants comme vérifiés

## ✅ Étape 2: Infrastructure PHPMailer
- [x] Installer PHPMailer dans `vendor/PHPMailer/`
- [x] Créer `vendor/PHPMailer/autoload.php`
- [x] Créer `includes/mail_config.php` (configuration SMTP centralisée)
- [x] Créer `includes/functions.php` (fonctions utilitaires partagées)

## ✅ Étape 3: Mot de passe oublié
- [x] Créer `mdp_oublie.php` (étape 1: saisie email → génération code 6 chiffres → envoi email)
- [x] Créer `verifier_code.php` (étape 2: saisie code, validation, 5 tentatives max, expiration 15 min)
- [x] Créer `reinitialiser_mdp.php` (étape 3: nouveau mot de passe hashé, invalidation du code)
- [x] Ajouter lien "Mot de passe oublié ?" dans `connexion.php`
- [x] Limiter renvoi email à 60s (anti-spam)

## ✅ Étape 4: Inscription — Champs obligatoires
- [x] Astérisque rouge (*) devant tous les labels requis
- [x] Légende "Champs obligatoires" en haut du formulaire
- [x] Validation JS côté client avec messages d'erreur sous chaque champ
- [x] Conservation des données saisies (value="<?= e(...) ?>")
- [x] Messages d'erreur spécifiques (nom, prénom, email, etc.)

## ✅ Étape 5: Vérification d'email (après inscription)
- [x] Génération token sécurisé SHA-256 lors de l'inscription
- [x] Envoi email avec lien d'activation (valable 24h)
- [x] Créer `verification.php?token=xxx` pour activer le compte
- [x] Blocage connexion si email_verified = 0
- [x] Lien "Renvoyer l'email d'activation" dans le message d'erreur
- [x] Délai de 60s avant renvoi (anti-spam)

## ✅ Étape 6: CSS & Design
- [x] Classes `.required-asterisk`, `.input-error`, `.field-error`, `.required-legend`
- [x] Style des étapes de réinitialisation (indicateur 1-2-3)
- [x] Design responsive et cohérent avec le thème existant

## 📝 Notes
- Configurer SMTP dans `includes/mail_config.php` avant utilisation
- Les comptes existants sont déjà marqués comme vérifiés (UPDATE déjà exécuté)
- Toutes les requêtes SQL ont été fournies et exécutées
