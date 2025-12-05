<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $origin = sanitize($_POST['origin'] ?? '');
        $destination = sanitize($_POST['destination'] ?? '');
        $departure_date = sanitize($_POST['departure_date'] ?? '');
        $departure_time = sanitize($_POST['departure_time'] ?? '');
        $seats_available = intval($_POST['seats_available'] ?? 0);
        $price_per_seat = floatval($_POST['price_per_seat'] ?? 0);
        $vehicle_description = sanitize($_POST['vehicle_description'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        // Validation
        if (empty($origin) || empty($destination)) {
            throw new Exception("Le lieu de départ et d'arrivée sont requis.");
        }

        if (empty($departure_date) || empty($departure_time)) {
            throw new Exception("La date et l'heure de départ sont requises.");
        }

        if ($seats_available <= 0 || $seats_available > 8) {
            throw new Exception("Le nombre de places doit être compris entre 1 et 8.");
        }

        if ($price_per_seat < 0) {
            throw new Exception("Le prix ne peut pas être négatif.");
        }
        
        if (empty($vehicle_description)) {
            throw new Exception("La description du véhicule est requise.");
        }

        // Combiner date et heure
        $departure_datetime = $departure_date . ' ' . $departure_time . ':00';

        // Inserer dans la base de donnees
        $query = "INSERT INTO rides (driver_id, origin, destination, departure_date, seats_available, price_per_seat, vehicle_description, description, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

        $stmt = $db->prepare($query);

        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête: " . $db->error);
        }

        $stmt->bind_param(
            "isssisds",
            $user_id,
            $origin,
            $destination,
            $departure_datetime,
            $seats_available,
            $price_per_seat,
            $vehicle_description,
            $description
        );

        if (!$stmt->execute()) {
            // Note: This assumes the ALTER TABLE command was run. If not, this will fail.
            throw new Exception("Erreur lors de la création du trajet: " . $stmt->error);
        }

        $ride_id = $stmt->insert_id;
        $stmt->close();

        $success_message = "Trajet créé avec succès! Vous allez être redirigé.";

        // Rediriger apres 2 secondes
        header("Refresh: 2; url=dashboard.php");

    } catch (Exception $e) {
        $error_message = "Erreur: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer un Trajet - Covoiturage</title>
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
        <div class="container">
            <h1>Proposer un Trajet</h1>
            <p>Partagez votre trajet et économisez sur les frais.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header">
                        Créer un nouveau trajet
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="origin" class="form-label">Lieu de départ</label>
                                    <input type="text" class="form-control" id="origin" name="origin" placeholder="Ex: Paris" required>
                                    <div class="invalid-feedback">Le lieu de départ est requis.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="destination" class="form-label">Lieu d'arrivée</label>
                                    <input type="text" class="form-control" id="destination" name="destination" placeholder="Ex: Lyon" required>
                                    <div class="invalid-feedback">Le lieu d'arrivée est requis.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="departure_date" class="form-label">Date de départ</label>
                                    <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                                    <div class="invalid-feedback">La date de départ est requise.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="departure_time" class="form-label">Heure de départ</label>
                                    <input type="time" class="form-control" id="departure_time" name="departure_time" required>
                                    <div class="invalid-feedback">L'heure de départ est requise.</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="seats_available" class="form-label">Nombre de places</label>
                                    <input type="number" class="form-control" id="seats_available" name="seats_available" min="1" max="8" placeholder="Ex: 3" required>
                                    <div class="invalid-feedback">Nombre de places invalide (1-8).</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="price_per_seat" class="form-label">Prix par place (TND)</label>
                                    <input type="number" class="form-control" id="price_per_seat" name="price_per_seat" placeholder="Ex: 15.00" step="0.01" min="0" required>
                                    <div class="invalid-feedback">Le prix est requis.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="vehicle_description" class="form-label">Véhicule</label>
                                <input type="text" class="form-control" id="vehicle_description" name="vehicle_description" placeholder="Ex: Peugeot 208 Blanche" required>
                                <div class="invalid-feedback">La description du véhicule est requise.</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description du trajet (optionnel)</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Informations utiles: fumeur/non-fumeur, animaux, bagages, etc."></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Créer le trajet</button>
                            </div>
                        </form>
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
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
