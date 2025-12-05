<?php
// pages/checkout.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'] ?? 'cash';

if (!in_array($payment_method, ['cash', 'online'])) {
    $_SESSION['error_message'] = "Méthode de paiement invalide.";
    header('Location: cart.php');
    exit;
}

$db->begin_transaction();

try {
    // 1. Récupérer tous les articles du panier de l'utilisateur
    $query = "
        SELECT c.ride_id, c.seats_to_book, r.price_per_seat, r.seats_available 
        FROM cart c
        JOIN rides r ON c.ride_id = r.id
        WHERE c.user_id = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($cart_items)) {
        throw new Exception("Votre panier est vide.");
    }

    // 2. Pour chaque article, créer une réservation et mettre à jour les places
    foreach ($cart_items as $item) {
        // Vérifier la disponibilité des places (double-vérification)
        if ($item['seats_available'] < $item['seats_to_book']) {
            throw new Exception("Le trajet de " . $item['origin'] . " à " . $item['destination'] . " n'a plus assez de places.");
        }

        // Calculer le prix total pour cette réservation
        $total_price = $item['seats_to_book'] * $item['price_per_seat'];
        $booking_status = 'confirmed';

        // Insérer la réservation
        $insert_booking_stmt = $db->prepare(
            "INSERT INTO bookings (ride_id, passenger_id, seats_booked, total_price, payment_method, booking_status) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert_booking_stmt->bind_param('iiidss', $item['ride_id'], $user_id, $item['seats_to_book'], $total_price, $payment_method, $booking_status);
        $insert_booking_stmt->execute();
        $insert_booking_stmt->close();

        // Mettre à jour le nombre de places disponibles pour le trajet
        $update_ride_stmt = $db->prepare("UPDATE rides SET seats_available = seats_available - ? WHERE id = ?");
        $update_ride_stmt->bind_param('ii', $item['seats_to_book'], $item['ride_id']);
        $update_ride_stmt->execute();
        $update_ride_stmt->close();
    }

    // 3. Vider le panier de l'utilisateur
    $clear_cart_stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_stmt->bind_param('i', $user_id);
    $clear_cart_stmt->execute();
    $clear_cart_stmt->close();

    // Si tout s'est bien passé, valider la transaction
    $db->commit();

    $_SESSION['success_message'] = "Votre commande a été passée avec succès ! Vous pouvez voir vos réservations dans le tableau de bord.";
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // En cas d'erreur, annuler toutes les opérations
    $db->rollback();
    $_SESSION['error_message'] = "Erreur lors du paiement : " . $e->getMessage();
    header('Location: cart.php');
    exit;
}
?>
