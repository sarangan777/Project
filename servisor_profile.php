<?php 
session_start();
include 'includes/db.php'; 

// Get servisor ID
$servisor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($servisor_id <= 0) {
    header('Location: services.php');
    exit();
}

// Get servisor details
$servisor = getServisorDetails($conn, $servisor_id);
if (!$servisor || !$servisor['is_approved'] || !$servisor['is_active']) {
    header('Location: services.php');
    exit();
}

$page_title = $servisor['name'] . ' - ' . $servisor['service_category'];
include 'includes/header.php'; 

// Get reviews for this servisor
$stmt = $conn->prepare("
    SELECT r.*, u.name as customer_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.servisor_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

// Get recent bookings count (for popularity indicator)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE servisor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>

<main class="container">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-section">
                <?php if (!empty($servisor['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="<?php echo htmlspecialchars($servisor['name']); ?>" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <?php if ($recent_bookings > 5): ?>
                    <div class="popularity-badge">
                        <i class="fa fa-fire"></i> Popular
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($servisor['name']); ?></h1>
                
                <div class="profile-badges">
                    <span class="service-badge">
                        <i class="fa <?php echo htmlspecialchars($servisor['service_icon']); ?>"></i>
                        <?php echo htmlspecialchars($servisor['service_category']); ?>
                    </span>
                    <span class="area-badge">
                        <i class="fa fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($servisor['area']); ?>
                    </span>
                </div>
                
                <?php if ($servisor['rating'] > 0): ?>
                    <div class="profile-rating">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa fa-star<?php echo $i <= $servisor['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo number_format($servisor['rating'], 1); ?> out of 5 
                            (<?php echo $servisor['total_reviews']; ?> reviews)
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="fa fa-clock"></i>
                        <span><?php echo $servisor['experience_years']; ?> Years Experience</span>
                    </div>
                    <div class="stat-item">
                        <i class="fa fa-calendar-check"></i>
                        <span><?php echo $recent_bookings; ?> Bookings This Month</span>
                    </div>
                    <div class="stat-item">
                        <i class="fa fa-money-bill"></i>
                        <span>Starting from <?php echo formatCurrency($servisor['base_fee']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="profile-content">
            <div class="content-grid">
                <!-- About Section -->
                <div class="content-section">
                    <h3><i class="fa fa-user"></i> About</h3>
                    <?php if (!empty($servisor['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($servisor['description'])); ?></p>
                    <?php else: ?>
                        <p>Professional <?php echo htmlspecialchars($servisor['service_category']); ?> service provider in <?php echo htmlspecialchars($servisor['area']); ?>.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Contact Section -->
                <div class="content-section">
                    <h3><i class="fa fa-phone"></i> Contact Information</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fa fa-phone"></i>
                            <span><?php echo htmlspecialchars($servisor['phone']); ?></span>
                            <a href="tel:<?php echo htmlspecialchars($servisor['phone']); ?>" class="btn btn-sm btn-outline">Call</a>
                        </div>
                        <div class="contact-item">
                            <i class="fa fa-envelope"></i>
                            <span><?php echo htmlspecialchars($servisor['email']); ?></span>
                            <a href="mailto:<?php echo htmlspecialchars($servisor['email']); ?>" class="btn btn-sm btn-outline">Email</a>
                        </div>
                    </div>
                </div>
                
                <!-- Service Details -->
                <div class="content-section">
                    <h3><i class="fa fa-tools"></i> Service Details</h3>
                    <div class="service-details">
                        <div class="detail-row">
                            <span class="detail-label">Service Category:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($servisor['service_category']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Service Area:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($servisor['area']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Base Fee:</span>
                            <span class="detail-value"><?php echo formatCurrency($servisor['base_fee']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Experience:</span>
                            <span class="detail-value"><?php echo $servisor['experience_years']; ?> years</span>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="content-section reviews-section">
                    <h3><i class="fa fa-star"></i> Customer Reviews</h3>
                    
                    <?php if ($reviews->num_rows > 0): ?>
                        <div class="reviews-list">
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <i class="fa fa-user"></i>
                                            </div>
                                            <div class="reviewer-details">
                                                <h5><?php echo htmlspecialchars($review['customer_name'] ?? 'Anonymous'); ?></h5>
                                                <div class="review-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fa fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-date">
                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($review['review_text'])): ?>
                                        <div class="review-text">
                                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="fa fa-star-o fa-2x"></i>
                            <p>No reviews yet. Be the first to review this service provider!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="profile-actions">
                <a href="booking.php?servisor_id=<?php echo $servisor['id']; ?>" class="btn btn-primary btn-lg">
                    <i class="fa fa-calendar-plus"></i> Book This Service
                </a>
                <a href="tel:<?php echo htmlspecialchars($servisor['phone']); ?>" class="btn btn-secondary btn-lg">
                    <i class="fa fa-phone"></i> Call Now
                </a>
                <a href="services.php" class="btn btn-outline btn-lg">
                    <i class="fa fa-arrow-left"></i> Back to Services
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.profile-container {
    max-width: 1000px;
    margin: 2rem auto;
}

.profile-header {
    background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
    color: #fff;
    padding: 3rem 2rem;
    border-radius: 1.5rem 1.5rem 0 0;
    display: flex;
    align-items: center;
    gap: 2rem;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
    opacity: 0.3;
}

.profile-avatar-section {
    position: relative;
    z-index: 1;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: rgba(255,255,255,0.8);
}

.popularity-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #ff6b35;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255,107,53,0.3);
}

.profile-info {
    flex: 1;
    position: relative;
    z-index: 1;
}

.profile-info h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.profile-badges {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.service-badge, .area-badge {
    padding: 0.5rem 1rem;
    border-radius: 1.5rem;
    font-weight: 500;
    font-size: 0.95rem;
}

.service-badge {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.area-badge {
    background: rgba(40,167,69,0.9);
    color: #fff;
}

.profile-rating {
    margin-bottom: 1.5rem;
}

.stars {
    margin-bottom: 0.5rem;
}

.stars i {
    color: #ffc107;
    font-size: 1.2rem;
    margin-right: 0.2rem;
}

.rating-text {
    font-size: 1rem;
    opacity: 0.9;
}

.profile-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    opacity: 0.9;
}

.profile-content {
    background: #fff;
    border-radius: 0 0 1.5rem 1.5rem;
    box-shadow: 0 8px 30px rgba(0,123,255,0.1);
}

.content-grid {
    padding: 2rem;
    display: grid;
    gap: 2rem;
}

.content-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 1rem;
}

.content-section h3 {
    color: #333;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.content-section h3 i {
    color: #007BFF;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
}

.contact-item i {
    color: #007BFF;
    width: 20px;
    text-align: center;
}

.contact-item span {
    flex: 1;
    font-weight: 500;
}

.service-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
}

.detail-label {
    font-weight: 500;
    color: #666;
}

.detail-value {
    font-weight: 600;
    color: #333;
}

.reviews-section {
    grid-column: 1 / -1;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    background: #fff;
    padding: 1.5rem;
    border-radius: 0.8rem;
    border: 1px solid #e9ecef;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.reviewer-avatar {
    width: 40px;
    height: 40px;
    background: #e3f0ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007BFF;
}

.reviewer-details h5 {
    margin: 0 0 0.3rem 0;
    font-weight: 600;
    color: #333;
}

.review-rating i {
    color: #ffc107;
    font-size: 0.9rem;
}

.review-date {
    color: #666;
    font-size: 0.9rem;
}

.review-text {
    color: #555;
    line-height: 1.6;
}

.no-reviews {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-reviews i {
    color: #ccc;
    margin-bottom: 1rem;
}

.profile-actions {
    padding: 2rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .profile-info h1 {
        font-size: 2rem;
    }
    
    .profile-stats {
        justify-content: center;
    }
    
    .content-grid {
        padding: 1rem;
    }
    
    .contact-item {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .profile-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .profile-actions .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>