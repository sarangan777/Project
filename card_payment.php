<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Check if payment data exists
if (!isset($_SESSION['payment_data'])) {
    redirectWithMessage('services.php', 'Invalid payment session.', 'error');
}

$paymentData = $_SESSION['payment_data'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = sanitizeInput($_POST['card_number']);
    $expiryDate = sanitizeInput($_POST['expiry_date']);
    $cvv = sanitizeInput($_POST['cvv']);
    $cardholderName = sanitizeInput($_POST['cardholder_name']);
    
    // Basic validation
    if (empty($cardNumber) || empty($expiryDate) || empty($cvv) || empty($cardholderName)) {
        $message = '<div class="card" style="color:red;">All fields are required.</div>';
    } else {
        // In a real application, you would integrate with a payment gateway here
        // For demo purposes, we'll simulate a successful payment
        
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        // Insert payment transaction
        $stmt = $conn->prepare("INSERT INTO payment_transactions (booking_id, transaction_id, amount, payment_method_id, payment_status, payment_date) VALUES (?, ?, ?, 2, 'completed', NOW())");
        $stmt->bind_param('isd', $paymentData['booking_id'], $transactionId, $paymentData['amount']);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update booking status to confirmed
            $stmt = $conn->prepare("UPDATE bookings SET status_id = 2 WHERE id = ?");
            $stmt->bind_param('i', $paymentData['booking_id']);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['booking_success'] = [
                'booking_number' => $paymentData['booking_number'],
                'payment_method' => 'Credit/Debit Card',
                'transaction_id' => $transactionId,
                'message' => 'Payment successful! Your booking has been confirmed.'
            ];
            
            unset($_SESSION['payment_data']);
            header('Location: booking_success.php');
            exit();
        } else {
            $message = '<div class="card" style="color:red;">Payment processing failed. Please try again.</div>';
            $stmt->close();
        }
    }
}
?>

<main class="container">
    <h2 style="text-align:center;">Card Payment</h2>
    
    <?php if ($message) echo $message; ?>
    
    <div class="card" style="max-width:400px;margin:2rem auto;text-align:center;">
        <h3>Payment Details</h3>
        <p><strong>Booking Number:</strong> <?php echo htmlspecialchars($paymentData['booking_number']); ?></p>
        <p><strong>Amount:</strong> <?php echo formatCurrency($paymentData['amount']); ?></p>
    </div>
    
    <form method="POST" class="form" style="max-width:400px;">
        <label for="cardholder_name">Cardholder Name:</label>
        <input type="text" id="cardholder_name" name="cardholder_name" required placeholder="John Doe">
        
        <label for="card_number">Card Number:</label>
        <input type="text" id="card_number" name="card_number" maxlength="19" required placeholder="1234 5678 9012 3456" oninput="formatCardNumber(this)">
        
        <div style="display:flex;gap:1rem;">
            <div style="flex:1;">
                <label for="expiry_date">Expiry Date:</label>
                <input type="text" id="expiry_date" name="expiry_date" maxlength="5" required placeholder="MM/YY" oninput="formatExpiry(this)">
            </div>
            <div style="flex:1;">
                <label for="cvv">CVV:</label>
                <input type="password" id="cvv" name="cvv" maxlength="4" required placeholder="123">
            </div>
        </div>
        
        <div style="background:#f8f9fa;padding:1rem;border-radius:0.5rem;margin:1rem 0;font-size:0.9rem;color:#666;">
            <i class="fa fa-lock" style="color:#28a745;"></i> Your payment information is secure and encrypted.
        </div>
        
        <button type="submit" class="btn" style="width:100%;">
            <i class="fa fa-credit-card"></i> Pay <?php echo formatCurrency($paymentData['amount']); ?>
        </button>
        
        <a href="payment.php" class="btn btn-secondary" style="width:100%;text-align:center;margin-top:0.5rem;">
            <i class="fa fa-arrow-left"></i> Back to Payment Methods
        </a>
    </form>
</main>

<script>
function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formattedValue;
}

function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    input.value = value;
}
</script>

<?php include 'includes/footer.php'; ?>