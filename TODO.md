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

---

# TODO — Correction « Unknown column 'c.etudiant_id' » (migration candidatures)

## Problème
- La table `candidatures` existait en base avec un ANCIEN schéma sans colonne `etudiant_id`.
- `CREATE TABLE IF NOT EXISTS` ne modifie pas une table existante → erreurs :
  - `profil.php:56` — `WHERE c.etudiant_id = ?`
  - `details_offre.php:61` — `WHERE offre_id = ? AND etudiant_id = ?`
  - `candidatures.php` — jointure `users u ON u.id = c.etudiant_id`

## Correctif appliqué
- [x] Ajout d'une **migration auto-réparatrice** dans `config.php` :
  - `SHOW COLUMNS FROM candidatures` pour inspecter le schéma réel.
  - Si `etudiant_id` manque → renommage de `user_id` si présent, sinon `ADD COLUMN etudiant_id`.
  - Garantie de l'index `idx_etudiant`.
- [x] Validation PHP (`php -l`) : aucune erreur de syntaxe.


