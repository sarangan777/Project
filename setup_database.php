<?php
// Complete Database Setup Script for Jaffna Services
// Run this script to create the normalized database structure

$host = 'localhost';
$user = 'root';
$pass = '';

// Connect without specifying database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Setting up Jaffna Services Database...</h2>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `service` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Database 'service' created successfully</p>";
} else {
    echo "<p style='color:red;'>Error creating database: " . $conn->error . "</p>";
}

// Select database
$conn->select_db('service');

// Drop existing tables if they exist (for clean setup)
$tables_to_drop = ['admin_logs', 'reviews', 'payment_transactions', 'bookings', 'servisors', 'contacts', 'users', 'booking_statuses', 'payment_methods', 'areas', 'service_categories'];
foreach ($tables_to_drop as $table) {
    $conn->query("DROP TABLE IF EXISTS `$table`");
}

// Drop views if they exist
$conn->query("DROP VIEW IF EXISTS `servisor_details`");
$conn->query("DROP VIEW IF EXISTS `booking_details`");

// 1. Service categories table
$sql = "CREATE TABLE `service_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE,
    `description` text,
    `icon` varchar(50),
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Service categories table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 2. Areas table
$sql = "CREATE TABLE `areas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `district` varchar(50) DEFAULT 'Jaffna',
    `postal_code` varchar(10),
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Areas table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 3. Payment methods table
$sql = "CREATE TABLE `payment_methods` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `code` varchar(20) NOT NULL UNIQUE,
    `description` text,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Payment methods table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 4. Booking statuses table
$sql = "CREATE TABLE `booking_statuses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE,
    `description` text,
    `color` varchar(7) DEFAULT '#007BFF',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Booking statuses table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 5. Users table
$sql = "CREATE TABLE `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Users table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 6. Servisors table
$sql = "CREATE TABLE `servisors` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Servisors table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 7. Bookings table
$sql = "CREATE TABLE `bookings` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Bookings table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 8. Reviews table
$sql = "CREATE TABLE `reviews` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Reviews table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 9. Contacts table
$sql = "CREATE TABLE `contacts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Contacts table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// 10. Admin logs table
$sql = "CREATE TABLE `admin_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Admin logs table created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Insert initial data
echo "<h3>Inserting initial data...</h3>";

// Service categories
$sql = "INSERT INTO `service_categories` (`name`, `description`, `icon`) VALUES
('Plumber', 'Water pipe installation, repair, and maintenance services', 'fa-wrench'),
('Electrician', 'Electrical wiring, repair, and installation services', 'fa-bolt'),
('Mason', 'Construction, brickwork, and masonry services', 'fa-hammer'),
('Carpenter', 'Wood work, furniture making, and carpentry services', 'fa-tools'),
('Technician', 'General repair and maintenance services', 'fa-cog')";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Service categories inserted</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Areas
$sql = "INSERT INTO `areas` (`name`, `district`, `postal_code`) VALUES
('Jaffna Town', 'Jaffna', '40000'),
('Nallur', 'Jaffna', '40001'),
('Chunnakam', 'Jaffna', '40002'),
('Tellippalai', 'Jaffna', '40003'),
('Point Pedro', 'Jaffna', '40004'),
('Karainagar', 'Jaffna', '40005'),
('Kayts', 'Jaffna', '40006'),
('Delft', 'Jaffna', '40007')";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Areas inserted</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Payment methods
$sql = "INSERT INTO `payment_methods` (`name`, `code`, `description`) VALUES
('Cash on Delivery', 'COD', 'Pay cash when service is completed'),
('Credit/Debit Card', 'CARD', 'Pay online using credit or debit card'),
('Bank Transfer', 'BANK', 'Direct bank transfer'),
('Mobile Payment', 'MOBILE', 'Pay using mobile payment apps')";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Payment methods inserted</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Booking statuses
$sql = "INSERT INTO `booking_statuses` (`name`, `description`, `color`) VALUES
('Pending', 'Booking submitted, waiting for confirmation', '#FFA500'),
('Confirmed', 'Booking confirmed by servisor', '#007BFF'),
('In Progress', 'Service work is in progress', '#17A2B8'),
('Completed', 'Service completed successfully', '#28A745'),
('Cancelled', 'Booking cancelled', '#DC3545'),
('Rescheduled', 'Booking date/time changed', '#6F42C1')";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Booking statuses inserted</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO `users` (`name`, `email`, `phone`, `password`, `is_admin`) VALUES
('System Administrator', 'admin@jaffnaservices.com', '0771234567', '$admin_password', 1)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Admin user created (email: admin@jaffnaservices.com, password: admin123)</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Sample servisors
$servisor_password = password_hash('password', PASSWORD_DEFAULT);
$sql = "INSERT INTO `servisors` (`name`, `email`, `phone`, `password`, `service_category_id`, `area_id`, `description`, `experience_years`, `base_fee`, `is_approved`) VALUES
('Rajan Kumar', 'rajan.plumber@gmail.com', '0771111111', '$servisor_password', 1, 1, 'Experienced plumber with 10+ years in residential and commercial plumbing', 10, 2500.00, 1),
('Suresh Electrical', 'suresh.electric@gmail.com', '0772222222', '$servisor_password', 2, 2, 'Licensed electrician specializing in home wiring and electrical repairs', 8, 3000.00, 1),
('Murugan Mason', 'murugan.mason@gmail.com', '0773333333', '$servisor_password', 3, 1, 'Expert mason for construction and renovation projects', 12, 2000.00, 1),
('Selvam Carpenter', 'selvam.wood@gmail.com', '0774444444', '$servisor_password', 4, 3, 'Custom furniture maker and carpentry specialist', 15, 2800.00, 1),
('Kumar Technician', 'kumar.tech@gmail.com', '0775555555', '$servisor_password', 5, 1, 'Multi-skilled technician for various repair services', 6, 2200.00, 1)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Sample servisors inserted</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

// Create views
echo "<h3>Creating database views...</h3>";

$sql = "CREATE VIEW `servisor_details` AS
SELECT 
    s.id,
    s.name,
    s.email,
    s.phone,
    s.service_category_id,
    s.area_id,
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
JOIN areas a ON s.area_id = a.id";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Servisor details view created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

$sql = "CREATE VIEW `booking_details` AS
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
JOIN booking_statuses bs ON b.status_id = bs.id";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ Booking details view created</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

$conn->close();

echo "<h2 style='color:green;'>Database setup completed successfully!</h2>";
echo "<p><strong>Admin Login:</strong> admin@jaffnaservices.com / admin123</p>";
echo "<p><strong>Sample Servisor Login:</strong> rajan.plumber@gmail.com / password</p>";
echo "<p><a href='index.php' style='background:#007BFF;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Homepage</a></p>";
?>