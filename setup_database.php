<?php
// Database setup script for Jaffna Services Booking Website
// Run this script once to create the database and tables

$host = 'localhost';
$user = 'root';
$pass = '';

// First, connect without specifying a database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Setting up database...</h2>";

// Create the database
$sql = "CREATE DATABASE IF NOT EXISTS `service` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p>✓ Database 'service' created successfully</p>";
} else {
    echo "<p>Error creating database: " . $conn->error . "</p>";
}

// Select the database
$conn->select_db('service');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `is_admin` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p>✓ Users table created successfully</p>";
} else {
    echo "<p>Error creating users table: " . $conn->error . "</p>";
}

// Create servisors table
$sql = "CREATE TABLE IF NOT EXISTS `servisors` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p>✓ Servisors table created successfully</p>";
} else {
    echo "<p>Error creating servisors table: " . $conn->error . "</p>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS `bookings` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p>✓ Bookings table created successfully</p>";
} else {
    echo "<p>Error creating bookings table: " . $conn->error . "</p>";
}

// Create contacts table
$sql = "CREATE TABLE IF NOT EXISTS `contacts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p>✓ Contacts table created successfully</p>";
} else {
    echo "<p>Error creating contacts table: " . $conn->error . "</p>";
}

// Insert sample data
$check_admin = $conn->query("SELECT id FROM users WHERE email = 'admin@jaffnaservices.com'");
if ($check_admin->num_rows == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, phone, password, is_admin) VALUES ('Admin User', 'admin@jaffnaservices.com', '0771234567', '$admin_password', 1)";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✓ Admin user created (email: admin@jaffnaservices.com, password: admin123)</p>";
    }
}

$check_servisors = $conn->query("SELECT id FROM servisors LIMIT 1");
if ($check_servisors->num_rows == 0) {
    $servisor_password = password_hash('password', PASSWORD_DEFAULT);
    $sql = "INSERT INTO servisors (name, email, phone, password, service_type, is_approved) VALUES 
    ('John Plumber', 'john@example.com', '0771111111', '$servisor_password', 'Plumber', 1),
    ('Mike Electrician', 'mike@example.com', '0772222222', '$servisor_password', 'Electrician', 1),
    ('David Carpenter', 'david@example.com', '0773333333', '$servisor_password', 'Carpenter', 1)";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✓ Sample servisors created</p>";
    }
}

$conn->close();

echo "<h3>Database setup completed!</h3>";
echo "<p><a href='index.php'>Go to homepage</a></p>";
?> 