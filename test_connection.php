<?php
/**
 * Page de test de connexion à la base de données
 * Utilisée pour vérifier que la configuration est correcte
 */

require_once 'includes/db_connect.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connexion Base de Données</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .status {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }
        
        .status.success {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .status.error {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        
        .icon {
            font-size: 24px;
            font-weight: bold;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #0c5aa0;
        }
        
        .info-box h3 {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .info-box code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .next-steps {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            color: #856404;
        }
        
        .next-steps h3 {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .next-steps ol {
            margin-left: 20px;
        }
        
        .next-steps li {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .tables-list {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .tables-list h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 16px;
        }
        
        .tables-list ul {
            list-style-position: inside;
            margin-left: 0;
        }
        
        .tables-list li {
            padding: 5px;
            color: #555;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5568d3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Test de Connexion à la Base de Données</h1>
        
        <?php
        // Vérifier la connexion
        if (isset($conn) && $conn instanceof mysqli) {
            ?>
            <div class="status success">
                <span class="icon">✓</span>
                <div>
                    <strong>Connexion réussie!</strong>
                    <br>La connexion à la base de données est opérationnelle.
                </div>
            </div>
            
            <div class="info-box">
                <h3>📊 Informations de Connexion</h3>
                <p><strong>Serveur:</strong> <code><?php echo DB_HOST; ?></code></p>
                <p><strong>Utilisateur:</strong> <code><?php echo DB_USER; ?></code></p>
                <p><strong>Base de données:</strong> <code><?php echo DB_NAME; ?></code></p>
                <p><strong>Port:</strong> <code><?php echo DB_PORT; ?></code></p>
                <p><strong>Charset:</strong> <code><?php echo DB_CHARSET; ?></code></p>
                <p><strong>Version MySQL:</strong> <code><?php echo $conn->server_info; ?></code></p>
            </div>
            
            <?php
            // Vérifier les tables
            $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                ?>
                <div class="tables-list">
                    <h3>📋 Tables de la base de données:</h3>
                    <ul>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo "<li>" . $row['TABLE_NAME'] . "</li>";
                        }
                        ?>
                    </ul>
                </div>
                <?php
            } else {
                ?>
                <div class="info-box" style="background-color: #fff3cd; border-color: #ffc107; color: #856404;">
                    <h3>⚠️ Attention</h3>
                    <p>La base de données est vide. Vous devez importer les tables.</p>
                    <p style="margin-top: 10px;">Suivez les étapes du README.md pour importer le fichier <code>database/sql_schema.sql</code></p>
                </div>
                <?php
            }
            ?>
            
            <div class="next-steps">
                <h3>📝 Prochaines étapes:</h3>
                <ol>
                    <li>Consultez le fichier <strong>README.md</strong> pour plus de détails</li>
                    <li>Importez les tables à partir de <strong>database/sql_schema.sql</strong></li>
                    <li>Consultez phpMyAdmin sur <strong>http://localhost/phpmyadmin</strong></li>
                    <li>Accédez à l'application sur <strong>http://localhost/covoiturage/</strong></li>
                </ol>
            </div>
            
            <?php
        } else {
            ?>
            <div class="status error">
                <span class="icon">✗</span>
                <div>
                    <strong>Erreur de connexion!</strong>
                    <br>Impossible de se connecter à la base de données.
                </div>
            </div>
            
            <div class="info-box" style="background-color: #f8d7da; border-color: #dc3545; color: #721c24;">
                <h3>🔧 Dépannage:</h3>
                <p>1. Vérifiez que WAMP Server est lancé</p>
                <p>2. Vérifiez que le service MySQL est actif (point vert)</p>
                <p>3. Vérifiez les paramètres de connexion dans <code>includes/db_connect.php</code></p>
                <p>4. Assurez-vous que la base de données <code><?php echo DB_NAME; ?></code> existe</p>
            </div>
            
            <?php
        }
        ?>
        
        <div class="button-group">
            <a href="http://localhost/phpmyadmin" class="btn btn-primary">📊 Ouvrir phpMyAdmin</a>
            <a href="./" class="btn btn-secondary">🏠 Accueil</a>
        </div>
    </div>
</body>
</html>
