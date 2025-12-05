<?php
// pages/cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total_price = 0;
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

try {
    $query = "
        SELECT 
            c.id as cart_id,
            r.id as ride_id,
            r.origin,
            r.destination,
            r.departure_date,
            r.price_per_seat,
            c.seats_to_book,
            (r.price_per_seat * c.seats_to_book) as sub_total,
            u.first_name as driver_first_name,
            u.last_name as driver_last_name
        FROM cart c
        JOIN rides r ON c.ride_id = r.id
        JOIN users u ON r.driver_id = u.id
        WHERE c.user_id = ?
        ORDER BY r.departure_date ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['sub_total'];
    }
    $stmt->close();

} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération du panier: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container my-5">
            <h1>Mon Panier</h1>
            <hr>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <p class="lead">Votre panier est vide.</p>
                    <a href="search_rides.php" class="btn btn-primary">Trouver un trajet</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($item['origin']); ?> → <?php echo htmlspecialchars($item['destination']); ?></h5>
                                            <p class="card-text text-muted">
                                                Le <?php echo date('d/m/Y à H:i', strtotime($item['departure_date'])); ?><br>
                                                Avec <?php echo htmlspecialchars($item['driver_first_name']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <p class="fw-bold fs-5"><?php echo number_format($item['sub_total'], 2); ?> TND</p>
                                            <p class="text-muted"><?php echo $item['seats_to_book']; ?> place(s)</p>
                                            <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>" class="btn btn-sm btn-outline-danger">Supprimer</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Résumé de la commande</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Total</span>
                                        <strong class="fs-5"><?php echo number_format($total_price, 2); ?> TND</strong>
                                    </li>
                                </ul>
                                <form action="checkout.php" method="POST" class="mt-3">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Méthode de paiement</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="cash" selected>Paiement en espèces (au conducteur)</option>
                                            <option value="online">Paiement en ligne (Carte)</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Passer la commande</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
