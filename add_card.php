<?php include 'includes/header.php'; ?>
<main class="container text-center">
    <h2>Add Card Details</h2>
    <?php
    $success = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Here you would normally save the card details securely
        $success = true;
        echo '<div class="card" style="color:green;max-width:400px;margin:2rem auto;text-align:center;">';
        echo '<div style="font-size:2.5rem;"><i class="fa fa-check-circle"></i></div>';
        echo '<h2>Payment Successful!</h2>';
        echo '<p>Your card details have been added and your booking is complete.</p>';
        echo '<a href="index.php" class="btn mt-2">Back to Home</a>';
        echo '</div>';
    }
    if (!$success) {
    ?>
    <form method="POST" class="form" style="max-width:400px;margin:2rem auto;">
        <label for="card_number">Card Number:</label>
        <input type="text" id="card_number" name="card_number" maxlength="19" required placeholder="1234 5678 9012 3456">
        <label for="expiry">Expiry Date:</label>
        <input type="text" id="expiry" name="expiry" maxlength="5" required placeholder="MM/YY">
        <label for="cvv">CVV:</label>
        <input type="password" id="cvv" name="cvv" maxlength="4" required placeholder="123">
        <button type="submit" class="btn">Submit</button>
    </form>
    <?php } ?>
</main>
<?php include 'includes/footer.php'; ?> 