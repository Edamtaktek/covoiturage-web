<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

$results = [];
$total_results = 0;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $origin = sanitize($_POST['origin'] ?? '');
        $destination = sanitize($_POST['destination'] ?? '');
        $date = sanitize($_POST['date'] ?? '');
        $seats = intval($_POST['seats'] ?? 1);
        $max_price = !empty($_POST['max_price']) ? floatval($_POST['max_price']) : null;

        $query = "SELECT r.*, u.username, u.first_name, u.last_name, u.rating, u.reviews_count,
                         COUNT(DISTINCT b.id) as booked_seats,
                         (r.seats_available - COUNT(DISTINCT b.id)) as available_seats
                  FROM rides r
                  INNER JOIN users u ON r.driver_id = u.id
                  LEFT JOIN bookings b ON r.id = b.ride_id AND b.booking_status != 'cancelled'
                  WHERE r.status = 'active'
                  AND r.approval_status = 'approved'
                  AND r.origin LIKE ?
                  AND r.destination LIKE ?";

        $params = [
            '%' . $origin . '%',
            '%' . $destination . '%'
        ];

        if (!empty($date)) {
            $query .= " AND DATE(r.departure_date) = ?";
            $params[] = $date;
        }

        if ($max_price !== null) {
            $query .= " AND r.price_per_seat <= ?";
            $params[] = $max_price;
        }

        $query .= " GROUP BY r.id
                    HAVING available_seats >= ?
                    ORDER BY r.departure_date ASC";
        $params[] = $seats;

        $stmt = $db->prepare($query);

        if (!$stmt) {
            throw new Exception("Erreur de préparation: " . $db->error);
        }

        $types = str_repeat('s', count($params) - 1) . 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $results = $result->fetch_all(MYSQLI_ASSOC);
        $total_results = count($results);

        if ($total_results > 0) {
            $success_message = "Trouvé " . $total_results . " trajet(s) disponible(s)";
        } else {
            $error_message = "Aucun trajet ne correspond à votre recherche";
        }

        $stmt->close();

    } catch (Exception $e) {
        $error_message = "Erreur lors de la recherche: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un Trajet - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">

    <!-- Hero Section -->
    <div class="hero-section">
        <h1>Chercher un Trajet</h1>
        <p>Trouvez le trajet qui vous convient</p>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-0">
        <!-- Search Form -->
        <div class="search-form">
            <h3 class="mb-4">Filtrer votre recherche</h3>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="origin" class="form-label">Lieu de depart</label>
                        <input type="text" class="form-control" id="origin" name="origin"
                               placeholder="Ex: Paris, Lyon..." value="<?php echo isset($_POST['origin']) ? htmlspecialchars($_POST['origin']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="destination" class="form-label">Lieu d'arrivee</label>
                        <input type="text" class="form-control" id="destination" name="destination"
                               placeholder="Ex: Marseille, Toulouse..." value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : ''; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date" class="form-label">Date de depart</label>
                        <input type="date" class="form-control" id="date" name="date"
                               value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="seats" class="form-label">Nombre de places</label>
                        <input type="number" class="form-control" id="seats" name="seats" min="1" max="8"
                               value="<?php echo isset($_POST['seats']) ? intval($_POST['seats']) : 1; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="max_price" class="form-label">Prix maximum par place</label>
                        <input type="number" class="form-control" id="max_price" name="max_price"
                               placeholder="Sans limite" step="0.01" value="<?php echo isset($_POST['max_price']) ? htmlspecialchars($_POST['max_price']) : ''; ?>">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg">Chercher des trajets</button>
                    <a href="search_rides.php" class="btn btn-secondary btn-lg">Reinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="mt-5">
            <h3 class="section-title">Trajets Disponibles</h3>

            <?php if ($total_results > 0): ?>
                <?php foreach ($results as $ride): ?>
                    <div class="ride-card">
                        <div class="ride-header">
                            <div>
                                <div class="ride-route">
                                    <strong><?php echo htmlspecialchars($ride['origin']); ?></strong>
                                    <span style="color: #999; margin: 0 10px;">-></span>
                                    <strong><?php echo htmlspecialchars($ride['destination']); ?></strong>
                                </div>
                                <small style="color: #999;">
                                    Depart: <?php echo formatDate($ride['departure_date']); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="ride-price"><?php echo number_format($ride['price_per_seat'], 2); ?> TND</div>
                                <small style="color: #999;">par place</small>
                            </div>
                        </div>

                        <div class="ride-driver">
                            <div class="driver-avatar">
                                <?php echo strtoupper(substr($ride['first_name'], 0, 1)); ?>
                            </div>
                            <div class="driver-info flex-grow-1">
                                <div class="driver-name">
                                    <?php echo htmlspecialchars($ride['first_name'] . ' ' . $ride['last_name']); ?>
                                </div>
                                <div class="driver-rating">
                                    Note: <?php echo number_format($ride['rating'], 1); ?>/5.0
                                    (<?php echo $ride['reviews_count']; ?> avis)
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-success">
                                    <?php echo $ride['available_seats']; ?> place(s) disponible(s)
                                </span>
                            </div>
                        </div>

                        <div class="ride-details">
                            <div class="ride-detail-item">
                                <span class="ride-detail-label">Heure de départ:</span>
                                <?php echo date('H:i', strtotime($ride['departure_date'])); ?>
                            </div>
                            <div class="ride-detail-item">
                                <span class="ride-detail-label">Places disponibles:</span>
                                <?php echo $ride['seats_available']; ?>
                            </div>
                        </div>

                        <?php if (!empty($ride['description'])): ?>
                            <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <strong>Description:</strong><br>
                                <?php echo htmlspecialchars($ride['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <?php if (isLoggedIn()): ?>
                                <form action="add_to_cart.php" method="POST" class="d-inline">
                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                    <input type="hidden" name="seats" value="1"> <!-- Default 1, can be changed -->
                                    <button type="submit" class="btn btn-primary">
                                        Ajouter au panier
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">
                                    Se connecter pour réserver
                                </a>
                            <?php endif; ?>
                            <a href="view_ride.php?id=<?php echo $ride['id']; ?>" class="btn btn-outline-primary">
                                Plus de détails
                            </a>
                        </div>
                    </div>

                    <!-- Booking Modal (can be removed or repurposed) -->
                <?php endforeach; ?>
            <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="alert alert-info">
                    Aucun trajet ne correspond à votre recherche. Essayez d'autres critères.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Utilisez le formulaire ci-dessus pour chercher des trajets.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <h5>Covoiturage</h5>
                    <p>Une plateforme pour partager vos trajets</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens utiles</h5>
                    <a href="../index.php">Accueil</a>
                    <a href="#">À propos</a>
                    <a href="#">Contact</a>
                </div>
                <div class="col-md-4">
                    <h5>Mon Compte</h5>
                    <a href="dashboard.php">Tableau de Bord</a>
                    <a href="login.php">Connexion</a>
                    <a href="register.php">Inscription</a>
                </div>
            </div>
            <hr style="border-color: var(--border-color); margin: 20px 0;">
            <p class="text-center" style="color: var(--text-light); margin: 0;">&copy; 2025 Covoiturage. Tous droits réservés.</p>
        </div>
    </footer>

</div><!-- End main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
