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
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit();
}

// Get booking details and verify it belongs to the user
$stmt = $conn->prepare("
    SELECT 
        b.*,
        s.name as servisor_name,
        sc.name as service_category,
        bs.name as status_name
    FROM bookings b
    JOIN servisors s ON b.servisor_id = s.id
    JOIN service_categories sc ON s.service_category_id = sc.id
    JOIN booking_statuses bs ON b.status_id = bs.id
    WHERE b.id = ? AND b.user_id = ?
");

$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my_bookings.php');
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

// Check if booking is completed
if ($booking['status_name'] !== 'Completed') {
    $_SESSION['flash_message'] = 'You can only review completed bookings.';
    $_SESSION['flash_type'] = 'error';
    header('Location: my_bookings.php');
    exit();
}

// Check if review already exists
$stmt = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ? AND user_id = ?");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$existing_review = $stmt->get_result();

if ($existing_review->num_rows > 0) {
    $_SESSION['flash_message'] = 'You have already reviewed this booking.';
    $_SESSION['flash_type'] = 'info';
    header('Location: my_bookings.php');
    exit();
}
$stmt->close();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $review_text = sanitizeInput($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $message = '<div class="message error">Please select a rating between 1 and 5 stars.</div>';
    } else {
        // Insert review
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, servisor_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiis', $booking_id, $user_id, $booking['servisor_id'], $rating, $review_text);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update servisor rating
            updateServisorRating($conn, $booking['servisor_id']);
            
            $_SESSION['flash_message'] = 'Thank you for your review! It has been submitted successfully.';
            $_SESSION['flash_type'] = 'success';
            header('Location: my_bookings.php');
            exit();
        } else {
            $message = '<div class="message error">Failed to submit review. Please try again.</div>';
            $stmt->close();
        }
    }
}
?>

<main class="container">
    <div class="review-container">
        <div class="review-header">
            <h1><i class="fa fa-star"></i> Leave a Review</h1>
            <p>Share your experience with this service</p>
        </div>
        
        <!-- Booking Summary -->
        <div class="booking-summary">
            <h3>Booking Details</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <label>Service Provider:</label>
                    <span><?php echo htmlspecialchars($booking['servisor_name']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Service:</label>
                    <span><?php echo htmlspecialchars($booking['service_category']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Date:</label>
                    <span><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <label>Booking Number:</label>
                    <span><?php echo htmlspecialchars($booking['booking_number']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Review Form -->
        <div class="review-form-section">
            <?php if (!empty($message)) echo $message; ?>
            
            <form method="POST" class="review-form">
                <div class="form-group">
                    <label>Rate Your Experience <span class="required">*</span></label>
                    <div class="star-rating">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5" class="star"><i class="fa fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="4" id="star4" required>
                        <label for="star4" class="star"><i class="fa fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="3" id="star3" required>
                        <label for="star3" class="star"><i class="fa fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="2" id="star2" required>
                        <label for="star2" class="star"><i class="fa fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="1" id="star1" required>
                        <label for="star1" class="star"><i class="fa fa-star"></i></label>
                    </div>
                    <div class="rating-text">
                        <span id="rating-description">Click to rate</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Write Your Review</label>
                    <textarea id="review_text" name="review_text" rows="6" 
                              placeholder="Share your experience with this service provider. What did you like? How was the quality of work? Would you recommend them to others?"><?php echo isset($_POST['review_text']) ? htmlspecialchars($_POST['review_text']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-paper-plane"></i> Submit Review
                    </button>
                    <a href="my_bookings.php" class="btn btn-secondary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.review-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.review-header {
    text-align: center;
    margin-bottom: 3rem;
}

.review-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.review-header p {
    font-size: 1.2rem;
    color: #666;
}

.booking-summary {
    background: #fff;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    margin-bottom: 2rem;
}

.booking-summary h3 {
    color: #333;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.summary-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.summary-item span {
    color: #333;
    font-size: 1rem;
}

.review-form-section {
    background: #fff;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.required {
    color: #dc3545;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.2rem;
    margin-bottom: 0.5rem;
}

.star-rating input[type="radio"] {
    display: none;
}

.star-rating .star {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.star-rating .star:hover,
.star-rating .star:hover ~ .star,
.star-rating input[type="radio"]:checked ~ .star {
    color: #ffc107;
}

.rating-text {
    font-size: 1rem;
    color: #666;
    margin-top: 0.5rem;
}

.form-group textarea {
    padding: 1rem;
    border: 2px solid #e3f0ff;
    border-radius: 0.8rem;
    font-size: 1rem;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.form-group textarea:focus {
    border-color: #007BFF;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .review-container {
        margin: 1rem;
        padding: 0;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .form-actions .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating input[type="radio"]');
    const ratingDescription = document.getElementById('rating-description');
    
    const descriptions = {
        1: 'Poor - Very unsatisfied',
        2: 'Fair - Below expectations',
        3: 'Good - Met expectations',
        4: 'Very Good - Exceeded expectations',
        5: 'Excellent - Outstanding service'
    };
    
    stars.forEach(star => {
        star.addEventListener('change', function() {
            const rating = this.value;
            ratingDescription.textContent = descriptions[rating];
        });
    });
    
    // Hover effects
    const starLabels = document.querySelectorAll('.star-rating .star');
    starLabels.forEach((star, index) => {
        star.addEventListener('mouseenter', function() {
            const rating = 5 - index;
            ratingDescription.textContent = descriptions[rating];
        });
    });
    
    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        const checkedStar = document.querySelector('.star-rating input[type="radio"]:checked');
        if (checkedStar) {
            ratingDescription.textContent = descriptions[checkedStar.value];
        } else {
            ratingDescription.textContent = 'Click to rate';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>