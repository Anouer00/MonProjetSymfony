# 📚 API de Gestion de Bibliothèque (Symfony 7.4)

**Auteur :** Anouer OUERGHI  
*Projet réalisé dans le cadre des TP Symfony.*

---

## 📌 État du projet (Branches)

L'avancement est séparé en deux branches pour plus de clarté :

* ✅ **`main`** : Contient tout le travail terminé des **TP 1 à 5** (L'API est fonctionnelle, testée et sécurisée).
* 🚧 **`tp7`** : Sera utilisée pour le développement spécifique du **TP 7**.

---

## 🚀 Ce que fait l'application

### 1. API Livres
Une API REST classique pour gérer une bibliothèque :
- **Listing** : Pagination et recherche intégrées (`/api/v1/books?q=Titre`).
- **CRUD** : Création, modification et suppression (protégées par rôles).
- **Architecture** : Utilisation de **DTOs** et d'un **Mapper** pour garder le code propre et ne pas exposer directement la base de données.

### 2. Sécurité
- **Auth** : Inscription, Login et Logout fonctionnels.
- **Vérifications** : Un système (`UserChecker`) empêche la connexion si l'email n'est pas vérifié ou si l'utilisateur est banni.
- **Tracking** : La date de dernière connexion (`lastLogin`) est mise à jour automatiquement.
- **Rôles** :
  - `ROLE_USER` : Gestion basique.
  - `ROLE_ADMIN` : Droit de suppression.

### 3. Les "plus" du projet
- **Fixtures** : Base de données pré-remplie avec 50 livres et des utilisateurs de test (via Faker).
- **Versioning** : Routes préfixées par `/api/v1`.
- **Validation** : Les données envoyées sont strictement contrôlées (ISBN unique, etc.).

---

## 🛠️ Choix Techniques

J'ai structuré le projet pour séparer la logique :
* **Entity** : Les données brutes (`User`, `Book`).
* **Controller/Api** : Réception des requêtes (reste léger).
* **Service & DTO** : Toute la logique de transformation des données se passe ici (`BookMapper`).
* **Stack** : Symfony 7.4, Doctrine, JWT (ou session), Nelmio (Swagger), PHPUnit.

---

## ⚙️ Comment lancer le projet

Prérequis : PHP 8.2+, Composer, MySQL.

1.  **Récupérer le code**
    ```bash
    git clone [https://github.com/Anouer00/MonProjetSymfony.git](https://github.com/Anouer00/MonProjetSymfony.git)
    cd MonProjetSymfony
    ```

2.  **Installer les libs**
    ```bash
    composer install
    ```

3.  **Configurer la BDD**
    *(Le `.env` est déjà configuré pour MariaDB local avec un timeout étendu)*
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

4.  **Charger les fausses données**
    Indispensable pour tester l'API tout de suite :
    ```bash
    php bin/console doctrine:fixtures:load --no-interaction
    ```

5.  **Démarrer**
    ```bash
    symfony server:start
    ```

---

## ✅ Tests

Les tests fonctionnels couvrent les endpoints principaux. Pour vérifier que tout est vert :

```bash
php bin/phpunit