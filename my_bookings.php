<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings
$query = "SELECT b.*, s.name as servisor_name, s.phone as servisor_phone, s.service_type 
          FROM bookings b 
          JOIN servisors s ON b.servisor_id = s.id 
          WHERE b.user_id = ? OR b.customer_phone = (SELECT phone FROM users WHERE id = ?)
          ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>

<main class="container">
    <div style="max-width: 1000px; margin: 2rem auto;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1><i class="fa fa-calendar-alt"></i> My Bookings</h1>
            <p>Track and manage your service bookings</p>
        </div>
        
        <?php if ($bookings && $bookings->num_rows > 0): ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="card" style="text-align: left; max-width: none;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; color: #007BFF;">
                                <?php echo htmlspecialchars($booking['servisor_name']); ?>
                            </h3>
                            <span style="background: #007BFF; color: white; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($booking['service_type']); ?>
                            </span>
                        </div>
                        <div style="text-align: right;">
                            <?php
                            $status_colors = [
                                'pending' => '#FFA500',
                                'confirmed' => '#007BFF',
                                'completed' => '#28A745',
                                'cancelled' => '#DC3545'
                            ];
                            $status_color = $status_colors[$booking['status']] ?? '#6c757d';
                            ?>
                            <span style="background: <?php echo $status_color; ?>; color: white; padding: 0.4rem 1rem; border-radius: 1rem; font-size: 0.9rem; font-weight: 600;">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>Date & Time:</strong><br>
                            <i class="fa fa-calendar"></i> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?><br>
                            <i class="fa fa-clock"></i> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                        </div>
                        <div>
                            <strong>Contact:</strong><br>
                            <i class="fa fa-phone"></i> <?php echo htmlspecialchars($booking['servisor_phone']); ?><br>
                            <i class="fa fa-user"></i> <?php echo htmlspecialchars($booking['customer_name']); ?>
                        </div>
                        <div>
                            <strong>Booking Info:</strong><br>
                            <i class="fa fa-calendar-plus"></i> <?php echo date('M j, Y', strtotime($booking['created_at'])); ?><br>
                            <i class="fa fa-hashtag"></i> #<?php echo $booking['id']; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking['customer_address'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>Service Address:</strong><br>
                        <i class="fa fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['customer_address']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking['message'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong>Service Description:</strong><br>
                        <p style="margin: 0.5rem 0; padding: 1rem; background: #f8f9fa; border-radius: 0.5rem; border-left: 4px solid #007BFF;">
                            <?php echo nl2br(htmlspecialchars($booking['message'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-end;">
                        <?php if ($booking['status'] === 'pending'): ?>
                            <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="btn" style="background: #dc3545;">
                                <i class="fa fa-times"></i> Cancel Booking
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] === 'completed'): ?>
                            <a href="review.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-secondary">
                                <i class="fa fa-star"></i> Leave Review
                            </a>
                        <?php endif; ?>
                        
                        <a href="tel:<?php echo htmlspecialchars($booking['servisor_phone']); ?>" class="btn btn-secondary">
                            <i class="fa fa-phone"></i> Call Servisor
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <i class="fa fa-calendar-times" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any bookings yet. Start by browsing our services.</p>
                <a href="services.php" class="btn">
                    <i class="fa fa-search"></i> Browse Services
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // In a real application, you would send an AJAX request to cancel the booking
        fetch('cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({booking_id: bookingId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to cancel booking. Please try again.');
            }
        })
        .catch(error => {
            alert('Error cancelling booking. Please try again.');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>