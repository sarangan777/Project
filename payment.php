<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Check if booking data exists in session
if (!isset($_SESSION['booking_data'])) {
    redirectWithMessage('services.php', 'Please select a service first.', 'error');
}

$bookingData = $_SESSION['booking_data'];
$paymentMethods = getPaymentMethods($conn);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethodId = intval($_POST['payment_method']);
    $bookingData['payment_method_id'] = $paymentMethodId;
    
    // Get payment method details
    $stmt = $conn->prepare("SELECT * FROM payment_methods WHERE id = ?");
    $stmt->bind_param('i', $paymentMethodId);
    $stmt->execute();
    $paymentMethod = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$paymentMethod) {
        $message = '<div class="card" style="color:red;">Invalid payment method selected.</div>';
    } else {
        // Generate booking number
        $bookingNumber = generateBookingNumber();
        
        // Insert booking - check if new schema tables exist
        $table_check = $conn->query("SHOW TABLES LIKE 'booking_statuses'");
        if ($table_check && $table_check->num_rows > 0) {
            // New schema with normalized tables
            $stmt = $conn->prepare("INSERT INTO bookings (booking_number, user_id, servisor_id, customer_name, customer_phone, customer_email, customer_address, booking_date, booking_time, service_description, estimated_cost, payment_method_id, status_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $userId = $_SESSION['user_id'] ?? null;
            $statusId = 1; // Pending status
            $stmt->bind_param('siissssssdii', 
                $bookingNumber,
                $userId,
                $bookingData['servisor_id'],
                $bookingData['customer_name'],
                $bookingData['customer_phone'],
                $bookingData['customer_email'],
                $bookingData['customer_address'],
                $bookingData['booking_date'],
                $bookingData['booking_time'],
                $bookingData['service_description'],
                $bookingData['estimated_cost'],
                $paymentMethodId,
                $statusId
            );
        } else {
            // Old schema - fallback
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, servisor_id, customer_name, customer_phone, customer_address, booking_date, booking_time, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $userId = $_SESSION['user_id'] ?? null;
            $stmt->bind_param('iissssss', 
                $userId,
                $bookingData['servisor_id'],
                $bookingData['customer_name'],
                $bookingData['customer_phone'],
                $bookingData['customer_address'],
                $bookingData['booking_date'],
                $bookingData['booking_time'],
                $bookingData['service_description']
            );
        }
        
        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;
            $stmt->close();
            
            // Handle different payment methods
            if (isset($paymentMethod['code']) && $paymentMethod['code'] === 'COD') {
                // Cash on Delivery - booking is complete
                $_SESSION['booking_success'] = [
                    'booking_number' => $bookingNumber ?? 'BK' . $bookingId,
                    'payment_method' => 'Cash on Delivery',
                    'message' => 'Your booking has been confirmed! Pay cash when the service is completed.'
                ];
                unset($_SESSION['booking_data']);
                header('Location: booking_success.php');
                exit();
            } elseif (isset($paymentMethod['code']) && $paymentMethod['code'] === 'CARD') {
                // Redirect to card payment
                $_SESSION['payment_data'] = [
                    'booking_id' => $bookingId,
                    'booking_number' => $bookingNumber ?? 'BK' . $bookingId,
                    'amount' => $bookingData['estimated_cost']
                ];
                header('Location: card_payment.php');
                exit();
            } else {
                // Other payment methods
                $_SESSION['booking_success'] = [
                    'booking_number' => $bookingNumber ?? 'BK' . $bookingId,
                    'payment_method' => $paymentMethod['name'] ?? 'Cash on Delivery',
                    'message' => 'Your booking has been confirmed! Payment instructions will be sent to you.'
                ];
                unset($_SESSION['booking_data']);
                header('Location: booking_success.php');
                exit();
            }
        } else {
            $message = '<div class="card" style="color:red;">Failed to create booking. Please try again.</div>';
            $stmt->close();
        }
    }
}
?>

<main class="container">
    <h2 style="text-align:center;">Select Payment Method</h2>
    
    <?php if ($message) echo $message; ?>
    <?php displayFlashMessage(); ?>
    
    <div class="card" style="max-width:600px;margin:2rem auto;">
        <h3>Booking Summary</h3>
        <p><strong>Service:</strong> <?php echo htmlspecialchars($bookingData['service_name']); ?></p>
        <p><strong>Servisor:</strong> <?php echo htmlspecialchars($bookingData['servisor_name']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($bookingData['booking_date']); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($bookingData['booking_time']); ?></p>
        <p><strong>Estimated Cost:</strong> <?php echo formatCurrency($bookingData['estimated_cost']); ?></p>
    </div>
    
    <form method="POST" class="form" style="max-width:500px;">
        <h3>Choose Payment Method</h3>
        
        <?php foreach ($paymentMethods as $method): ?>
        <div style="margin:1rem 0;padding:1rem;border:2px solid #e0e7ef;border-radius:0.7rem;cursor:pointer;" onclick="selectPayment(<?php echo $method['id']; ?>)">
            <label style="cursor:pointer;display:flex;align-items:center;gap:1rem;">
                <input type="radio" name="payment_method" value="<?php echo $method['id']; ?>" required>
                <div>
                    <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                    <?php if ($method['code'] === 'COD'): ?>
                        <span style="background:#28a745;color:white;padding:0.2rem 0.5rem;border-radius:0.3rem;font-size:0.8rem;margin-left:0.5rem;">RECOMMENDED</span>
                    <?php endif; ?>
                    <p style="margin:0.3rem 0 0 0;color:#666;font-size:0.9rem;"><?php echo htmlspecialchars($method['description']); ?></p>
                </div>
            </label>
        </div>
        <?php endforeach; ?>
        
        <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">Proceed with Payment</button>
    </form>
</main>

<script>
function selectPayment(methodId) {
    document.querySelector(`input[value="${methodId}"]`).checked = true;
    
    // Update visual selection
    document.querySelectorAll('div[onclick^="selectPayment"]').forEach(div => {
        div.style.borderColor = '#e0e7ef';
        div.style.background = '#fff';
    });
    
    event.currentTarget.style.borderColor = '#007BFF';
    event.currentTarget.style.background = '#f8f9ff';
}
</script>

<?php include 'includes/footer.php'; ?>
