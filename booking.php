<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if servisor_id is provided
$servisor_id = isset($_GET['servisor_id']) ? intval($_GET['servisor_id']) : 0;
if ($servisor_id <= 0) {
    header('Location: services.php');
    exit();
}

// Fetch servisor details
$servisor = null;
$table_check = $conn->query("SHOW TABLES LIKE 'servisor_details'");
if ($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare('SELECT * FROM servisor_details WHERE id = ? AND is_approved = 1');
    if (!$stmt) {
        redirectWithMessage('services.php', 'Database error: ' . mysqli_error($conn), 'error');
    }
    $stmt->bind_param('i', $servisor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $servisor = $result->fetch_assoc();
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare('SELECT *, service_type as service_category, 0 as base_fee FROM servisors WHERE id = ? AND is_approved = 1');
    if (!$stmt) {
        redirectWithMessage('services.php', 'Database error: ' . mysqli_error($conn), 'error');
    }
    $stmt->bind_param('i', $servisor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $servisor = $result->fetch_assoc();
        $servisor['area'] = 'Jaffna';
        $servisor['base_fee'] = 2500;
        $servisor['rating'] = 0;
        $servisor['total_reviews'] = 0;
    }
    $stmt->close();
}

if (!$servisor) {
    header('Location: services.php');
    exit();
}

// Fetch user data if logged in
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT name, email, phone, address FROM users WHERE id = ?');
    if (!$stmt) {
        $message = '<div class="card" style="color:red;">Database error fetching user data: ' . mysqli_error($conn) . '</div>';
    } else {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitizeInput($_POST['name'] ?? '');
    $customer_phone = sanitizeInput($_POST['phone'] ?? '');
    $customer_email = sanitizeInput($_POST['email'] ?? '');
    $customer_address = sanitizeInput($_POST['address'] ?? '');
    $booking_date = sanitizeInput($_POST['date'] ?? '');
    $booking_time = sanitizeInput($_POST['time'] ?? '');
    $service_description = sanitizeInput($_POST['message'] ?? '');
    $selected_servisor_id = intval($_POST['servisor'] ?? 0);
    
    // Validation
    if (empty($customer_name) || empty($customer_phone) || empty($customer_address) || 
        empty($booking_date) || empty($booking_time) || empty($service_description) || 
        $selected_servisor_id <= 0) {
        $message = '<div class="card" style="color:red;">All fields are required.</div>';
    } elseif (!isValidPhone($customer_phone)) {
        $message = '<div class="card" style="color:red;">Invalid phone number format. Use +94 or 0 followed by 9 digits (e.g., 0771234567).</div>';
    } elseif (!empty($customer_email) && !isValidEmail($customer_email)) {
        $message = '<div class="card" style="color:red;">Invalid email address.</div>';
    } else {
        // Generate booking number
        $bookingNumber = generateBookingNumber();
        
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, servisor_id, customer_name, customer_phone, customer_address, booking_date, booking_time, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $userId = $_SESSION['user_id'] ?? null;
        $stmt->bind_param('iissssss', 
            $userId,
            $selected_servisor_id,
            $customer_name,
            $customer_phone,
            $customer_address,
            $booking_date,
            $booking_time,
            $service_description
        );
        
        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;
            $stmt->close();
            
            // Store booking data for payment
            $_SESSION['booking_data'] = [
                'booking_id' => $bookingId,
                'servisor_id' => $selected_servisor_id,
                'servisor_name' => $servisor['name'],
                'service_name' => $servisor['service_category'] ?? $servisor['service_type'],
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email ?: null,
                'customer_address' => $customer_address,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'service_description' => $service_description,
                'estimated_cost' => $servisor['base_fee'] ?? 2500
            ];
            
            header('Location: payment.php');
            exit();
        } else {
            $message = '<div class="card" style="color:red;">Failed to create booking. Please try again.</div>';
            $stmt->close();
        }
    }
}
?>

<main class="container">
    <h2>Book <?php echo htmlspecialchars($servisor['name'] ?? 'a Service'); ?></h2>
    
    <?php if (!empty($message)) echo $message; ?>
    <?php displayFlashMessage(); ?>
    
    <?php if ($servisor): ?>
    <div class="card" style="max-width:500px;margin:2rem auto;text-align:center;">
        <?php if (!empty($servisor['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="<?php echo htmlspecialchars($servisor['name']); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
        <?php else: ?>
            <i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($servisor['name']); ?></h3>
        <span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;"><?php echo htmlspecialchars($servisor['service_category']); ?></span>
        <p><strong>Area:</strong> <?php echo htmlspecialchars($servisor['area']); ?></p>
        <p><strong>Base Fee:</strong> <?php echo formatCurrency($servisor['base_fee']); ?></p>
        <?php if ($servisor['rating'] > 0): ?>
        <p><strong>Rating:</strong> 
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa fa-star" style="color:<?php echo $i <= $servisor['rating'] ? '#ffc107' : '#e0e0e0'; ?>"></i>
            <?php endfor; ?>
            (<?php echo $servisor['total_reviews']; ?> reviews)
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="form">
        <h3>Booking Details</h3>
        
        <label for="name">Your Name: <span style="color:red;">*</span></label>
        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($user_data['name'] ?? ''); ?>">
        
        <label for="phone">Phone Number: <span style="color:red;">*</span></label>
        <input type="text" id="phone" name="phone" required placeholder="0771234567 or +94771234567" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($user_data['phone'] ?? ''); ?>">
        
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($user_data['email'] ?? ''); ?>">
        
        <label for="address">Service Address: <span style="color:red;">*</span></label>
        <textarea id="address" name="address" required placeholder="Enter the full address where service is needed"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
        
        <label for="date">Preferred Date: <span style="color:red;">*</span></label>
        <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
        
        <label for="time">Preferred Time: <span style="color:red;">*</span></label>
        <input type="time" id="time" name="time" required value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
        
        <label for="servisor">Select Servisor: <span style="color:red;">*</span></label>
        <select id="servisor" name="servisor" required>
            <option value="">Choose a servisor</option>
            <?php
            $table_check = $conn->query("SHOW TABLES LIKE 'servisor_details'");
            if ($table_check && $table_check->num_rows > 0) {
                $servisors = $conn->query('SELECT * FROM servisor_details WHERE is_approved = 1 AND is_active = 1 ORDER BY service_category, name');
                $current_category = '';
                while ($row = $servisors->fetch_assoc()) {
                    if ($current_category !== $row['service_category']) {
                        if ($current_category !== '') echo '</optgroup>';
                        echo '<optgroup label="' . htmlspecialchars($row['service_category']) . '">';
                        $current_category = $row['service_category'];
                    }
                    $selected = ($servisor_id && $servisor_id == $row['id']) ? 'selected' : '';
                    $rating_text = $row['rating'] > 0 ? ' - ' . $row['rating'] . '/5' : '';
                    echo '<option value="' . $row['id'] . '" ' . $selected . '>' . 
                         htmlspecialchars($row['name'] . ' (' . $row['area'] . ')' . $rating_text) . '</option>';
                }
                if ($current_category !== '') echo '</optgroup>';
            } else {
                $servisors = $conn->query('SELECT *, service_type as service_category FROM servisors WHERE is_approved = 1 ORDER BY service_type, name');
                $current_category = '';
                while ($row = $servisors->fetch_assoc()) {
                    if ($current_category !== $row['service_category']) {
                        if ($current_category !== '') echo '</optgroup>';
                        echo '<optgroup label="' . htmlspecialchars($row['service_category']) . '">';
                        $current_category = $row['service_category'];
                    }
                    $selected = ($servisor_id && $servisor_id == $row['id']) ? 'selected' : '';
                    echo '<option value="' . $row['id'] . '" ' . $selected . '>' . 
                         htmlspecialchars($row['name'] . ' (Jaffna)') . '</option>';
                }
                if ($current_category !== '') echo '</optgroup>';
            }
            ?>
        </select>
        
        <label for="message">Service Description: <span style="color:red;">*</span></label>
        <textarea id="message" name="message" required placeholder="Describe your specific requirements or any additional details"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
        
        <button type="submit" class="btn">Proceed to Payment</button>
    </form>
    
    <div style="text-align:center;margin-top:2rem;">
        <a href="services.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Services
        </a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
