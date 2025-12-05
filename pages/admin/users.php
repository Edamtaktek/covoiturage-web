<?php
/**
 * Admin Users Management Page
 */
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Require admin access
requireAdmin();

$error_message = '';
$success_message = '';
$users = [];
$total_users = 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Token de sécurité invalide.";
    } elseif ($user_id <= 0) {
        $error_message = "Utilisateur invalide.";
    } else {
        try {
            switch ($action) {
                case 'activate':
                    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $success_message = "Utilisateur activé avec succès.";
                    break;
                    
                case 'deactivate':
                    // Don't allow deactivating yourself
                    if ($user_id == $_SESSION['user_id']) {
                        $error_message = "Vous ne pouvez pas désactiver votre propre compte.";
                    } else {
                        $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                        $stmt->bind_param('i', $user_id);
                        $stmt->execute();
                        $success_message = "Utilisateur désactivé avec succès.";
                    }
                    break;
                    
                case 'ban':
                    if ($user_id == $_SESSION['user_id']) {
                        $error_message = "Vous ne pouvez pas bannir votre propre compte.";
                    } else {
                        $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                        $stmt->bind_param('i', $user_id);
                        $stmt->execute();
                        $success_message = "Utilisateur banni avec succès.";
                    }
                    break;
                    
                case 'make_admin':
                    $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $success_message = "Utilisateur promu administrateur.";
                    break;
                    
                case 'remove_admin':
                    if ($user_id == $_SESSION['user_id']) {
                        $error_message = "Vous ne pouvez pas retirer vos propres droits d'administrateur.";
                    } else {
                        $stmt = $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                        $stmt->bind_param('i', $user_id);
                        $stmt->execute();
                        $success_message = "Droits d'administrateur retirés.";
                    }
                    break;
                    
                default:
                    $error_message = "Action non reconnue.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
}

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($filter_status && in_array($filter_status, ['active', 'inactive', 'banned'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }
    
    if ($search) {
        $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssss';
    }
    
    $where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total users
    $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
    if (count($params) > 0) {
        $stmt = $db->prepare($count_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_users = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total_users = $db->query($count_query)->fetch_assoc()['total'];
    }
    
    // Get users with pagination
    $users_query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $db->prepare($users_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
}

$total_pages = ceil($total_users / $per_page);
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- Admin Header -->
        <div class="admin-header">
            <h1>Gestion des Utilisateurs</h1>
            <p>Gérez tous les utilisateurs de la plateforme.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Rechercher</label>
                        <input type="text" name="search" class="form-control" placeholder="Nom, email, username..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                            <option value="banned" <?php echo $filter_status === 'banned' ? 'selected' : ''; ?>>Banni</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                        <a href="users.php" class="btn btn-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Utilisateurs (<?php echo $total_users; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($user['is_driver']): ?>
                                                <span class="badge bg-info">Conducteur</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Utilisateur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $user['status']; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateShort($user['created_at']); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($user['status'] !== 'active'): ?>
                                                        <li>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="activate">
                                                                <button type="submit" class="dropdown-item text-success">Activer</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <li>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="deactivate">
                                                                <button type="submit" class="dropdown-item text-warning">Désactiver</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['status'] !== 'banned'): ?>
                                                        <li>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="ban">
                                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Êtes-vous sûr de vouloir bannir cet utilisateur?')">Bannir</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <li><hr class="dropdown-divider"></li>
                                                    
                                                    <?php if (!$user['is_admin']): ?>
                                                        <li>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="make_admin">
                                                                <button type="submit" class="dropdown-item" onclick="return confirm('Promouvoir cet utilisateur comme administrateur?')">Promouvoir Admin</button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="remove_admin">
                                                                <button type="submit" class="dropdown-item" onclick="return confirm('Retirer les droits admin de cet utilisateur?')">Retirer Admin</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Précédent</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Suivant</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-4 mb-0">Aucun utilisateur trouvé.</p>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- End main-content -->

    <!-- Footer -->
    <footer class="py-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date("Y"); ?> Covoiturage - Administration. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="dashboard.php" class="text-decoration-none me-3">Tableau de Bord</a>
                    <a href="rides.php" class="text-decoration-none">Trajets</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
