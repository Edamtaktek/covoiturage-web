<?php
// seed.php
// Usage: Exécutez ce script une seule fois pour peupler la base de données avec des données de test.
// Assurez-vous que vos informations de connexion à la base de données sont correctes dans includes/db_connect.php

require_once '../includes/db_connect.php';

echo "<pre>";
echo "Démarrage du script de seeding...\n";

// Utiliser une transaction pour assurer l'intégrité des données
$db->begin_transaction();

try {
    // --- 1. Créer un utilisateur de test (conducteur) ---
    $driver_email = 'driver@covoiturage.tn';
    $driver_id = null;

    // Vérifier si l'utilisateur existe déjà
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $driver_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $driver_id = $result->fetch_assoc()['id'];
        echo "Utilisateur de test (conducteur) existe déjà avec l'ID: $driver_id\n";
    } else {
        // Créer l'utilisateur s'il n'existe pas
        $username = 'conducteur_test';
        $password = 'password123'; // Mot de passe simple pour le test
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $first_name = 'Sami';
        $last_name = 'Ben Ali';
        $phone = '22333444';
        
        $insert_user_query = "INSERT INTO users (username, email, password, first_name, last_name, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $db->prepare($insert_user_query);
        $stmt->bind_param('ssssss', $username, $driver_email, $hashed_password, $first_name, $last_name, $phone);
        $stmt->execute();
        
        $driver_id = $db->insert_id;
        echo "Nouvel utilisateur de test (conducteur) créé avec l'ID: $driver_id\n";
    }
    $stmt->close();

    // --- 2. Supprimer les anciens trajets de ce conducteur pour éviter les doublons ---
    $stmt = $db->prepare("DELETE FROM rides WHERE driver_id = ?");
    $stmt->bind_param('i', $driver_id);
    $deleted_rows = $stmt->execute() ? $stmt->affected_rows : 0;
    echo "$deleted_rows anciens trajets supprimés pour ce conducteur.\n";
    $stmt->close();

    // --- 3. Définir et insérer les trajets de test ---
    $rides = [
        [
            'origin' => 'Tunis',
            'destination' => 'Sousse',
            'departure_date' => date('Y-m-d H:i:s', strtotime('+1 day 2 hours')),
            'seats_available' => 3,
            'price_per_seat' => 15.00,
            'vehicle_description' => 'Polo 7',
            'description' => 'Trajet confortable, climatiseur disponible.'
        ],
        [
            'origin' => 'Sfax',
            'destination' => 'Tunis',
            'departure_date' => date('Y-m-d H:i:s', strtotime('+2 days 9 hours')),
            'seats_available' => 2,
            'price_per_seat' => 25.00,
            'vehicle_description' => 'Clio 5',
            'description' => 'Je ne prends pas de bagages volumineux.'
        ],
        [
            'origin' => 'Bizerte',
            'destination' => 'Nabeul',
            'departure_date' => date('Y-m-d H:i:s', strtotime('+3 days 14 hours')),
            'seats_available' => 4,
            'price_per_seat' => 18.00,
            'vehicle_description' => 'Kia Rio',
            'description' => 'Départ à l\'heure.'
        ],
        [
            'origin' => 'Hammamet',
            'destination' => 'Djerba',
            'departure_date' => date('Y-m-d H:i:s', strtotime('+5 days 7 hours')),
            'seats_available' => 1,
            'price_per_seat' => 45.00,
            'vehicle_description' => 'Hyundai i20',
            'description' => 'Long trajet, arrêts prévus pour se reposer.'
        ],
        [
            'origin' => 'Monastir',
            'destination' => 'Gabès',
            'departure_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'seats_available' => 3,
            'price_per_seat' => 30.00,
            'vehicle_description' => 'Peugeot 208',
            'description' => 'Musique et bonne ambiance garanties !'
        ]
    ];

    $insert_ride_query = "INSERT INTO rides (driver_id, origin, destination, departure_date, seats_available, price_per_seat, vehicle_description, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($insert_ride_query);

    $rides_added_count = 0;
    foreach ($rides as $ride) {
        $stmt->bind_param(
            'isssisds',
            $driver_id,
            $ride['origin'],
            $ride['destination'],
            $ride['departure_date'],
            $ride['seats_available'],
            $ride['price_per_seat'],
            $ride['vehicle_description'],
            $ride['description']
        );
        if ($stmt->execute()) {
            $rides_added_count++;
        }
    }
    echo "$rides_added_count nouveaux trajets ajoutés avec succès.\n";
    $stmt->close();

    // Valider la transaction
    $db->commit();
    echo "Script de seeding terminé avec succès !\n";

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollback();
    echo "ERREUR: Une exception a été attrapée. La transaction a été annulée.\n";
    echo "Message: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo '<a href="../index.php">Retour à l\'accueil</a>';

?>
