<?php
/**
 * Admin Ride Inspection and Approval Page
 */
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/config.php';

// Require admin access
requireAdmin();

$ride_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error_message = '';
$success_message = '';
$ride = null;
$driver = null;
$bookings = [];

if (!$ride_id) {
    header('Location: rides.php');
    exit;
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Token de sécurité invalide.";
    } else {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $db->prepare("UPDATE rides SET approval_status = 'approved', rejection_reason = NULL WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Le trajet a été approuvé avec succès. Il est maintenant visible aux utilisateurs.";
                    break;
                    
                case 'reject':
                    $rejection_reason = isset($_POST['rejection_reason']) ? sanitize($_POST['rejection_reason']) : '';
                    if (empty($rejection_reason)) {
                        $error_message = "Veuillez fournir une raison de rejet.";
                    } else {
                        $stmt = $db->prepare("UPDATE rides SET approval_status = 'rejected', rejection_reason = ? WHERE id = ?");
                        $stmt->bind_param('si', $rejection_reason, $ride_id);
                        $stmt->execute();
                        $success_message = "Le trajet a été rejeté.";
                    }
                    break;
                    
                case 'cancel':
                    $stmt = $db->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Le trajet a été annulé.";
                    break;
                    
                case 'activate':
                    $stmt = $db->prepare("UPDATE rides SET status = 'active' WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Le trajet a été réactivé.";
                    break;
                    
                case 'reset_pending':
                    $stmt = $db->prepare("UPDATE rides SET approval_status = 'pending', rejection_reason = NULL WHERE id = ?");
                    $stmt->bind_param('i', $ride_id);
                    $stmt->execute();
                    $success_message = "Le trajet a été remis en attente d'approbation.";
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
    // Get ride details
    $stmt = $db->prepare("
        SELECT r.* 
        FROM rides r
        WHERE r.id = ?
    ");
    $stmt->bind_param('i', $ride_id);
    $stmt->execute();
    $ride = $stmt->get_result()->fetch_assoc();
    
    if (!$ride) {
        header('Location: rides.php?error=not_found');
        exit;
    }
    
    // Get driver details
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $ride['driver_id']);
    $stmt->execute();
    $driver = $stmt->get_result()->fetch_assoc();
    
    // Get bookings for this ride
    $stmt = $db->prepare("
        SELECT b.*, u.first_name, u.last_name, u.email, u.phone
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        WHERE b.ride_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param('i', $ride_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection du Trajet #<?php echo $ride_id; ?> - Administration</title>
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Inspection du Trajet #<?php echo $ride_id; ?></h1>
                    <p>Examinez et approuvez ou rejetez ce trajet.</p>
                </div>
                <a href="rides.php" class="btn btn-light">← Retour aux trajets</a>
            </div>
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

        <?php if ($ride): ?>
            <div class="row">
                <!-- Ride Details Column -->
                <div class="col-lg-8">
                    <!-- Ride Information Card -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Détails du Trajet</h3>
                            <div>
                                <span class="status-badge <?php echo $ride['approval_status']; ?> me-2">
                                    <?php 
                                        $approval_labels = ['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Rejeté'];
                                        echo $approval_labels[$ride['approval_status']] ?? $ride['approval_status'];
                                    ?>
                                </span>
                                <span class="status-badge <?php echo $ride['status']; ?>">
                                    <?php echo ucfirst($ride['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-2">Origine</h5>
                                    <h4><?php echo htmlspecialchars($ride['origin']); ?></h4>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-2">Destination</h5>
                                    <h4><?php echo htmlspecialchars($ride['destination']); ?></h4>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <strong>Date de Départ</strong>
                                    <p class="mb-0"><?php echo formatDate($ride['departure_date']); ?></p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <strong>Prix par Place</strong>
                                    <p class="mb-0"><?php echo number_format($ride['price_per_seat'], 2); ?> TND</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <strong>Places Disponibles</strong>
                                    <p class="mb-0"><?php echo $ride['seats_available']; ?></p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Véhicule</strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($ride['vehicle_description'] ?? 'Non spécifié'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Créé le</strong>
                                    <p class="mb-0"><?php echo formatDate($ride['created_at']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($ride['description']): ?>
                                <div class="mt-3">
                                    <strong>Description</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ride['description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($ride['rejection_reason']): ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <strong>Raison du Rejet:</strong>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ride['rejection_reason'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">Actions Administratives</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($ride['approval_status'] === 'pending'): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('Confirmer l\'approbation de ce trajet?')">
                                                ✓ Approuver le Trajet
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                            ✗ Rejeter le Trajet
                                        </button>
                                    </div>
                                </div>
                            <?php elseif ($ride['approval_status'] === 'approved'): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="reset_pending">
                                            <button type="submit" class="btn btn-warning w-100">
                                                Remettre en Attente
                                            </button>
                                        </form>
                                    </div>
                                    <?php if ($ride['status'] === 'active'): ?>
                                        <div class="col-md-6 mb-3">
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Confirmer l\'annulation de ce trajet?')">
                                                    Annuler le Trajet
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-md-6 mb-3">
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-success w-100">
                                                    Réactiver le Trajet
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($ride['approval_status'] === 'rejected'): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Confirmer l\'approbation de ce trajet?')">
                                                Approuver malgré tout
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="reset_pending">
                                            <button type="submit" class="btn btn-warning w-100">
                                                Remettre en Attente
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Bookings -->
                    <?php if (count($bookings) > 0): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0">Réservations (<?php echo count($bookings); ?>)</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table admin-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Passager</th>
                                                <th>Contact</th>
                                                <th>Places</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['email']); ?>
                                                        <?php if ($booking['phone']): ?>
                                                            <br><small><?php echo htmlspecialchars($booking['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $booking['seats_booked']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                            <?php echo ucfirst($booking['booking_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($booking['created_at']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Driver Details Column -->
                <div class="col-lg-4">
                    <?php if ($driver): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="mb-0">Informations du Conducteur</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="driver-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                                        <?php echo strtoupper(substr($driver['first_name'], 0, 1)); ?>
                                    </div>
                                    <h4><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h4>
                                    <p class="text-muted">@<?php echo htmlspecialchars($driver['username']); ?></p>
                                </div>
                                
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Email</span>
                                        <span><?php echo htmlspecialchars($driver['email']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Téléphone</span>
                                        <span><?php echo htmlspecialchars($driver['phone'] ?? 'Non fourni'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Note</span>
                                        <span><?php echo number_format($driver['rating'], 1); ?>/5</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Statut</span>
                                        <span class="status-badge <?php echo $driver['status']; ?>">
                                            <?php echo ucfirst($driver['status']); ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Membre depuis</span>
                                        <span><?php echo formatDateShort($driver['created_at']); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0">Statistiques</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total Réservations</span>
                                    <strong><?php echo count($bookings); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Places Réservées</span>
                                    <strong><?php echo array_sum(array_column($bookings, 'seats_booked')); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Revenus Estimés</span>
                                    <strong><?php echo number_format(array_sum(array_column($bookings, 'seats_booked')) * $ride['price_per_seat'], 2); ?> TND</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- End main-content -->

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="reject">
                    <div class="modal-header">
                        <h5 class="modal-title">Rejeter le Trajet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Raison du Rejet <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" required placeholder="Expliquez pourquoi ce trajet est rejeté..."></textarea>
                            <small class="text-muted">Cette raison sera visible par le conducteur.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Confirmer le Rejet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
