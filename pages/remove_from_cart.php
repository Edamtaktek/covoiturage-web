<?php
// pages/remove_from_cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$cart_id = filter_input(INPUT_GET, 'cart_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$cart_id) {
    $_SESSION['error_message'] = "ID d'article de panier invalide.";
    header('Location: cart.php');
    exit;
}

try {
    // Supprimer l'article du panier en s'assurant qu'il appartient bien à l'utilisateur connecté
    $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $cart_id, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "L'article a été supprimé de votre panier.";
        } else {
            // Soit l'article n'existe pas, soit il n'appartient pas à l'utilisateur
            $_SESSION['error_message'] = "Impossible de supprimer l'article.";
        }
    } else {
        throw new Exception("Erreur lors de la suppression.");
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: cart.php');
exit;
?>
