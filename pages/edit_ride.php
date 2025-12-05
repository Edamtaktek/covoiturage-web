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
$success_message = '';
$ride = null;

if (!$ride_id) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les détails du trajet pour pré-remplir le formulaire
try {
    $stmt = $db->prepare("SELECT * FROM rides WHERE id = ? AND driver_id = ?");
    $stmt->bind_param('ii', $ride_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ride = $result->fetch_assoc();
    $stmt->close();

    if (!$ride) {
        $_SESSION['error_message'] = "Trajet non trouvé ou accès non autorisé.";
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    $error_message = "Erreur de récupération des données: " . $e->getMessage();
    $ride = []; // Empêche les erreurs si le formulaire est affiché
}


// Gérer la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $ride) {
    try {
        // Récupération et nettoyage des données
        $origin = sanitize($_POST['origin'] ?? '');
        $destination = sanitize($_POST['destination'] ?? '');
        $departure_date = sanitize($_POST['departure_date'] ?? '');
        $departure_time = sanitize($_POST['departure_time'] ?? '');
        $seats_available = intval($_POST['seats_available'] ?? 0);
        $price_per_seat = floatval($_POST['price_per_seat'] ?? 0);
        $vehicle_description = sanitize($_POST['vehicle_description'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');

        // Validation (similaire à create_ride.php)
        if (empty($origin) || empty($destination) || empty($departure_date) || empty($departure_time)) {
            throw new Exception("Tous les champs principaux sont requis.");
        }
        if ($seats_available <= 0) {
            throw new Exception("Le nombre de places doit être positif.");
        }

        $departure_datetime = $departure_date . ' ' . $departure_time;

        // Mise à jour dans la base de données
        $query = "UPDATE rides SET 
                    origin = ?, 
                    destination = ?, 
                    departure_date = ?, 
                    seats_available = ?, 
                    price_per_seat = ?, 
                    vehicle_description = ?, 
                    description = ?,
                    status = ?
                  WHERE id = ? AND driver_id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param(
            'sssiisssii',
            $origin,
            $destination,
            $departure_datetime,
            $seats_available,
            $price_per_seat,
            $vehicle_description,
            $description,
            $status,
            $ride_id,
            $user_id
        );

        if ($stmt->execute()) {
            $success_message = "Trajet mis à jour avec succès !";
            // Re-fetch data to show updated values
            $stmt->close();
            $stmt = $db->prepare("SELECT * FROM rides WHERE id = ?");
            $stmt->bind_param('i', $ride_id);
            $stmt->execute();
            $ride = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            throw new Exception("Erreur lors de la mise à jour: " . $stmt->error);
        }

    } catch (Exception $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer le Trajet - Covoiturage</title>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3>Éditer le trajet</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <?php if ($ride): ?>
                            <form method="POST" action="edit_ride.php?id=<?php echo $ride_id; ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="origin" class="form-label">Lieu de départ</label>
                                        <input type="text" class="form-control" id="origin" name="origin" value="<?php echo htmlspecialchars($ride['origin']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="destination" class="form-label">Lieu d'arrivée</label>
                                        <input type="text" class="form-control" id="destination" name="destination" value="<?php echo htmlspecialchars($ride['destination']); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="departure_date" class="form-label">Date de départ</label>
                                        <input type="date" class="form-control" id="departure_date" name="departure_date" value="<?php echo date('Y-m-d', strtotime($ride['departure_date'])); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="departure_time" class="form-label">Heure de départ</label>
                                        <input type="time" class="form-control" id="departure_time" name="departure_time" value="<?php echo date('H:i', strtotime($ride['departure_date'])); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="seats_available" class="form-label">Nombre de places</label>
                                        <input type="number" class="form-control" id="seats_available" name="seats_available" value="<?php echo $ride['seats_available']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="price_per_seat" class="form-label">Prix par place (TND)</label>
                                        <input type="number" class="form-control" id="price_per_seat" name="price_per_seat" value="<?php echo $ride['price_per_seat']; ?>" step="0.01" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="vehicle_description" class="form-label">Véhicule</label>
                                    <input type="text" class="form-control" id="vehicle_description" name="vehicle_description" value="<?php echo htmlspecialchars($ride['vehicle_description']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description du trajet</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($ride['description']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($ride['status'] == 'active') ? 'selected' : ''; ?>>Actif</option>
                                        <option value="completed" <?php echo ($ride['status'] == 'completed') ? 'selected' : ''; ?>>Terminé</option>
                                        <option value="cancelled" <?php echo ($ride['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulé</option>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">Le trajet que vous essayez de modifier n'existe pas ou vous n'avez pas la permission de le faire.</div>
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
