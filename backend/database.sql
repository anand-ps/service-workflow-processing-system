-- Database Schema for On-Demand Local Service Booking Application
-- Project: BCSP-064 Final Year Project

CREATE DATABASE IF NOT EXISTS `service_booking_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `service_booking_db`;

-- 1. Users Table (Handles Customers, Providers, and Admins)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `role` ENUM('user', 'provider', 'admin') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Services Table (Master list of service categories)
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `icon` VARCHAR(50) DEFAULT 'wrench',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Providers Table (Extended details for service workers)
CREATE TABLE IF NOT EXISTS `providers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `service_id` INT NOT NULL,
    `experience_years` INT DEFAULT 1,
    `hourly_rate` DECIMAL(10, 2) NOT NULL DEFAULT 250.00,
    `location` VARCHAR(150) NOT NULL,
    `is_approved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bookings Table (Manages service requests and status lifecycle)
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `provider_id` INT NOT NULL,
    `service_id` INT NOT NULL,
    `booking_date` DATE NOT NULL,
    `booking_time` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `problem_desc` TEXT NOT NULL,
    `status` ENUM('Pending', 'Accepted', 'On the Way', 'Completed', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Feedback Table (Customer ratings and reviews)
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `provider_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `comments` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Initial Data for Testing & Demonstration

-- Insert Default Service Categories
INSERT INTO `services` (`id`, `name`, `description`, `icon`) VALUES
(1, 'Electrician', 'Wiring repair, appliance fixes, short circuit repair, and light fitting', 'zap'),
(2, 'Plumber', 'Pipe leak repair, tap replacement, bathroom fittings, and drain clearing', 'droplet'),
(3, 'Carpenter', 'Furniture repairs, door lock fitting, woodwork, and modular fittings', 'hammer'),
(4, 'Painter', 'Interior and exterior wall painting, waterproof coating, and touch-ups', 'brush'),
(5, 'Mechanic', 'Two-wheeler and four-wheeler breakdown assistance, servicing, and tune-ups', 'tool'),
(6, 'Home Cleaning', 'Full house deep cleaning, bathroom sanitation, and kitchen degreasing', 'sparkles')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert Default Admin Account (Password: admin123)
-- bcrypt hash for 'admin123': $2y$10$w80K/z5o8e0T038h2EaK6uKevK/O.aDkP9i3PzKkWyLg4Xw4k85x6 (will be set via setup.php with password_hash)
