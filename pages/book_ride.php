<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

// 1. Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour réserver un trajet.";
    header('Location: login.php');
    exit;
}

// 2. Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search_rides.php');
    exit;
}

$ride_id = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);
$seats_to_book = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
$passenger_id = $_SESSION['user_id'];

if (!$ride_id || !$seats_to_book || $seats_to_book <= 0) {
    $_SESSION['error_message'] = "Données de réservation invalides.";
    header('Location: search_rides.php');
    exit;
}

try {
    $db->begin_transaction();

    // 3. Récupérer les informations du trajet et le verrouiller pour la mise à jour
    $stmt = $db->prepare("SELECT * FROM rides WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $ride_id);
    $stmt->execute();
    $ride = $stmt->get_result()->fetch_assoc();

    // 4. Valider le trajet
    if (!$ride) {
        throw new Exception("Le trajet demandé n'existe pas.");
    }
    if ($ride['driver_id'] == $passenger_id) {
        throw new Exception("Vous не pouvez pas réserver une place dans votre propre trajet.");
    }
    if ($ride['status'] !== 'active') {
        throw new Exception("Ce trajet n'est plus actif ou a été annulé.");
    }

    // 5. Vérifier les places disponibles
    $booking_stmt = $db->prepare("SELECT SUM(seats_booked) as total_booked FROM bookings WHERE ride_id = ? AND booking_status != 'cancelled'");
    $booking_stmt->bind_param('i', $ride_id);
    $booking_stmt->execute();
    $total_booked = $booking_stmt->get_result()->fetch_assoc()['total_booked'] ?? 0;
    
    $available_seats = $ride['seats_available'] - $total_booked;

    if ($seats_to_book > $available_seats) {
        throw new Exception("Il n'y a pas assez de places disponibles. Places restantes : " . $available_seats);
    }

    // 6. Vérifier si l'utilisateur a déjà réservé
    $existing_booking_stmt = $db->prepare("SELECT id FROM bookings WHERE ride_id = ? AND passenger_id = ? AND booking_status != 'cancelled'");
    $existing_booking_stmt->bind_param('ii', $ride_id, $passenger_id);
    $existing_booking_stmt->execute();
    if ($existing_booking_stmt->get_result()->num_rows > 0) {
        throw new Exception("Vous avez déjà réservé une place pour ce trajet.");
    }

    // 7. Créer la réservation
    $total_price = $seats_to_book * $ride['price_per_seat'];
    $booking_status = 'confirmed'; // On confirme directement pour simplifier

    $insert_stmt = $db->prepare(
        "INSERT INTO bookings (ride_id, passenger_id, seats_booked, total_price, booking_status) VALUES (?, ?, ?, ?, ?)"
    );
    $insert_stmt->bind_param('iiids', $ride_id, $passenger_id, $seats_to_book, $total_price, $booking_status);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("Erreur lors de la création de la réservation.");
    }

    // Si tout s'est bien passé, on valide la transaction
    $db->commit();

    $_SESSION['success_message'] = "Votre réservation a été confirmée avec succès !";
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // En cas d'erreur, on annule tout
    $db->rollback();
    $_SESSION['error_message'] = "Erreur de réservation : " . $e->getMessage();
    header('Location: search_rides.php');
    exit;
}
