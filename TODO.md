# TODO — Système de candidatures + Fix Menu Mobile

## Objectif
1. Corriger le menu hamburger sur mobile (icône X visible et cliquable).
2. Créer automatiquement la table `candidatures` (aucune création manuelle MySQL nécessaire).
3. Permettre aux étudiants de postuler à une offre (bouton « Postuler »).
4. Page `candidatures.php` où l'entreprise consulte et accepte/refuse les demandes.
5. Lien « Candidatures » dans le menu des entreprises + section « Mes candidatures » dans le profil étudiant.

## Étapes
- [x] Étape 1 : `config.php` — création automatique de la table `candidatures`
- [x] Étape 2 : `style.css` — corriger le z-index du header (X du menu visible/cliquable)
- [x] Étape 3 : `app.js` — basculer l'aria-label Ouvrir/Fermer
- [x] Étape 4 : `details_offre.php` — bouton « Postuler » pour les étudiants
- [x] Étape 5 : `candidatures.php` — nouvelle page de gestion des candidatures (entreprise)
- [x] Étape 6 : `profil.php` — section « Mes candidatures » pour l'étudiant
- [x] Étape 7 : Ajouter le lien « Candidatures » dans les menus entreprise (index, recherche_annuaire, details_offre, poster_offre, admin_validation, inscription, profil)
- [x] Étape 8 : Tester (validation PHP ✅ — tous les fichiers passent)

