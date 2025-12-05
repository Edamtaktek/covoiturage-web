# 🔐 Guide d'Authentification - CovoiturageApp

## 🎯 Vue d'Ensemble

Le système d'authentification est maintenant complet avec:
- ✅ Inscription (register.php)
- ✅ Connexion (login.php)
- ✅ Déconnexion (logout.php)
- ✅ Tableau de bord (dashboard.php)
- ✅ Nouveau design CSS moderne

---

## 📝 Inscription

### URL: `http://localhost/covoiturage/pages/register.php`

### Étapes:
1. Entrez votre prénom et nom
2. Entrez votre email
3. Choisissez un nom d'utilisateur
4. Entrez votre téléphone (optionnel)
5. Entrez un mot de passe (minimum 6 caractères)
6. Confirmez le mot de passe
7. Cliquez "S'inscrire"

### Validations:
- ✅ Tous les champs requis sauf le téléphone
- ✅ Email valide
- ✅ Mot de passe minimum 6 caractères
- ✅ Les mots de passe doivent correspondre
- ✅ Vérification des doublons (email/username)

### Résultat:
- Succès → Vous pouvez vous connecter
- Erreur → Message d'erreur affiché

---

## 🔑 Connexion

### URL: `http://localhost/covoiturage/pages/login.php`

### Étapes:
1. Entrez votre email
2. Entrez votre mot de passe
3. Optionnel: Cochez "Se souvenir de moi"
4. Cliquez "Se Connecter"

### Résultat:
- Succès → Redirection vers le dashboard
- Erreur → Message d'erreur affiché

---

## 📊 Tableau de Bord

### URL: `http://localhost/covoiturage/pages/dashboard.php`

### Accès:
- ✅ Automatiqu après connexion
- ✅ Protégé (redirection vers login si non connecté)

### Sections:
1. **Profil**
   - Affiche votre profil complet
   - Rating et nombre d'avis
   - Bouton "Modifier le Profil"

2. **Mes Réservations**
   - Trajets que vous avez réservés
   - Informations du conducteur
   - Statut de la réservation
   - Prix total

3. **Mes Trajets Proposés**
   - Trajets que vous avez créés
   - Places disponibles
   - Prix par place
   - Actions: Voir, Éditer

4. **Actions Rapides**
   - Proposer un trajet
   - Chercher un trajet

---

## 🚪 Déconnexion

### URL: `http://localhost/covoiturage/pages/logout.php`

### Comment:
1. Cliquez "Déconnexion" dans le dashboard
2. Vous serez redirigé vers l'accueil
3. Votre session sera détruite

---

## 🎨 Nouveau Design CSS

### Palette de Couleurs:
- **Bleu (Primaire)**: #0066ff
- **Cyan (Secondaire)**: #00d4ff
- **Rose (Accent)**: #ff0066
- **Vert (Succès)**: #00c853
- **Rouge (Danger)**: #ff3b30

### Composants Stylisés:
✅ Formulaires modernes
✅ Alertes colorées
✅ Boutons avec gradients
✅ Dashboard épuré
✅ Cartes avec ombres

---

## 🔒 Sécurité

### Implémenté:
✅ Hachage sécurisé des mots de passe (BCRYPT)
✅ Vérification des sessions
✅ Validation des données
✅ Prepared statements
✅ Sanitization des entrées

### Bonnes Pratiques:
- Ne jamais stocker les mots de passe en clair
- Toujours utiliser HTTPS en production
- Valider côté serveur et côté client
- Utiliser prepared statements pour les requêtes SQL

---

## 🧪 Test Rapide

### 1. Tester l'Inscription:
```
1. Allez à: http://localhost/covoiturage/pages/register.php
2. Remplissez le formulaire
3. Cliquez "S'inscrire"
4. Vérifiez le message de succès
```

### 2. Tester la Connexion:
```
1. Allez à: http://localhost/covoiturage/pages/login.php
2. Entrez l'email et le mot de passe
3. Cliquez "Se Connecter"
4. Vous devez être redirigé vers le dashboard
```

### 3. Tester le Dashboard:
```
1. Vérifiez que votre profil s'affiche
2. Vérifiez les sections (réservations, trajets)
3. Cliquez sur "Déconnexion"
4. Vous devez être redirigé vers l'accueil
```

### 4. Tester la Protection:
```
1. Allez directement à: dashboard.php
2. Si non connecté → redirection vers login.php
3. Si connecté → affichage du dashboard
```

---

## 📋 Codes de Statut

### Réservations:
- **pending**: En attente de confirmation
- **confirmed**: Confirmée
- **cancelled**: Annulée
- **completed**: Complétée

### Trajets:
- **active**: Actif (disponible)
- **completed**: Complété
- **cancelled**: Annulé

### Utilisateurs:
- **active**: Actif
- **inactive**: Inactif
- **banned**: Banni

---

## ⚠️ Messages d'Erreur Courants

### "L'email ou le nom d'utilisateur est déjà utilisé"
**Solution:** Utilisez un email ou username différent

### "Les mots de passe ne correspondent pas"
**Solution:** Assurez-vous que les deux mots de passe sont identiques

### "Le mot de passe doit contenir au moins 6 caractères"
**Solution:** Utilisez un mot de passe plus long

### "Email ou mot de passe incorrect"
**Solution:** Vérifiez vos identifiants

### "Vous n'avez pas les permissions pour accéder à cette page"
**Solution:** Connectez-vous d'abord

---

## 📱 Responsive Design

Le design s'adapte automatiquement à:
- **Mobile** (< 480px): Layout empilé
- **Tablet** (480px - 768px): Layout flexible
- **Desktop** (> 768px): Layout complet

---

## 🔄 Flow de Navigation

```
index.php (Accueil)
├─ Bouton "Inscription" → pages/register.php
└─ Bouton "Connexion" → pages/login.php

pages/register.php (Inscription)
├─ "S'inscrire" → Crée un compte
├─ "Se connecter" → pages/login.php
└─ "Retour" → index.php

pages/login.php (Connexion)
├─ "Se Connecter" → pages/dashboard.php (si succès)
├─ "S'inscrire" → pages/register.php
└─ "Retour" → index.php

pages/dashboard.php (Tableau de Bord)
├─ "Proposer un trajet" → pages/create_ride.php (à créer)
├─ "Chercher un trajet" → pages/search_rides.php (à créer)
├─ "Modifier le Profil" → pages/profile.php (à créer)
├─ "Déconnexion" → pages/logout.php
└─ "Accueil" → index.php
```

---

## 💡 Conseils

1. **Changez le mot de passe régulièrement**
2. **N'utilisez pas le même mot de passe partout**
3. **Sauvegardez votre adresse email**
4. **Mettez à jour votre profil**
5. **Signalez les problèmes de sécurité**

---

## 🚀 Prochaines Étapes

À développer:
- [ ] Page profil utilisateur
- [ ] Créer/éditer trajets
- [ ] Recherche de trajets
- [ ] Réservation de trajets
- [ ] Système d'avis
- [ ] Messagerie

---

**Version:** 1.0  
**Date:** Octobre 2025  
**Status:** ✅ Authentification Complète

Bon développement! 🎉
