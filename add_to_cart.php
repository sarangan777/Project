<?php 
session_start();
include 'includes/header.php'; 
include 'includes/db.php'; 
?>
<main class="container text-center">
<?php
if (isset($_GET['servisor_id'])) {
    $id = intval($_GET['servisor_id']);
    // Add to session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!in_array($id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $id;
        $cart_message = '<div class="card" style="background:#e3f0ff;color:#007BFF;margin:1rem 0;">Service added to cart! Redirecting to card details...</div>';
        echo '<script>setTimeout(function(){ window.location.href = "add_card.php?servisor_id=' . $id . '"; }, 1500);</script>';
    } else {
        $cart_message = '<div class="card" style="background:#fff3cd;color:#856404;margin:1rem 0;">This service is already in your cart. Redirecting to card details...</div>';
        echo '<script>setTimeout(function(){ window.location.href = "add_card.php?servisor_id=' . $id . '"; }, 1500);</script>';
    }
    $stmt = $conn->prepare('SELECT * FROM servisors WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $servisor = $result->fetch_assoc();
        echo '<div class="card" style="max-width:400px;margin:2rem auto;">';
        if (!empty($servisor['photo'])) {
            echo '<img src="' . htmlspecialchars($servisor['photo']) . '" alt="' . htmlspecialchars($servisor['name']) . '" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">';
        } else {
            echo '<i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>';
        }
        echo '<h2>' . htmlspecialchars($servisor['name']) . '</h2>';
        echo '<p><strong>Service:</strong> ' . htmlspecialchars($servisor['service_type']) . '</p>';
        echo $cart_message;
        echo '<a href="add_card.php?servisor_id=' . $servisor['id'] . '" class="btn mt-2">Go to Card Details</a> ';
        echo '<a href="services.php" class="btn btn-secondary mt-2">Back to Services</a>';
        echo '</div>';
    } else {
        echo '<div class="card">Servisor not found.</div>';
    }
    $stmt->close();
} else {
    echo '<div class="card">No servisor selected.</div>';
}
?>
</main>
<?php include 'includes/footer.php'; ?> 