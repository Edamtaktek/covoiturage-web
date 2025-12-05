<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$user = null;
$my_bookings = [];
$my_rides = [];

try {
    // Récupérer les informations de l'utilisateur
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("Utilisateur non trouvé.");
    }

    // Récupérer les trajets réservés par l'utilisateur (passager)
    $bookings_query = "
        SELECT 
            r.id, r.origin, r.destination, r.departure_date,
            b.booking_status, b.seats_booked,
            u.first_name as driver_first_name, u.last_name as driver_last_name
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u ON r.driver_id = u.id
        WHERE b.passenger_id = ?
        ORDER BY r.departure_date DESC
        LIMIT 5";
    $stmt = $db->prepare($bookings_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $my_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Récupérer les trajets proposés par l'utilisateur (conducteur)
    $rides_query = "
        SELECT r.*, r.approval_status, (SELECT COUNT(*) FROM bookings WHERE ride_id = r.id AND booking_status = 'confirmed') as confirmed_bookings
        FROM rides r
        WHERE r.driver_id = ? 
        ORDER BY r.departure_date DESC 
        LIMIT 10";
    $stmt = $db->prepare($rides_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $my_rides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $error_message = "Erreur: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">

    <!-- Main Content -->
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Tableau de bord</h1>
            <div>
                <a href="create_ride.php" class="btn btn-primary">Proposer un trajet</a>
                <a href="search_rides.php" class="btn btn-outline-primary">Chercher un trajet</a>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Colonne de gauche -->
            <div class="col-lg-8">
                <!-- Mes Trajets Proposés -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3>Mes Trajets Proposés</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($my_rides) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($my_rides as $ride): ?>
                                    <div class="list-group-item list-group-item-action flex-column align-items-start">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($ride['origin']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></h5>
                                            <small class="text-muted"><?php echo formatDate($ride['departure_date'], 'd/m/Y H:i'); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <span class="badge bg-primary me-2"><?php echo number_format($ride['price_per_seat'], 2); ?> TND / place</span>
                                            <span class="badge bg-info me-2"><?php echo $ride['seats_available']; ?> places disponibles</span>
                                            <span class="badge bg-success me-2"><?php echo $ride['confirmed_bookings']; ?> réservation(s)</span>
                                            <?php 
                                                $approval_status = $ride['approval_status'] ?? 'pending';
                                                $approval_class = $approval_status === 'approved' ? 'bg-success' : ($approval_status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                                                $approval_label = $approval_status === 'approved' ? 'Approuvé' : ($approval_status === 'rejected' ? 'Rejeté' : 'En attente');
                                            ?>
                                            <span class="badge <?php echo $approval_class; ?>"><?php echo $approval_label; ?></span>
                                        </p>
                                        <?php if ($approval_status === 'rejected' && !empty($ride['rejection_reason'])): ?>
                                            <small class="text-danger">Raison: <?php echo htmlspecialchars($ride['rejection_reason']); ?></small>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="view_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-sm btn-outline-secondary">Voir</a>
                                            <a href="edit_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-sm btn-secondary">Éditer</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">Vous n'avez pas encore proposé de trajets.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mes Réservations -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3>Mes Réservations</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($my_bookings) > 0): ?>
                             <div class="list-group">
                                <?php foreach ($my_bookings as $booking): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></h6>
                                            <small><?php echo formatDate($booking['departure_date'], 'd/m/Y'); ?></small>
                                        </div>
                                        <p class="mb-1">Avec <?php echo htmlspecialchars($booking['driver_first_name']); ?>. Statut: <span class="badge bg-warning text-dark"><?php echo htmlspecialchars(ucfirst($booking['booking_status'])); ?></span></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">Vous n'avez aucune réservation pour le moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3>Mon Profil</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($user): ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                            <ul class="list-unstyled">
                                <li><strong>Téléphone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Non fourni'); ?></li>
                                <li><strong>Note:</strong> <?php echo number_format($user['rating'], 1); ?>/5 (<?php echo $user['reviews_count']; ?> avis)</li>
                            </ul>
                            <a href="profile.php" class="btn btn-outline-primary w-100">Modifier le profil</a>
                        <?php endif; ?>
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
                    <p class="mb-0">&copy; <?php echo date("Y"); ?> Covoiturage. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="../index.php" class="text-decoration-none me-3">Accueil</a>
                    <a href="search_rides.php" class="text-decoration-none me-3">Chercher</a>
                    <a href="create_ride.php" class="text-decoration-none">Proposer</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
