<?php
/**
 * Admin Dashboard - Overview of all statistics and management
 */
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Require admin access
requireAdmin();

$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'total_rides' => 0,
    'pending_rides' => 0,
    'approved_rides' => 0,
    'total_bookings' => 0
];

$recent_users = [];
$pending_rides = [];
$error_message = '';

try {
    // Get statistics
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
        (SELECT COUNT(*) FROM rides) as total_rides,
        (SELECT COUNT(*) FROM rides WHERE approval_status = 'pending') as pending_rides,
        (SELECT COUNT(*) FROM rides WHERE approval_status = 'approved') as approved_rides,
        (SELECT COUNT(*) FROM bookings) as total_bookings
    ";
    $stats_result = $db->query($stats_query);
    if ($stats_result) {
        $stats = $stats_result->fetch_assoc();
    }

    // Get recent users
    $users_query = "SELECT id, username, email, first_name, last_name, status, is_admin, created_at 
                    FROM users ORDER BY created_at DESC LIMIT 5";
    $users_result = $db->query($users_query);
    if ($users_result) {
        $recent_users = $users_result->fetch_all(MYSQLI_ASSOC);
    }

    // Get pending rides for approval
    $pending_query = "
        SELECT r.*, u.first_name, u.last_name, u.email as driver_email
        FROM rides r
        JOIN users u ON r.driver_id = u.id
        WHERE r.approval_status = 'pending'
        ORDER BY r.created_at DESC
        LIMIT 5
    ";
    $pending_result = $db->query($pending_query);
    if ($pending_result) {
        $pending_rides = $pending_result->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    $error_message = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Covoiturage</title>
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
            <h1>Tableau de Bord Administrateur</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?>. Gérez la plateforme de covoiturage.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card admin-stats-card users">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Utilisateurs Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card admin-stats-card rides">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_rides']); ?></h3>
                        <p>Trajets Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card admin-stats-card pending">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['pending_rides']); ?></h3>
                        <p>Trajets en Attente</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card admin-stats-card bookings">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_bookings']); ?></h3>
                        <p>Réservations Total</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Rides Section -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Trajets en Attente d'Approbation</h3>
                        <a href="rides.php?filter=pending" class="btn btn-sm btn-outline-primary">Voir Tous</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_rides) > 0): ?>
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Trajet</th>
                                            <th>Conducteur</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_rides as $ride): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ride['origin']); ?></strong>
                                                    <span class="text-muted">→</span>
                                                    <strong><?php echo htmlspecialchars($ride['destination']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($ride['first_name'] . ' ' . $ride['last_name']); ?></td>
                                                <td><?php echo formatDate($ride['departure_date']); ?></td>
                                                <td>
                                                    <a href="approve_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-action view">Inspecter</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted py-4 mb-0">Aucun trajet en attente d'approbation.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users Section -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Utilisateurs Récents</h3>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">Voir Tous</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_users) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge bg-danger ms-1">Admin</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <span class="status-badge <?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted py-4 mb-0">Aucun utilisateur trouvé.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Actions Rapides</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <a href="users.php" class="btn btn-primary w-100 py-3">
                                    Gérer les Utilisateurs
                                </a>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <a href="rides.php" class="btn btn-success w-100 py-3">
                                    Gérer les Trajets
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="rides.php?filter=pending" class="btn btn-warning w-100 py-3">
                                    Approuver les Trajets (<?php echo $stats['pending_rides']; ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <a href="../../index.php" class="text-decoration-none me-3">Site Public</a>
                    <a href="users.php" class="text-decoration-none me-3">Utilisateurs</a>
                    <a href="rides.php" class="text-decoration-none">Trajets</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
