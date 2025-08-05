-- Normalized Database Schema for Jaffna Services (3NF)
-- Drop existing database and recreate with proper normalization

DROP DATABASE IF EXISTS `service`;
CREATE DATABASE `service` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `service`;

-- 1. Users table (customers)
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `address` text,
    `is_admin` tinyint(1) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Service categories table
CREATE TABLE `service_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE,
    `description` text,
    `icon` varchar(50),
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Areas/Locations table
CREATE TABLE `areas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `district` varchar(50) DEFAULT 'Jaffna',
    `postal_code` varchar(10),
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Servisors table (service providers)
CREATE TABLE `servisors` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `service_category_id` int(11) NOT NULL,
    `area_id` int(11) NOT NULL,
    `profile_image` varchar(255),
    `description` text,
    `experience_years` int(11) DEFAULT 0,
    `base_fee` decimal(10,2) DEFAULT 0.00,
    `rating` decimal(3,2) DEFAULT 0.00,
    `total_reviews` int(11) DEFAULT 0,
    `is_approved` tinyint(1) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`service_category_id`) REFERENCES `service_categories`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE RESTRICT,
    INDEX `idx_email` (`email`),
    INDEX `idx_service_area` (`service_category_id`, `area_id`),
    INDEX `idx_approved` (`is_approved`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Payment methods table
CREATE TABLE `payment_methods` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `code` varchar(20) NOT NULL UNIQUE,
    `description` text,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Booking status table
CREATE TABLE `booking_statuses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE,
    `description` text,
    `color` varchar(7) DEFAULT '#007BFF',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bookings table
CREATE TABLE `bookings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `booking_number` varchar(20) NOT NULL UNIQUE,
    `user_id` int(11),
    `servisor_id` int(11) NOT NULL,
    `customer_name` varchar(100) NOT NULL,
    `customer_phone` varchar(20) NOT NULL,
    `customer_email` varchar(100),
    `customer_address` text NOT NULL,
    `booking_date` date NOT NULL,
    `booking_time` time NOT NULL,
    `service_description` text,
    `estimated_cost` decimal(10,2),
    `final_cost` decimal(10,2),
    `payment_method_id` int(11) NOT NULL,
    `status_id` int(11) NOT NULL DEFAULT 1,
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`servisor_id`) REFERENCES `servisors`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`status_id`) REFERENCES `booking_statuses`(`id`) ON DELETE RESTRICT,
    INDEX `idx_booking_number` (`booking_number`),
    INDEX `idx_booking_date` (`booking_date`),
    INDEX `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Payment transactions table
CREATE TABLE `payment_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `booking_id` int(11) NOT NULL,
    `transaction_id` varchar(100),
    `amount` decimal(10,2) NOT NULL,
    `payment_method_id` int(11) NOT NULL,
    `payment_status` enum('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `payment_date` timestamp NULL,
    `gateway_response` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE RESTRICT,
    INDEX `idx_booking` (`booking_id`),
    INDEX `idx_transaction` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Reviews table
CREATE TABLE `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `booking_id` int(11) NOT NULL,
    `user_id` int(11),
    `servisor_id` int(11) NOT NULL,
    `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review_text` text,
    `is_approved` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`servisor_id`) REFERENCES `servisors`(`id`) ON DELETE CASCADE,
    INDEX `idx_servisor_rating` (`servisor_id`, `rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Contact messages table
CREATE TABLE `contacts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20),
    `subject` varchar(200),
    `message` text NOT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `replied_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_read_status` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Admin activity log
CREATE TABLE `admin_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) NOT NULL,
    `action` varchar(100) NOT NULL,
    `table_name` varchar(50),
    `record_id` int(11),
    `old_values` json,
    `new_values` json,
    `ip_address` varchar(45),
    `user_agent` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_admin_action` (`admin_id`, `action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial data

-- Service categories
INSERT INTO `service_categories` (`name`, `description`, `icon`) VALUES
('Plumber', 'Water pipe installation, repair, and maintenance services', 'fa-wrench'),
('Electrician', 'Electrical wiring, repair, and installation services', 'fa-bolt'),
('Mason', 'Construction, brickwork, and masonry services', 'fa-hammer'),
('Carpenter', 'Wood work, furniture making, and carpentry services', 'fa-tools'),
('Technician', 'General repair and maintenance services', 'fa-cog');

-- Areas in Jaffna
INSERT INTO `areas` (`name`, `district`, `postal_code`) VALUES
('Jaffna Town', 'Jaffna', '40000'),
('Nallur', 'Jaffna', '40001'),
('Chunnakam', 'Jaffna', '40002'),
('Tellippalai', 'Jaffna', '40003'),
('Point Pedro', 'Jaffna', '40004'),
('Karainagar', 'Jaffna', '40005'),
('Kayts', 'Jaffna', '40006'),
('Delft', 'Jaffna', '40007');

-- Payment methods
INSERT INTO `payment_methods` (`name`, `code`, `description`) VALUES
('Cash on Delivery', 'COD', 'Pay cash when service is completed'),
('Credit/Debit Card', 'CARD', 'Pay online using credit or debit card'),
('Bank Transfer', 'BANK', 'Direct bank transfer'),
('Mobile Payment', 'MOBILE', 'Pay using mobile payment apps');

-- Booking statuses
INSERT INTO `booking_statuses` (`name`, `description`, `color`) VALUES
('Pending', 'Booking submitted, waiting for confirmation', '#FFA500'),
('Confirmed', 'Booking confirmed by servisor', '#007BFF'),
('In Progress', 'Service work is in progress', '#17A2B8'),
('Completed', 'Service completed successfully', '#28A745'),
('Cancelled', 'Booking cancelled', '#DC3545'),
('Rescheduled', 'Booking date/time changed', '#6F42C1');

-- Default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `is_admin`) VALUES
('System Administrator', 'admin@jaffnaservices.com', '0771234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Sample servisors
INSERT INTO `servisors` (`name`, `email`, `phone`, `password`, `service_category_id`, `area_id`, `description`, `experience_years`, `base_fee`, `is_approved`) VALUES
('Rajan Kumar', 'rajan.plumber@gmail.com', '0771111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 'Experienced plumber with 10+ years in residential and commercial plumbing', 10, 2500.00, 1),
('Suresh Electrical', 'suresh.electric@gmail.com', '0772222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 2, 'Licensed electrician specializing in home wiring and electrical repairs', 8, 3000.00, 1),
('Murugan Mason', 'murugan.mason@gmail.com', '0773333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 'Expert mason for construction and renovation projects', 12, 2000.00, 1),
('Selvam Carpenter', 'selvam.wood@gmail.com', '0774444444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 3, 'Custom furniture maker and carpentry specialist', 15, 2800.00, 1),
('Kumar Technician', 'kumar.tech@gmail.com', '0775555555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 1, 'Multi-skilled technician for various repair services', 6, 2200.00, 1);

-- Create views for easier data access
CREATE VIEW `servisor_details` AS
SELECT 
    s.id,
    s.name,
    s.email,
    s.phone,
    sc.name as service_category,
    sc.icon as service_icon,
    a.name as area,
    s.description,
    s.experience_years,
    s.base_fee,
    s.rating,
    s.total_reviews,
    s.is_approved,
    s.is_active,
    s.profile_image,
    s.created_at
FROM servisors s
JOIN service_categories sc ON s.service_category_id = sc.id
JOIN areas a ON s.area_id = a.id;

CREATE VIEW `booking_details` AS
SELECT 
    b.id,
    b.booking_number,
    b.customer_name,
    b.customer_phone,
    b.customer_email,
    b.customer_address,
    b.booking_date,
    b.booking_time,
    s.name as servisor_name,
    s.phone as servisor_phone,
    sc.name as service_category,
    pm.name as payment_method,
    bs.name as status,
    bs.color as status_color,
    b.estimated_cost,
    b.final_cost,
    b.created_at
FROM bookings b
JOIN servisors s ON b.servisor_id = s.id
JOIN service_categories sc ON s.service_category_id = sc.id
JOIN payment_methods pm ON b.payment_method_id = pm.id
JOIN booking_statuses bs ON b.status_id = bs.id;

-- Create triggers to update servisor ratings
DELIMITER //
CREATE TRIGGER update_servisor_rating AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE servisors 
    SET 
        rating = (SELECT AVG(rating) FROM reviews WHERE servisor_id = NEW.servisor_id AND is_approved = 1),
        total_reviews = (SELECT COUNT(*) FROM reviews WHERE servisor_id = NEW.servisor_id AND is_approved = 1)
    WHERE id = NEW.servisor_id;
END//

CREATE TRIGGER update_servisor_rating_on_update AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    UPDATE servisors 
    SET 
        rating = (SELECT AVG(rating) FROM reviews WHERE servisor_id = NEW.servisor_id AND is_approved = 1),
        total_reviews = (SELECT COUNT(*) FROM reviews WHERE servisor_id = NEW.servisor_id AND is_approved = 1)
    WHERE id = NEW.servisor_id;
END//
DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_servisors_category_area ON servisors(service_category_id, area_id, is_approved, is_active);
CREATE INDEX idx_bookings_date_status ON bookings(booking_date, status_id);
CREATE INDEX idx_reviews_servisor_approved ON reviews(servisor_id, is_approved);