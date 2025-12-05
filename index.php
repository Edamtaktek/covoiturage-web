<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/config.php';

$featured_rides = [];
$stats = ['total_users' => 0, 'active_rides' => 0, 'completed_bookings' => 0];
$db_error = null;

try {
    // Recuperer les trajets actifs et approuvés pour la page d'accueil
    $rides_query = "
        SELECT 
            r.*,
            u.first_name,
            u.last_name,
            u.rating as driver_rating,
            u.reviews_count,
            COUNT(DISTINCT b.id) as bookings_count
        FROM rides r
        JOIN users u ON r.driver_id = u.id
        LEFT JOIN bookings b ON r.id = b.ride_id AND b.booking_status IN ('confirmed', 'completed')
        WHERE r.status = 'active' 
        AND r.approval_status = 'approved'
        AND r.departure_date > NOW()
        GROUP BY r.id
        ORDER BY r.departure_date ASC
        LIMIT 6
    ";

    $rides_result = $db->query($rides_query);
    if ($rides_result && $rides_result->num_rows > 0) {
        $featured_rides = $rides_result->fetch_all(MYSQLI_ASSOC);
    }

    // Recuperer les statistiques
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
        (SELECT COUNT(*) FROM rides WHERE status = 'active') as active_rides,
        (SELECT COUNT(*) FROM bookings WHERE booking_status = 'completed') as completed_bookings
    ";

    $stats_result = $db->query($stats_query);
    if ($stats_result) {
        $stats = $stats_result->fetch_assoc();
    }
} catch (Exception $e) {
    $db_error = "Erreur de connexion a la base de donnees: " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Covoiturage - Marketplace de Covoiturage en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">

    <!-- Hero Section -->
    <div class="hero-section">
        <h1>Partagez Votre Trajet, Économisez de l'Argent</h1>
        <p>Trouvez des compagnons de voyage ou proposez votre trajet</p>

        <div class="search-form mt-4">
            <form method="POST" action="pages/search_rides.php" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-lg" name="origin"
                           placeholder="Lieu de départ" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-lg" name="destination"
                           placeholder="Destination" required>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control form-control-lg" name="date" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Chercher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-0">
        <!-- Error Message -->
        <?php if ($db_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h4 class="alert-heading">Erreur de Connexion</h4>
                <?php echo $db_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="row mb-5">
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Utilisateurs actifs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['active_rides']); ?></h3>
                        <p>Trajets actifs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['completed_bookings']); ?></h3>
                        <p>Reservations realisees</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Rides Section -->
        <h2 class="section-title">Trajets Disponibles</h2>

        <?php if (count($featured_rides) > 0): ?>
            <div class="row">
                <?php foreach ($featured_rides as $ride): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="ride-card">
                            <div class="ride-header">
                                <div class="ride-route">
                                    <strong><?php echo htmlspecialchars($ride['origin']); ?></strong>
                                    <span style="color: #999;"> -> </span>
                                    <strong><?php echo htmlspecialchars($ride['destination']); ?></strong>
                                </div>
                                <div class="ride-price">
                                    <?php echo number_format($ride['price_per_seat'], 2); ?> TND
                                </div>
                            </div>

                            <div class="ride-driver">
                                <div class="driver-avatar">
                                    <?php echo strtoupper(substr($ride['first_name'], 0, 1)); ?>
                                </div>
                                <div class="driver-info">
                                    <div class="driver-name">
                                        <?php echo htmlspecialchars($ride['first_name'] . ' ' . $ride['last_name']); ?>
                                    </div>
                                    <div class="driver-rating">
                                        Note: <?php echo number_format($ride['driver_rating'], 1); ?>/5
                                    </div>
                                </div>
                            </div>

                            <div class="ride-details mt-3">
                                <div class="ride-detail-item">
                                    <span class="ride-detail-label">Depart:</span>
                                    <?php echo formatDate($ride['departure_date']); ?>
                                </div>
                                <div class="ride-detail-item">
                                    <span class="ride-detail-label">Places:</span>
                                    <?php echo $ride['seats_available']; ?> disponibles
                                </div>
                            </div>

                            <div class="mt-3">
                                <a href="pages/search_rides.php?ride_id=<?php echo $ride['id']; ?>" class="btn btn-primary btn-sm w-100">
                                    Voir plus et Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- How It Works Section -->
        <h2 class="section-title mt-5">Comment ca marche</h2>

        <div class="row mb-5">
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <div style="font-size: 48px; margin-bottom: 15px;">1</div>
                        <h5>Chercher un trajet</h5>
                        <p>Entrez votre lieu de depart et destination pour trouver des trajets disponibles.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <div style="font-size: 48px; margin-bottom: 15px;">2</div>
                        <h5>Reserver votre place</h5>
                        <p>Selectionnez le trajet qui vous convient et reservez votre place.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="card">
                    <div class="card-body">
                        <div style="font-size: 48px; margin-bottom: 15px;">3</div>
                        <h5>Profitez du voyage</h5>
                        <p>Rencontrez le conducteur et profitez d'un voyage economique et agreeable.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="card" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #5dade2 100%); border: none; color: white;">
            <div class="card-body text-center py-5">
                <h2>Prêt à partager votre trajet?</h2>
                <p>Proposez votre trajet et économisez de l'argent</p>
                <?php if (isLoggedIn()): ?>
                    <a href="pages/create_ride.php" class="btn btn-light btn-lg">Proposer un trajet</a>
                <?php else: ?>
                    <a href="pages/register.php" class="btn btn-light btn-lg">S'inscrire maintenant</a>
                <?php endif; ?>
            </div>
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
                    <a href="#">Accueil</a>
                    <a href="#">À propos</a>
                    <a href="#">Contact</a>
                </div>
                <div class="col-md-4">
                    <h5>Mon Compte</h5>
                    <a href="pages/dashboard.php">Tableau de Bord</a>
                    <a href="pages/login.php">Connexion</a>
                    <a href="pages/register.php">Inscription</a>
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
