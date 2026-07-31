# Plan — Simplification du système d'authentification

## Objectif
Supprimer la vérification d'e-mail lors de l'inscription et conserver uniquement le flux "Mot de passe oublié" (code 6 chiffres, 15 min, 5 tentatives max, renvoi après 60 s).

## Étapes
- [x] Étape 1 : Modifier `connexion.php` — supprimer le bloc `resend_verification` et le contrôle `email_verified`
- [x] Étape 2 : Modifier `inscription.php` — créer le compte immédiatement (`email_verified=1`), supprimer token + e-mail d'activation
- [x] Étape 3 : Supprimer `verification.php`
- [x] Étape 4 : Modifier `includes/mail_config.php` — supprimer `sendVerificationEmail()` et `getBaseUrl()` (devenu inutile)
- [x] Étape 5 : Modifier `includes/functions.php` — supprimer `generateSecureToken()`, `canResendEmail()`, `markEmailSent()`, `getResendCooldownMessage()`
- [x] Étape 6 : Nettoyer la base de données (`UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL, last_verification_sent_at = NULL`)
- [x] Étape 7 : Tester en conditions réelles (inscription, connexion, mot de passe oublié, envoi du code)

## Résumé des modifications
- **inscription.php** : Le compte est créé immédiatement avec `email_verified=1`. Plus de token de vérification, plus d'appel à `sendVerificationEmail()`.
- **connexion.php** : Plus de vérification de `email_verified` lors de la connexion. Suppression du bloc `resend_verification`. Suppression de l'include de `mail_config.php`.
- **verification.php** : Fichier supprimé (devenu inutile).
- **includes/functions.php** : Suppression des fonctions `generateSecureToken()`, `canResendEmail()`, `markEmailSent()`, `getResendCooldownMessage()`.
- **includes/mail_config.php** : Suppression de `sendVerificationEmail()` et `getBaseUrl()`.
- **_test_mail.php** : Fichier supprimé (fichier de test devenu inutile).
- **mdp_oublie.php / verifier_code.php / reinitialiser_mdp.php** : Inchangés — le flux mot de passe oublié (code 6 chiffres, 15 min, 5 tentatives, renvoi 60s) est conservé.

## Vérifications
- Tous les fichiers modifiés passent la validation syntaxique PHP (`php -l`).
- Aucune référence restante à `sendVerificationEmail`, `generateSecureToken`, `canResendEmail`, `markEmailSent`, `getResendCooldownMessage`, `resend_verification`, `verification.php`, `verification_token` ou `getBaseUrl` dans le code.
- La colonne `email_verified` est toujours présente dans la table `users` (pour compatibilité), mais n'est plus utilisée pour bloquer la connexion.

