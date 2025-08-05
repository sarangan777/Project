<?php
// Include configuration and functions
require_once 'config.php';
require_once 'functions.php';

// Get database connection
$conn = getDBConnection();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?> 