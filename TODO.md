# TODO — Suppression du flux « Mot de passe oublié » (vérification par code)

## Objectif
1. Supprimer définitivement le flux de réinitialisation de mot de passe par code e-mail (`mdp_oublie.php`, `verifier_code.php`, `reinitialiser_mdp.php`, `includes/mail_config.php`).
2. Garder uniquement la connexion simple (email + mot de passe).
3. Nettoyer le code inutilisé dans `includes/functions.php`.
4. Vérifier qu'il ne reste aucune référence cassée ni erreur PHP.

## Étapes
- [x] Étape 1 : Créer ce TODO.md avec le plan
- [x] Étape 2 : Supprimer `mdp_oublie.php`, `verifier_code.php`, `reinitialiser_mdp.php`, `includes/mail_config.php`
- [x] Étape 3 : Retirer le lien « Mot de passe oublié ? » dans `connexion.php`
- [x] Étape 4 : Nettoyer `includes/functions.php` (retirer `generateNumericCode` et `emailExists`)
- [x] Étape 5 : Vérifier qu'il ne reste aucune référence aux fichiers supprimés
- [x] Étape 6 : Validation PHP (php -l) sur les fichiers modifiés

