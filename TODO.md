# Plan de Correction — Bug HTTP 500 Inscription

## ✅ Étape 1: Corriger `inscription.php` (TERMINÉ)
- [x] Corriger bind_param (15 types → 14 variables) → **CAUSE DU HTTP 500**
- [x] Remplacer htmlspecialchars($success) par $success
- [x] Remplacer htmlspecialchars($error) par $error
- [x] Ajouter error_log() pour toutes les erreurs SQL
- [x] Ajouter vérification d'erreur prepare() et execute()

## ✅ Étape 2: Corriger `includes/mail_config.php` (TERMINÉ)
- [x] Nouvelle fonction getBaseUrl() - construction URL fiable (sans `/..`)
- [x] Support variables d'environnement pour SMTP (Clever Cloud)
- [x] Mode debug via env var SMTP_DEBUG
- [x] Journalisation améliorée des erreurs SMTP

## ✅ Étape 3: Corriger `verification.php` (TERMINÉ)
- [x] Ajouter &email= dans le lien "Renvoyer l'email"

## ✅ Étape 4: Améliorer `connexion.php` (TERMINÉ)
- [x] Ajouter error_log() pour les échecs SQL
- [x] Ajouter vérification d'erreur prepare() et execute()

## ✅ Étape 5: Nettoyage (TERMINÉ)
- [x] Supprimer test_env.php

