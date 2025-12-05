<?php
/**
 * Fichier de configuration et connexion à la base de données MySQL
 * 
 * Paramètres WAMP par défaut:
 * - Host: localhost
 * - User: root
 * - Password: (vide)
 * - Port: 3306
 */

// Définir les constantes de connexion
define('DB_HOST', 'localhost');      // Adresse du serveur MySQL
define('DB_USER', 'root');           // Utilisateur MySQL (root par défaut dans WAMP)
define('DB_PASS', '');               // Mot de passe (vide par défaut dans WAMP)
define('DB_NAME', 'covoiturage_db'); // Nom de la base de données
define('DB_PORT', 3306);             // Port MySQL
define('DB_CHARSET', 'utf8mb4');     // Charset UTF8

// Création de la connexion MySQLi
try {
    $db = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );
    
    // Vérifier la connexion
    if ($db->connect_error) {
        throw new Exception("Connexion échouée: " . $db->connect_error);
    }
    
    // Définir le charset UTF8MB4 (important pour supporter les caractères spéciaux)
    if (!$db->set_charset(DB_CHARSET)) {
        throw new Exception("Erreur lors de la définition du charset: " . $db->error);
    }
    
    // Optionnel: Activer les rapports d'erreurs en développement
    // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
} catch (Exception $e) {
    // En cas d'erreur, afficher le message et arrêter
    error_log("Erreur de connexion DB: " . $e->getMessage());
    die("<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px;'>
        <strong>Erreur de connexion:</strong> " . $e->getMessage() . "
        <br><small>Assurez-vous que WAMP est lancé et que la base de données existe.</small>
    </div>");
}

/**
 * Fonction de fermeture de la connexion
 * À appeler à la fin des scripts ou avant exit()
 */
function closeDatabase() {
    global $db;
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}

// Fermer la connexion à la fin du script
register_shutdown_function('closeDatabase');

?>
