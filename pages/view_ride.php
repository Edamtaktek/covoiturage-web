<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$ride_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];
$error_message = '';
$ride = null;
$bookings = [];

if (!$ride_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Récupérer les détails du trajet
    $stmt = $db->prepare("
        SELECT r.*, u.first_name as driver_first_name, u.last_name as driver_last_name 
        FROM rides r 
        JOIN users u ON r.driver_id = u.id
        WHERE r.id = ?
    ");
    $stmt->bind_param('i', $ride_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ride = $result->fetch_assoc();
    $stmt->close();

    // Le conducteur peut voir sa propre page de trajet, mais les autres utilisateurs aussi
    if (!$ride) {
        $_SESSION['error_message'] = "Trajet non trouvé.";
        header('Location: dashboard.php');
        exit;
    }

    // Si l'utilisateur connecté est le conducteur, récupérer les réservations
    if ($ride['driver_id'] == $user_id) {
        $booking_query = "
            SELECT b.*, u.first_name, u.last_name, u.email 
            FROM bookings b
            JOIN users u ON b.passenger_id = u.id
            WHERE b.ride_id = ?
            ORDER BY b.created_at DESC";
        $stmt = $db->prepare($booking_query);
        $stmt->bind_param('i', $ride_id);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Trajet - Covoiturage</title>
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
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php elseif ($ride): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Détails du Trajet</h1>
                <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <!-- Détails du trajet -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($ride['origin']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Proposé par : <strong><?php echo htmlspecialchars($ride['driver_first_name'] . ' ' . $ride['driver_last_name']); ?></strong></p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Date de départ:</strong> <?php echo formatDate($ride['departure_date'], 'l j F Y à H:i'); ?></li>
                                <li class="list-group-item"><strong>Prix par place:</strong> <?php echo number_format($ride['price_per_seat'], 2); ?> TND</li>
                                <li class="list-group-item"><strong>Places disponibles:</strong> <?php echo $ride['seats_available']; ?></li>
                                <li class="list-group-item"><strong>Véhicule:</strong> <?php echo htmlspecialchars($ride['vehicle_description']); ?></li>
                                <li class="list-group-item"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($ride['description'] ?: 'Aucune description')); ?></li>
                                <li class="list-group-item"><strong>Statut:</strong> <span class="badge bg-info"><?php echo ucfirst($ride['status']); ?></span></li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <?php if ($ride['driver_id'] == $user_id): ?>
                                <a href="edit_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-primary">Modifier le trajet</a>
                            <?php else: ?>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                    <div class="row align-items-end">
                                        <div class="col-6">
                                            <label for="seats" class="form-label">Nombre de places</label>
                                            <input type="number" name="seats" id="seats" class="form-control" value="1" min="1" max="<?php echo $ride['seats_available']; ?>">
                                        </div>
                                        <div class="col-6">
                                            <button type="submit" class="btn btn-primary w-100">Ajouter au panier</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($ride['driver_id'] == $user_id): ?>
                <div class="col-lg-5">
                    <!-- Passagers -->
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3>Passagers (<?php echo count($bookings); ?>)</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($bookings) > 0): ?>
                                <ul class="list-group">
                                    <?php foreach ($bookings as $booking): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo $booking['seats_booked']; ?> place(s)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center text-muted">Aucun passager n'a encore réservé.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
