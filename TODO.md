# TODO — Suppression candidatures étudiants + correctif bouton (X) menu mobile

## Objectif
1. Empêcher les étudiants de postuler : ils ne font que consulter les détails des offres.
2. Corriger le bouton (X) de fermeture du menu mobile.

## Étapes
- [x] Étape 1 : `details_offre.php` — supprimer le bloc PHP candidatures, l'affichage message/statut et le bouton « Postuler »
- [x] Étape 2 : `profil.php` — supprimer la section « Mes candidatures » côté étudiant
- [x] Étape 3 : `style.css` — corriger le z-index du `.nav-toggle` (110 → 210) pour que le X ferme le menu
- [x] Étape 4 : Validation PHP (`php -l`) sur les fichiers modifiés

