<?php
// Test de chargement des fichiers necessaires

echo "<h2>Test de Chargement des Fichiers</h2>";

// Test 1: db_connect.php
echo "<h3>1. db_connect.php</h3>";
if (file_exists('includes/db_connect.php')) {
    echo "<p style='color: green;'>OK - Fichier existe</p>";
    @require_once 'includes/db_connect.php';
    if (isset($db)) {
        echo "<p style='color: green;'>OK - Variable \$db initialisee</p>";
    } else {
        echo "<p style='color: red;'>ERREUR - Variable \$db non trouvee</p>";
    }
} else {
    echo "<p style='color: red;'>ERREUR - Fichier non trouve</p>";
}

// Test 2: config.php
echo "<h3>2. config.php</h3>";
if (file_exists('includes/config.php')) {
    echo "<p style='color: green;'>OK - Fichier existe</p>";
    @require_once 'includes/config.php';
    if (function_exists('isLoggedIn')) {
        echo "<p style='color: green;'>OK - Fonctions chargees</p>";
    } else {
        echo "<p style='color: red;'>ERREUR - Fonctions non trouvees</p>";
    }
} else {
    echo "<p style='color: red;'>ERREUR - Fichier non trouve</p>";
}

// Test 3: Base de donnees
echo "<h3>3. Connexion Base de Donnees</h3>";
if (isset($db)) {
    if ($db->connect_error) {
        echo "<p style='color: red;'>ERREUR - " . $db->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>OK - Connecte a la base de donnees</p>";
        
        // Tester une requete simple
        $result = $db->query("SELECT 1 as test");
        if ($result) {
            echo "<p style='color: green;'>OK - Requete execute avec succes</p>";
        } else {
            echo "<p style='color: red;'>ERREUR - " . $db->error . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>ERREUR - Base de donnees non initialisee</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Retour a l'accueil</a></p>";
?>
