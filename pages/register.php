<?php
/**
 * Page d'Inscription
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

$error = '';
$success = '';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validation
    if (empty($username)) {
        $error = 'Le nom d\'utilisateur est requis';
    } elseif (empty($email)) {
        $error = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'L\'email n\'est pas valide';
    } elseif (empty($password)) {
        $error = 'Le mot de passe est requis';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (empty($first_name) || empty($last_name)) {
        $error = 'Le prénom et le nom sont requis';
    } else {
        try {
            // Vérifier si l'utilisateur existe déjà
            $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Cet email ou nom d\'utilisateur est déjà utilisé';
            } else {
                // Hacher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insérer le nouvel utilisateur
                $insert_query = "
                    INSERT INTO users (username, email, password, first_name, last_name, phone, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ";
                
                $stmt = $db->prepare($insert_query);
                $stmt->bind_param('ssssss', $username, $email, $hashed_password, $first_name, $last_name, $phone);
                
                if ($stmt->execute()) {
                    $success = 'Inscription réussie! Vous pouvez maintenant <a href="login.php" style="color: #28a745; text-decoration: underline;">vous connecter</a>';
                    // Réinitialiser le formulaire
                    $username = $email = $first_name = $last_name = $password = $password_confirm = $phone = '';
                } else {
                    $error = 'Erreur lors de l\'inscription: ' . $db->error;
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3>Créer un compte</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; // HTML is allowed in success message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            </div>
                             <div class="mb-3">
                                <label for="phone" class="form-label">Téléphone (Optionnel)</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimum 6 caractères.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">S'inscrire</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Vous avez déjà un compte? <a href="login.php">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- End main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
