<?php
// Test script to verify database functionality
include 'includes/db.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if database connection works
if ($conn->ping()) {
    echo "<p style='color:green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color:red;'>✗ Database connection failed</p>";
    exit;
}

// Test 2: Check if tables exist
$tables = ['users', 'servisors', 'bookings', 'contacts'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color:green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color:red;'>✗ Table '$table' does not exist</p>";
    }
}

// Test 3: Check if sample data exists
$result = $conn->query("SELECT COUNT(*) as count FROM servisors WHERE is_approved = 1");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green;'>✓ Found " . $row['count'] . " approved servisors</p>";
} else {
    echo "<p style='color:red;'>✗ Error checking servisors</p>";
}

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green;'>✓ Found " . $row['count'] . " admin users</p>";
} else {
    echo "<p style='color:red;'>✗ Error checking users</p>";
}

// Test 4: Show sample servisors
echo "<h3>Sample Servisors:</h3>";
$result = $conn->query("SELECT id, name, service_type, phone FROM servisors WHERE is_approved = 1 LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['service_type']) . ") - " . htmlspecialchars($row['phone']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:orange;'>No approved servisors found</p>";
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='index.php'>Homepage</a></p>";
echo "<p><a href='services.php'>Services Page</a></p>";
echo "<p><a href='contact.php'>Contact Page</a></p>";
echo "<p><a href='signup.php'>User Signup</a></p>";
echo "<p><a href='servisor_signup.php'>Servisor Signup</a></p>";
echo "<p><a href='login.php'>Login</a></p>";

$conn->close();
?> 