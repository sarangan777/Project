-- Database setup for Jaffna Services Booking Website
-- Run this script in phpMyAdmin or MySQL command line

-- Create the database
CREATE DATABASE IF NOT EXISTS `service` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `service`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `is_admin` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create servisors table
CREATE TABLE IF NOT EXISTS `servisors` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `service_type` enum('Plumber', 'Mason', 'Electrician', 'Carpenter', 'Technician') NOT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `is_approved` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `servisor_id` int(11) NOT NULL,
    `customer_name` varchar(100) NOT NULL,
    `customer_phone` varchar(20) NOT NULL,
    `customer_address` text NOT NULL,
    `booking_date` date NOT NULL,
    `booking_time` time NOT NULL,
    `message` text,
    `status` enum('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`servisor_id`) REFERENCES `servisors`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- Insert a sample admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `is_admin`) VALUES 
('Admin User', 'admin@jaffnaservices.com', '0771234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insert sample servisors
INSERT INTO `servisors` (`name`, `email`, `phone`, `password`, `service_type`, `is_approved`) VALUES 
('John Plumber', 'john@example.com', '0771111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Plumber', 1),
('Mike Electrician', 'mike@example.com', '0772222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Electrician', 1),
('David Carpenter', 'david@example.com', '0773333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carpenter', 1);

-- Note: The password hash above corresponds to 'password' - change this in production 