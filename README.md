# Marketplace de Covoiturage en Ligne

Une application web permettant aux utilisateurs de créer et gérer des trajets de covoiturage.

## 📋 Table des matières

- [Configuration Requise](#configuration-requise)
- [Installation](#installation)
- [Configuration de la Base de Données](#configuration-de-la-base-de-données)
- [Connexion MySQL](#connexion-mysql)
- [Structure de la Base de Données](#structure-de-la-base-de-données)
- [Démarrage](#démarrage)

## 🔧 Configuration Requise

- **WAMP Server** (Windows Apache MySQL PHP)
- **PHP** 7.4 ou supérieur
- **MySQL** 5.7 ou supérieur
- **Apache** 2.4 ou supérieur
- Navigateur web moderne (Chrome, Firefox, Edge, Safari)

## 📦 Installation

### 1. Cloner/Copier le projet

```bash
# Le projet est déjà situé à:
c:\wamp64\www\covoiturage
```

### 2. Démarrer WAMP Server

- Ouvrez l'application WAMP
- Attendez que les icônes deviennent vertes (tous les services actifs)
- Vérifiez que le serveur Apache et MySQL sont lancés

### 3. Accéder à phpMyAdmin

```
http://localhost/phpmyadmin
```

## 🗄️ Configuration de la Base de Données

### Étape 1: Créer la base de données

#### Option A: Via phpMyAdmin (Interface GUI)

1. Ouvrez phpMyAdmin: `http://localhost/phpmyadmin`
2. Cliquez sur l'onglet **"Bases de données"** ou **"Databases"**
3. Dans le champ "Créer une nouvelle base de données", entrez: `covoiturage_db`
4. Sélectionnez le classement: `utf8mb4_unicode_ci`
5. Cliquez sur **"Créer"**

#### Option B: Via SQL (Ligne de commande)

1. Ouvrez phpMyAdmin
2. Cliquez sur l'onglet **"SQL"**
3. Collez le code SQL suivant:

```sql
CREATE DATABASE IF NOT EXISTS `covoiturage_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
USE `covoiturage_db`;
```

4. Cliquez sur **"Exécuter"** ou appuyez sur **Ctrl+Entrée**

### Étape 2: Importer les tables

1. Dans phpMyAdmin, sélectionnez la base de données `covoiturage_db`
2. Cliquez sur l'onglet **"Importer"**
3. Cliquez sur **"Parcourir"** et sélectionnez le fichier: `database/sql_schema.sql`
4. Cliquez sur **"Exécuter"**

Les tables seront créées automatiquement.

## 🔌 Connexion MySQL

### Paramètres de Connexion (WAMP)

```
Hôte (Host):        localhost
Utilisateur:        root
Mot de passe:       (vide par défaut)
Base de données:    covoiturage_db
Port:               3306
Charset:            utf8mb4
```

### Fichier de Configuration: `includes/db_connect.php`

```php
<?php
// Paramètres de connexion WAMP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Vide par défaut dans WAMP
define('DB_NAME', 'covoiturage_db');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Connexion à la base de données
try {
    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        throw new Exception("Connexion échouée: " . $conn->connect_error);
    }
    
    // Définir le charset UTF8MB4
    $conn->set_charset(DB_CHARSET);
    
} catch (Exception $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
```

### Utilisation dans les pages PHP

```php
<?php
// Inclure la connexion
require_once 'includes/db_connect.php';

// Utiliser la connexion
$query = "SELECT * FROM users";
$result = $conn->query($query);

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Traiter les données
    }
}

// Fermer la connexion à la fin
$conn->close();
?>
```

## 🗂️ Structure de la Base de Données

### Tables principales

#### 1. **users** - Utilisateurs
```
id (INT PRIMARY KEY AUTO_INCREMENT)
username (VARCHAR 100 UNIQUE)
email (VARCHAR 255 UNIQUE)
password (VARCHAR 255 - HASHED)
phone (VARCHAR 20)
profile_picture (VARCHAR 255)
location (VARCHAR 255)
bio (TEXT)
rating (FLOAT)
reviews_count (INT)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
status (ENUM: active, inactive, banned)
```

#### 2. **rides** - Trajets
```
id (INT PRIMARY KEY AUTO_INCREMENT)
driver_id (INT FOREIGN KEY -> users.id)
origin (VARCHAR 255)
destination (VARCHAR 255)
departure_date (DATETIME)
arrival_date (DATETIME)
seats_available (INT)
price_per_seat (DECIMAL 10,2)
vehicle_type (VARCHAR 100)
description (TEXT)
status (ENUM: active, cancelled, completed)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

#### 3. **bookings** - Réservations
```
id (INT PRIMARY KEY AUTO_INCREMENT)
ride_id (INT FOREIGN KEY -> rides.id)
passenger_id (INT FOREIGN KEY -> users.id)
seats_booked (INT)
total_price (DECIMAL 10,2)
booking_status (ENUM: pending, confirmed, cancelled, completed)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

#### 4. **reviews** - Avis et commentaires
```
id (INT PRIMARY KEY AUTO_INCREMENT)
from_user_id (INT FOREIGN KEY -> users.id)
to_user_id (INT FOREIGN KEY -> users.id)
ride_id (INT FOREIGN KEY -> rides.id)
rating (INT 1-5)
comment (TEXT)
created_at (TIMESTAMP)
```

#### 5. **messages** - Messagerie
```
id (INT PRIMARY KEY AUTO_INCREMENT)
sender_id (INT FOREIGN KEY -> users.id)
recipient_id (INT FOREIGN KEY -> users.id)
message (TEXT)
is_read (BOOLEAN)
created_at (TIMESTAMP)
```

## 🚀 Démarrage

### 1. Démarrer les services WAMP

- Assurez-vous que WAMP est lancé (icône verte)

### 2. Accéder à l'application

Ouvrez votre navigateur et allez à:

```
http://localhost/covoiturage/
```

### 3. Vérifier la connexion

Pour vérifier que la connexion MySQL fonctionne:

1. Ouvrez `http://localhost/covoiturage/test_connection.php`
2. Vous devriez voir: "✓ Connexion à la base de données réussie!"

## 📝 Gestion de la Base de Données

### Voir les tables via phpMyAdmin

1. Ouvrez: `http://localhost/phpmyadmin`
2. Cliquez sur `covoiturage_db` dans le menu de gauche
3. Vous verrez la liste de toutes les tables

### Réinitialiser la base de données

```sql
DROP DATABASE covoiturage_db;
CREATE DATABASE covoiturage_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

Puis ré-importer le fichier `database/sql_schema.sql`

## 🔐 Recommandations de Sécurité

### En Production

⚠️ **IMPORTANT**: Les paramètres par défaut ne sont PAS sécurisés pour la production!

1. **Créer un utilisateur MySQL dédié**
   ```sql
   CREATE USER 'covoiturage'@'localhost' IDENTIFIED BY 'motdepasse_securise';
   GRANT ALL PRIVILEGES ON covoiturage_db.* TO 'covoiturage'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Utiliser les variables d'environnement**
   ```php
   define('DB_USER', getenv('DB_USER'));
   define('DB_PASS', getenv('DB_PASS'));
   ```

3. **Utiliser les prepared statements**
   ```php
   $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
   $stmt->bind_param("s", $email);
   $stmt->execute();
   ```

4. **Valider et nettoyer les entrées**
   ```php
   $input = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   ```

## 🐛 Dépannage

### Erreur: "Connexion échouée: Lost connection to MySQL server"

- Vérifiez que WAMP est lancé
- Vérifiez que MySQL est actif (point vert dans WAMP)
- Redémarrez le service MySQL

### Erreur: "Base de données n'existe pas"

- Vérifiez que vous avez exécuté l'étape de création de la base de données
- Vérifiez le nom de la base de données dans `db_connect.php`

### Erreur: "Access denied for user 'root'"

- Le mot de passe est probablement incorrect
- Par défaut dans WAMP, root n'a pas de mot de passe (laissez vide)

### Erreur: "Charset UTF8MB4 not supported"

- Vérifiez votre version de MySQL (doit être 5.5.3+)
- Changez `utf8mb4` par `utf8` si nécessaire

## 📞 Support

Pour plus d'informations:
- [Documentation WAMP](http://www.wampserver.com/)
- [Documentation MySQL](https://dev.mysql.com/doc/)
- [Documentation PHP](https://www.php.net/manual/)

## 📄 Licence

Projet personnel

---

**Dernière mise à jour**: Octobre 2025
**Version**: 1.0
