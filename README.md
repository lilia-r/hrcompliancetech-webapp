# HRComplianceTech — Webapp

Application web de gestion des signalements internes en entreprise, conforme à la loi Sapin 2 et au RGPD.
Développée dans le cadre de l'épreuve E6 du BTS SIO option SLAM — 2025-2026.

---

## 1. Présentation du projet

HRComplianceTech permet aux entreprises de gérer leurs signalements internes (harcèlement, corruption, discrimination) de façon sécurisée et confidentielle.

| Informations | Détails                                              |
| ------------ | ---------------------------------------------------- |
| Entreprise   | HRComplianceTech Solutions                           |
| Client       | Mme Caroline Morel — Responsable Produits Conformité |
| Cadre        | BTS SIO SLAM — Épreuve E6                            |
| Période      | Novembre 2025 — Avril 2026                           |
| Version      | 1.0 — Prototype fonctionnel                          |

---

## 2. Stack technique

| Composant       | Technologie                                  |
| --------------- | -------------------------------------------- |
| Frontend        | HTML5, CSS3, Vanilla JavaScript, Bootstrap 5 |
| Backend         | PHP 8 natif (PDO, sessions, password_hash)   |
| Base de données | MySQL — InnoDB                               |
| API REST        | PHP — Classification par mots-clés pondérés  |
| Sécurité        | BCRYPT, PDO requêtes préparées               |
| Polices         | Playfair Display + DM Sans (Google Fonts)    |
| Serveur local   | XAMPP (Apache + PHP + MySQL)                 |
| Versioning      | GitHub                                       |

---

## 3. Prérequis

- XAMPP avec Apache et MySQL démarrés
- PHP 8.0 minimum
- Navigateur moderne (Chrome, Firefox, Edge)
- phpMyAdmin pour administrer la base de données

---

## 4. Installation

### 4.1 Déposer le projet

```
C:\xampp\htdocs\hrcompliancetech\
```

### 4.2 Créer la base de données

1. Ouvrir phpMyAdmin : http://localhost/phpmyadmin
2. Créer une base nommée : `hr_compliance_db`
3. Importer les fichiers SQL dans cet ordre :

```
hr_compliance_db.sql
annotations_juriste.sql
responsable_signalement.sql
lien_utilisateur_signalement.sql
priorite_trois_niveaux.sql
nouveaux_utilisateurs.sql
```

### 4.3 Configurer la connexion

Ouvrir `config/config.php` et vérifier :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hr_compliance_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4.4 Créer le dossier uploads

```
C:\xampp\htdocs\hrcompliancetech\uploads\
```

### 4.5 Accéder à l'application

```
http://localhost/hrcompliancetech/
```

---

## 5. Structure du projet

```
hrcompliancetech/
├── api/
│   └── api-priorite.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── auth/
│   ├── auth.php
│   └── connexion.html
├── config/
│   └── config.php
├── dashboards/
│   ├── admin.php
│   ├── dashboard-rh.php
│   ├── dashboard-juriste.php
│   └── dashboard-salarie.php
├── includes/
│   ├── fonctions.php
│   └── deconnexion.php
├── modules/
│   ├── admin/
│   │   ├── parametres.php
│   │   └── sauvegardes.php
│   ├── audit/
│   │   └── logs-audit.php
│   ├── dossier/
│   │   ├── dossier.php
│   │   └── telecharger.php
│   ├── messagerie/
│   │   └── message-ajax.php
│   └── signalement/
│       ├── signalement.php
│       ├── traitement-signalement.php
│       └── confirmation.php
├── uploads/
├── index.html
└── mentions-legales.html
```

---

## 6. Comptes de test

### WebApp — Authentification BCRYPT

| Rôle    | Email                                  | Mot de passe     |
| ------- | -------------------------------------- | ---------------- |
| Admin   | jeanette.marceau@hrcompliancetech.fr   | ******\*\******* |
| RH      | raymond.margand@hrcompliancetech.fr    | ******\*\******* |
| RH      | damiane.chnadonnet@hrcompliancetech.fr | ******\*\******* |
| Juriste | vick.faucher@hrcompliancetech.fr       | ******\*\******* |
| Juriste | paul.patry@hrcompliancetech.fr         | ******\*\******* |
| Salarié | hugh.guernon@hrcompliancetech.fr       | ******\*\******* |
| Salarié | gradasso.mailly@hrcompliancetech.fr    | ******\*\******* |
| Salarié | ernest.lacombe@hrcompliancetech.fr     | ******\*\******* |
| Salarié | dorothee.bordeaux@hrcompliancetech.fr  | ******\*\******* |
| Salarié | gregoire.garceau@hrcompliancetech.fr   | ******\*\******* |

### Windev — Application bureautique

| Rôle    | Email                                   | Mot de passe     |
| ------- | --------------------------------------- | ---------------- |
| Admin   | thomas.bernard@hrcompliancetech.fr      | ******\*\******* |
| RH      | claire.moreau@hrcompliancetech.fr       | ******\*\******* |
| RH      | laurence.austin@hrcompliancetech.fr     | ******\*\******* |
| Juriste | pierre.durand@hrcompliancetech.fr       | ******\*\******* |
| Juriste | harry.laforest@hrcompliancetech.fr      | ******\*\******* |
| Salarié | marie.dupont@hrcompliancetech.fr        | ******\*\******* |
| Salarié | william.charpentier@hrcompliancetech.fr | ******\*\******* |
| Salarié | agnes.beaulac@hrcompliancetech.fr       | ******\*\******* |
| Salarié | olivier.pepin@hrcompliancetech.fr       | ******\*\******* |
| Salarié | jose.jimenez@hrcompliancetech.fr        | ******\*\******* |
| Salarié | xavier.deserres@hrcompliancetech.fr     | ******\*\******* |

---

## 7. Fonctionnalités

### Salarié

- Dépôt de signalement anonyme ou identifié avec pièces jointes
- Tableau de bord avec suivi de ses dossiers
- Messagerie confidentielle
- Ajout de pièces jointes après dépôt

### RH

- Tableau de bord avec filtres (statut, priorité, catégorie) et tri par date
- Traitement des dossiers et gestion des statuts
- Attribution d'un responsable
- Messagerie anonyme et téléchargement des pièces jointes

### Juriste

- Toutes les fonctionnalités RH
- Annotations légales
- Validation de clôture des dossiers

### Administrateur

- Gestion des utilisateurs (suspendre / réactiver)
- Journal d'audit complet
- Sauvegardes et paramètres

### API Pseudo-IA

- Classification automatique de la priorité (haute / normale / basse)
- Génération de réponse type selon la catégorie
- Insertion automatique dans la messagerie du dossier

---

## 8. Sécurité & conformité

- Mots de passe hashés en BCRYPT via `password_hash()`
- Requêtes SQL préparées (PDO) — protection injections SQL
- Contrôle d'accès par rôle via `exigerConnexion()` sur chaque page
- Journal d'audit horodaté pour toutes les actions sensibles
- Anonymat optionnel — `masquer_identite` stocké en BDD
- Conservation des données limitée à 2 ans après clôture (RGPD)
- Pièces jointes servies via `telecharger.php` de façon sécurisée
- Conformité loi Sapin 2 — protection du lanceur d'alerte

---

## 9. Documentation API

**Endpoint :** `POST /api/api-priorite.php`

**Corps de la requête (JSON) :**

```json
{
  "description": "texte du signalement",
  "categorie": "harcelement"
}
```

**Réponse (JSON) :**

```json
{
  "priorite": "haute",
  "score": 18,
  "seuil_haute": 12,
  "seuil_normale": 5,
  "mots_detectes": ["harcelement moral", "pression", "intimidation"],
  "reponse_type": "Votre signalement a été reçu et marqué comme prioritaire..."
}
```

---

## 10. Conformité RGPD & Loi Sapin 2

| Point                       | Détail                                                         |
| --------------------------- | -------------------------------------------------------------- |
| Textes applicables          | RGPD (UE 2016/679), Loi Sapin 2 (n°2016-1691), Code du travail |
| Anonymat                    | Identité masquable via masquer_identite                        |
| Conservation                | 2 ans maximum après clôture                                    |
| Hébergement                 | Serveur européen en production                                 |
| Traçabilité                 | Journal d'audit inaltérable                                    |
| Protection lanceur d'alerte | Conformément aux articles 6 à 16 de la loi Sapin 2             |
