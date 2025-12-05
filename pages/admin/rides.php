<?php
/**
 * Admin Rides Management Page
 */
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Require admin access
requireAdmin();

$error_message = '';
$success_message = '';
$rides = [];
$total_rides = 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Handle quick approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $ride_id = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Token de sécurité invalide.";
    } elseif ($ride_id <= 0) {
        $error_message = "Trajet invalide.";
    } else {
        try {
            switch ($action) {
                case 'quick_approve':
                    $stmt = $db->prepare("UPDATE rides SET approval_status = 'approved' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Trajet approuvé avec succès.";
                    break;
                    
                case 'quick_reject':
                    $stmt = $db->prepare("UPDATE rides SET approval_status = 'rejected', rejection_reason = 'Rejeté par l''administrateur' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Trajet rejeté.";
                    break;
                    
                case 'cancel':
                    $stmt = $db->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Trajet annulé.";
                    break;
                    
                case 'activate':
                    $stmt = $db->prepare("UPDATE rides SET status = 'active' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Trajet réactivé.";
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
    
    if ($filter) {
        if ($filter === 'pending') {
            $where_conditions[] = "r.approval_status = 'pending'";
        } elseif ($filter === 'approved') {
            $where_conditions[] = "r.approval_status = 'approved'";
        } elseif ($filter === 'rejected') {
            $where_conditions[] = "r.approval_status = 'rejected'";
        } elseif ($filter === 'active') {
            $where_conditions[] = "r.status = 'active'";
        } elseif ($filter === 'cancelled') {
            $where_conditions[] = "r.status = 'cancelled'";
        }
    }
    
    if ($search) {
        $where_conditions[] = "(r.origin LIKE ? OR r.destination LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssss';
    }
    
    $where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total rides
    $count_query = "SELECT COUNT(*) as total FROM rides r JOIN users u ON r.driver_id = u.id $where_clause";
    if (count($params) > 0) {
        $stmt = $db->prepare($count_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rides = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total_rides = $db->query($count_query)->fetch_assoc()['total'];
    }
    
    // Get rides with pagination
    $rides_query = "
        SELECT r.*, u.first_name, u.last_name, u.email as driver_email,
               (SELECT COUNT(*) FROM bookings WHERE ride_id = r.id) as booking_count
        FROM rides r
        JOIN users u ON r.driver_id = u.id
        $where_clause
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $db->prepare($rides_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des trajets: " . $e->getMessage();
}

$total_pages = ceil($total_rides / $per_page);
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Trajets - Administration</title>
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
            <h1>Gestion des Trajets</h1>
            <p>Gérez et approuvez les trajets soumis par les conducteurs.</p>
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
                        <input type="text" name="search" class="form-control" placeholder="Origine, destination, conducteur..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filtrer par</label>
                        <select name="filter" class="form-select">
                            <option value="">Tous les trajets</option>
                            <optgroup label="Approbation">
                                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approuvés</option>
                                <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejetés</option>
                            </optgroup>
                            <optgroup label="Statut">
                                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Actifs</option>
                                <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Annulés</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                        <a href="rides.php" class="btn btn-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Filter Buttons -->
        <div class="mb-4">
            <a href="rides.php" class="btn btn-outline-secondary <?php echo !$filter ? 'active' : ''; ?>">Tous</a>
            <a href="rides.php?filter=pending" class="btn btn-outline-warning <?php echo $filter === 'pending' ? 'active' : ''; ?>">En Attente</a>
            <a href="rides.php?filter=approved" class="btn btn-outline-success <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approuvés</a>
            <a href="rides.php?filter=rejected" class="btn btn-outline-danger <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejetés</a>
        </div>

        <!-- Rides Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Trajets (<?php echo $total_rides; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (count($rides) > 0): ?>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Trajet</th>
                                    <th>Conducteur</th>
                                    <th>Départ</th>
                                    <th>Prix</th>
                                    <th>Places</th>
                                    <th>Approbation</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rides as $ride): ?>
                                    <tr>
                                        <td><?php echo $ride['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ride['origin']); ?></strong>
                                            <span class="text-muted">→</span>
                                            <strong><?php echo htmlspecialchars($ride['destination']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ride['first_name'] . ' ' . $ride['last_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ride['driver_email']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($ride['departure_date']); ?></td>
                                        <td><?php echo number_format($ride['price_per_seat'], 2); ?> TND</td>
                                        <td>
                                            <?php echo $ride['seats_available']; ?>
                                            <?php if ($ride['booking_count'] > 0): ?>
                                                <br><small class="text-muted">(<?php echo $ride['booking_count']; ?> rés.)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $ride['approval_status']; ?>">
                                                <?php 
                                                    $approval_labels = ['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Rejeté'];
                                                    echo $approval_labels[$ride['approval_status']] ?? $ride['approval_status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $ride['status']; ?>">
                                                <?php echo ucfirst($ride['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="approve_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-action view" title="Voir détails">Voir</a>
                                            
                                            <?php if ($ride['approval_status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                                    <input type="hidden" name="action" value="quick_approve">
                                                    <button type="submit" class="btn btn-action approve" title="Approuver">✓</button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                                    <input type="hidden" name="action" value="quick_reject">
                                                    <button type="submit" class="btn btn-action reject" title="Rejeter">✗</button>
                                                </form>
                                            <?php endif; ?>
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
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">Précédent</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">Suivant</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-4 mb-0">Aucun trajet trouvé.</p>
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
                    <a href="users.php" class="text-decoration-none">Utilisateurs</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
