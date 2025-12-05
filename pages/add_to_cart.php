<?php
// pages/add_to_cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$ride_id = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);
$seats_to_book = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$ride_id || !$seats_to_book || $seats_to_book <= 0) {
    $_SESSION['error_message'] = "Données invalides pour l'ajout au panier.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'search_rides.php'));
    exit;
}

try {
    // 1. Vérifier si le trajet existe et a assez de places
    $stmt = $db->prepare("SELECT seats_available, driver_id FROM rides WHERE id = ?");
    $stmt->bind_param('i', $ride_id);
    $stmt->execute();
    $ride = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ride) {
        throw new Exception("Trajet non trouvé.");
    }
    if ($ride['driver_id'] == $user_id) {
        throw new Exception("Vous ne pouvez pas ajouter votre propre trajet au panier.");
    }
    if ($ride['seats_available'] < $seats_to_book) {
        throw new Exception("Pas assez de places disponibles pour ce trajet.");
    }

    // 2. Vérifier si l'article est déjà dans le panier (ON DUPLICATE KEY UPDATE)
    $query = "
        INSERT INTO cart (user_id, ride_id, seats_to_book) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE seats_to_book = seats_to_book + VALUES(seats_to_book);
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param('iii', $user_id, $ride_id, $seats_to_book);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Le trajet a été ajouté à votre panier !";
    } else {
        throw new Exception("Erreur lors de l'ajout au panier.");
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'search_rides.php'));
    exit;
}

header('Location: cart.php');
exit;
?>
