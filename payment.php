<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Check if booking data exists in session
if (!isset($_SESSION['booking_data'])) {
    header('Location: services.php');
    exit();
}

$bookingData = $_SESSION['booking_data'];

// Default payment methods
$paymentMethods = [
    ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'COD', 'description' => 'Pay cash when service is completed'],
    ['id' => 2, 'name' => 'Credit/Debit Card', 'code' => 'CARD', 'description' => 'Pay online using credit or debit card']
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethodId = intval($_POST['payment_method']);
    
    // Find payment method
    $paymentMethod = null;
    foreach ($paymentMethods as $method) {
        if ($method['id'] == $paymentMethodId) {
            $paymentMethod = $method;
            break;
        }
    }
    
    if (!$paymentMethod) {
        $message = '<div class="card" style="color:red;">Invalid payment method selected.</div>';
    } else {
        // Handle different payment methods
        if ($paymentMethod['code'] === 'COD') {
            // Cash on Delivery - booking is complete
            $_SESSION['booking_success'] = [
                'booking_number' => 'BK' . $bookingData['booking_id'],
                'payment_method' => 'Cash on Delivery',
                'message' => 'Your booking has been confirmed! Pay cash when the service is completed.'
            ];
            unset($_SESSION['booking_data']);
            header('Location: booking_success.php');
            exit();
        } elseif ($paymentMethod['code'] === 'CARD') {
            // Redirect to card payment
            $_SESSION['payment_data'] = [
                'booking_id' => $bookingData['booking_id'],
                'booking_number' => 'BK' . $bookingData['booking_id'],
                'amount' => $bookingData['estimated_cost']
            ];
            header('Location: card_payment.php');
            exit();
        } else {
            // Other payment methods
            $_SESSION['booking_success'] = [
                'booking_number' => 'BK' . $bookingData['booking_id'],
                'payment_method' => $paymentMethod['name'],
                'message' => 'Your booking has been confirmed! Payment instructions will be sent to you.'
            ];
            unset($_SESSION['booking_data']);
            header('Location: booking_success.php');
            exit();
        }
    }
}
?>

<main class="container">
    <h2 style="text-align:center;">Select Payment Method</h2>
    
    <?php if ($message) echo $message; ?>
    
    <div class="card" style="max-width:600px;margin:2rem auto;">
        <h3>Booking Summary</h3>
        <p><strong>Service:</strong> <?php echo htmlspecialchars($bookingData['service_name']); ?></p>
        <p><strong>Servisor:</strong> <?php echo htmlspecialchars($bookingData['servisor_name']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($bookingData['booking_date']); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($bookingData['booking_time']); ?></p>
        <p><strong>Estimated Cost:</strong> LKR <?php echo number_format($bookingData['estimated_cost'], 2); ?></p>
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
