<?php
// Common functions for Jaffna Services

// Database connection with error handling
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number (Sri Lankan format)
function isValidPhone($phone) {
    return preg_match('/^(\+94|0)[0-9]{9}$/', $phone);
}

// Generate booking number
function generateBookingNumber() {
    return 'BK' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        $color = $type === 'error' ? 'red' : ($type === 'success' ? 'green' : '#007BFF');
        
        echo "<div class='card' style='color: $color; background: " . 
             ($type === 'error' ? '#ffebee' : ($type === 'success' ? '#e8f5e9' : '#e3f0ff')) . 
             "; margin-bottom: 1rem;'>$message</div>";
        
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}

// Upload file with validation
function uploadFile($file, $targetDir = UPLOAD_DIR) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $fileSize = $file['size'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file size
    if ($fileSize > MAX_FILE_SIZE) {
        return false;
    }
    
    // Validate file extension
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Generate unique filename
    $newFileName = uniqid('servisor_', true) . '.' . $fileExtension;
    $targetPath = $targetDir . $newFileName;
    
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $targetPath)) {
        return $newFileName;
    }
    
    return false;
}

// Format currency
function formatCurrency($amount) {
    return DEFAULT_CURRENCY . ' ' . number_format($amount, 2);
}

// Get service categories
function getServiceCategories($conn) {
    $result = $conn->query("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get areas
function getAreas($conn) {
    $result = $conn->query("SELECT * FROM areas WHERE is_active = 1 ORDER BY name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get payment methods
function getPaymentMethods($conn) {
    $result = $conn->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get booking statuses
function getBookingStatuses($conn) {
    $result = $conn->query("SELECT * FROM booking_statuses ORDER BY id");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Log admin activity
function logAdminActivity($conn, $adminId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;
    
    $stmt->bind_param('ississss', $adminId, $action, $tableName, $recordId, $oldValuesJson, $newValuesJson, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
}

// Send email notification (basic implementation)
function sendEmail($to, $subject, $message, $isHTML = true) {
    // This is a basic implementation. In production, use a proper email library like PHPMailer
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

// Pagination helper
function getPaginationData($totalItems, $currentPage = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1
    ];
}
?>