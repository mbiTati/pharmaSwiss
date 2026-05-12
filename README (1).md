# PharmaSwiss - Plateforme web BTEC Unit 4 LO2

Application web complète accompagnant l'exercice d'entraînement **Unit4_Entrainement1_LO2_PharmaSwiss**.

## Installation (Windows + WAMP/XAMPP)

### 1. Importer la base de données

Dans phpMyAdmin ou MySQL Workbench, exécuter dans l'ordre :

```
1. pharmaswiss_database.sql  (création BDD + données)
2. pharmaswiss_users.sql     (création des 3 utilisateurs MySQL)
```

> **Note** : le second script doit être exécuté en tant que `root` car il fait du `CREATE USER` et du `GRANT`.

### 2. Déployer les fichiers PHP

Copier tous les fichiers `.php` et `style.css` dans :
- WAMP : `C:\wamp64\www\pharmaswiss\`
- XAMPP : `C:\xampp\htdocs\pharmaswiss\`

### 3. Accéder à l'application

Ouvrir dans le navigateur : http://localhost/pharmaswiss/login.php

## Comptes de connexion

| Identifiant | Mot de passe | Rôle | Droits |
|-------------|--------------|------|--------|
| `assistant_pharmacie` | `AssistantP@ss2025` | Assistant | SELECT limité + INSERT ordonnances |
| `pharmacien_titulaire` | `TitulaireP@ss2025` | Pharmacien titulaire | Lecture + INSERT/UPDATE ordonnances, médicaments, patients |
| `administrateur_pharma` | `AdminP@ss2025` | Administrateur | Tous les droits |

## Structure des fichiers

| Fichier | Rôle |
|---------|------|
| `style.css` | Thème vert santé partagé |
| `connexion.php` | Helpers PDO + auth (require_once dans toutes les pages) |
| `login.php` | Formulaire de connexion (auth contre MySQL) |
| `logout.php` | Déconnexion + destruction session |
| `index.php` | Dashboard avec KPIs + recherche patient |
| `console.php` | Console SQL libre (200 lignes max, historique) |
| `patients.php` | Liste patients avec filtres (canton, assurance, allergies) |
| `medicaments.php` | Catalogue médicaments avec filtres (pharmacie, catégorie, stock) |
| `ordonnances.php` | Liste ordonnances + action "Délivrer" (test des permissions) |
| `rapports.php` | 5 rapports agrégés (CA pharmacie, ruptures, top catégories, allergies, performance pharmaciens) |

## Pédagogie

- **LO2/P2/P3** : les élèves utilisent la **console SQL** pour leurs requêtes
- **LO2/M2** : les **3 comptes** différents permettent de tester les permissions GRANT
- **LO2/M3** : la page **Rapports** illustre l'extraction d'informations de gestion
- **LO2/D2** : les **données piégées** (Augmentin délivré à un patient allergique pénicilline) servent de matériau pour la réflexion critique

## Mots-clés SQL bloqués par l'application

Pour des raisons de sécurité, les requêtes contenant ces mots-clés sont rejetées dans la console :
- `DROP DATABASE`, `DROP SCHEMA`
- `CREATE USER`, `DROP USER`
- `GRANT `, `REVOKE `
- `SHUTDOWN`

Tout le reste passe par les permissions MySQL natives.

---
*École Schulz - BTEC Unit 4 - 2025/2026*
