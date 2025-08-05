<?php
session_start();
include 'includes/header.php';

// Check if success data exists
if (!isset($_SESSION['booking_success'])) {
    header('Location: index.php');
    exit();
}

$successData = $_SESSION['booking_success'];
unset($_SESSION['booking_success']);
?>

<main class="container text-center">
    <div class="card" style="max-width:500px;margin:3rem auto;text-align:center;">
        <div style="font-size:4rem;color:#28a745;margin-bottom:1rem;">
            <i class="fa fa-check-circle"></i>
        </div>
        
        <h2 style="color:#28a745;margin-bottom:1rem;">Booking Confirmed!</h2>
        
        <div style="background:#f8f9fa;padding:1.5rem;border-radius:0.7rem;margin:1.5rem 0;">
            <p><strong>Booking Number:</strong> <?php echo htmlspecialchars($successData['booking_number']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($successData['payment_method']); ?></p>
            <?php if (isset($successData['transaction_id'])): ?>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($successData['transaction_id']); ?></p>
            <?php endif; ?>
        </div>
        
        <p style="color:#666;margin-bottom:2rem;"><?php echo htmlspecialchars($successData['message']); ?></p>
        
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="index.php" class="btn">
                <i class="fa fa-home"></i> Back to Home
            </a>
            <a href="services.php" class="btn btn-secondary">
                <i class="fa fa-search"></i> Book Another Service
            </a>
        </div>
        
        <div style="margin-top:2rem;padding:1rem;background:#e3f0ff;border-radius:0.5rem;font-size:0.9rem;color:#007BFF;">
            <i class="fa fa-info-circle"></i> You will receive a confirmation call from our team within 24 hours.
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>