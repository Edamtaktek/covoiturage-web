-- ===============================================
-- Schéma de Base de Données: Marketplace de Covoiturage
-- ===============================================
-- Cette base de données gère:
-- - Les utilisateurs (conducteurs et passagers)
-- - Les trajets proposés
-- - Les réservations
-- - Les avis et commentaires
-- - La messagerie entre utilisateurs

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS `covoiturage_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Sélectionner la base de données
USE `covoiturage_db`;

-- ===============================================
-- 1. TABLE: users (Utilisateurs)
-- ===============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `phone` VARCHAR(20),
  `profile_picture` VARCHAR(255),
  `bio` TEXT,
  `location` VARCHAR(255),
  `rating` FLOAT DEFAULT 5.0,
  `reviews_count` INT DEFAULT 0,
  `is_driver` TINYINT(1) DEFAULT 0,
  `is_admin` TINYINT(1) DEFAULT 0,
  `vehicle_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  UNIQUE KEY `username_unique` (`username`),
  INDEX `status_idx` (`status`),
  INDEX `is_driver_idx` (`is_driver`),
  INDEX `is_admin_idx` (`is_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 2. TABLE: vehicles (Véhicules)
-- ===============================================
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `vehicle_type` VARCHAR(100) NOT NULL,
  `brand` VARCHAR(100),
  `model` VARCHAR(100),
  `color` VARCHAR(50),
  `license_plate` VARCHAR(20) UNIQUE NOT NULL,
  `seats_total` INT NOT NULL DEFAULT 4,
  `registration_year` INT,
  `description` TEXT,
  `photo_url` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `license_plate_unique` (`license_plate`),
  INDEX `owner_idx` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 3. TABLE: rides (Trajets)
-- ===============================================
CREATE TABLE IF NOT EXISTS `rides` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `driver_id` INT NOT NULL,
  `origin` VARCHAR(255) NOT NULL,
  `destination` VARCHAR(255) NOT NULL,
  `departure_date` DATETIME NOT NULL,
  `seats_available` INT NOT NULL,
  `price_per_seat` DECIMAL(10, 2) NOT NULL,
  `vehicle_description` VARCHAR(255) NULL,
  `description` TEXT,
  `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `rejection_reason` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `driver_idx` (`driver_id`),
  INDEX `departure_date_idx` (`departure_date`),
  INDEX `status_idx` (`status`),
  INDEX `approval_status_idx` (`approval_status`),
  INDEX `origin_idx` (`origin`),
  INDEX `destination_idx` (`destination`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 4. TABLE: bookings (Réservations)
-- ===============================================
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `passenger_id` INT NOT NULL,
  `seats_booked` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10, 2) NOT NULL,
  `booking_status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
  `cancellation_reason` TEXT,
  `cancelled_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`passenger_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `ride_idx` (`ride_id`),
  INDEX `passenger_idx` (`passenger_id`),
  INDEX `status_idx` (`booking_status`),
  UNIQUE KEY `ride_passenger_unique` (`ride_id`, `passenger_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 5. TABLE: reviews (Avis et commentaires)
-- ===============================================
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `from_user_id` INT NOT NULL,
  `to_user_id` INT NOT NULL,
  `ride_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `comment` TEXT,
  `is_verified_ride` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE,
  INDEX `from_user_idx` (`from_user_id`),
  INDEX `to_user_idx` (`to_user_id`),
  INDEX `rating_idx` (`rating`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 6. TABLE: messages (Messagerie)
-- ===============================================
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `recipient_id` INT NOT NULL,
  `message_text` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME,
  `ride_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE SET NULL,
  INDEX `sender_idx` (`sender_id`),
  INDEX `recipient_idx` (`recipient_id`),
  INDEX `is_read_idx` (`is_read`),
  INDEX `created_at_idx` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 7. TABLE: payments (Paiements) - Optional
-- ===============================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` ENUM('card', 'bank_transfer', 'cash', 'wallet') DEFAULT 'card',
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `transaction_id` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `booking_idx` (`booking_id`),
  INDEX `user_idx` (`user_id`),
  INDEX `payment_status_idx` (`payment_status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 8. TABLE: favorites (Trajets favoris)
-- ===============================================
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `ride_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_ride_unique` (`user_id`, `ride_id`),
  INDEX `user_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 9. TABLE: notifications (Notifications)
-- ===============================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `type` ENUM('booking', 'review', 'message', 'ride_update', 'cancellation') DEFAULT 'booking',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `related_id` INT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`),
  INDEX `is_read_idx` (`is_read`),
  INDEX `created_at_idx` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- INSERTION DE DONNÉES D'EXEMPLE (Optionnel)
-- ===============================================

-- Exemple d'utilisateurs
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `phone`, `bio`, `location`, `rating`, `is_driver`, `is_admin`, `status`) VALUES
('admin', 'admin@covoiturage.com', SHA2('admin123', 256), 'Admin', 'System', '0600000000', 'Administrateur système', 'Tunis, Tunisie', 5.0, 0, 1, 'active'),
('john_driver', 'john@example.com', SHA2('password123', 256), 'John', 'Dupont', '0601020304', 'Conducteur expérimenté', 'Paris, France', 4.8, 1, 0, 'active'),
('marie_passenger', 'marie@example.com', SHA2('password123', 256), 'Marie', 'Martin', '0607080910', 'Voyageur régulier', 'Lyon, France', 4.9, 0, 0, 'active'),
('pierre_driver', 'pierre@example.com', SHA2('password123', 256), 'Pierre', 'Bernard', '0611121314', 'Covoitureur professionnel', 'Marseille, France', 4.7, 1, 0, 'active');

-- Exemple de véhicules
INSERT INTO `vehicles` (`owner_id`, `vehicle_type`, `brand`, `model`, `color`, `license_plate`, `seats_total`, `registration_year`, `description`) VALUES
(2, 'Voiture', 'Peugeot', '308', 'Bleu', 'AB-123-CD', 5, 2020, 'Voiture confortable et économe en carburant'),
(4, 'Monospace', 'Renault', 'Scenic', 'Noir', 'EF-456-GH', 7, 2019, 'Grand monospace avec climatisation');

-- Exemple de trajets
INSERT INTO `rides` (`driver_id`, `vehicle_id`, `origin`, `destination`, `departure_date`, `arrival_date`, `seats_available`, `price_per_seat`, `total_price`, `description`, `status`, `approval_status`) VALUES
(2, 1, 'Paris', 'Lyon', '2025-10-25 08:00:00', '2025-10-25 12:30:00', 3, 25.00, 75.00, 'Trajet confortable avec pauses', 'active', 'approved'),
(4, 2, 'Marseille', 'Nice', '2025-10-26 14:00:00', '2025-10-26 16:00:00', 5, 15.00, 75.00, 'Petite distance, route côtière', 'active', 'approved');

-- ===============================================
-- INDEXES SUPPLÉMENTAIRES POUR LES PERFORMANCES
-- ===============================================

-- Optimiser les recherches de trajet
CREATE INDEX idx_rides_route ON `rides` (`origin`, `destination`, `departure_date`);

-- Optimiser les recherches de réservation
CREATE INDEX idx_bookings_ride_status ON `bookings` (`ride_id`, `booking_status`);

-- ===============================================
-- FIN DU SCRIPT
-- ===============================================
