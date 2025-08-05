<?php 
session_start();
$page_title = 'Home';
include 'includes/db.php'; 
include 'includes/header.php'; 
?>

<main class="container">
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Jaffna Services</h1>
            <p>Find trusted and skilled service providers in Jaffna for all your home and business needs. Book with confidence and get quality service delivered to your doorstep.</p>
            
            <div class="hero-stats">
                <?php
                $stats = getDashboardStats($conn);
                ?>
                <div class="stat-item">
                    <i class="fa fa-users"></i>
                    <span class="stat-number"><?php echo $stats['total_servisors']; ?>+</span>
                    <span class="stat-label">Trusted Servisors</span>
                </div>
                <div class="stat-item">
                    <i class="fa fa-calendar-check"></i>
                    <span class="stat-number"><?php echo $stats['total_bookings']; ?>+</span>
                    <span class="stat-label">Completed Bookings</span>
                </div>
                <div class="stat-item">
                    <i class="fa fa-star"></i>
                    <span class="stat-number">4.8</span>
                    <span class="stat-label">Average Rating</span>
                </div>
            </div>
            
            <div class="hero-actions">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="services.php" class="btn btn-primary">
                        <i class="fa fa-search"></i> Browse Services
                    </a>
                    <a href="signup.php" class="btn btn-secondary">
                        <i class="fa fa-user-plus"></i> Join Now
                    </a>
                <?php else: ?>
                    <a href="services.php" class="btn btn-primary">
                        <i class="fa fa-search"></i> Browse Services
                    </a>
                    <a href="my_bookings.php" class="btn btn-secondary">
                        <i class="fa fa-calendar-alt"></i> My Bookings
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="services-overview">
        <div class="section-header">
            <h2>Our Services</h2>
            <p>Professional services for every need</p>
        </div>
        
        <div class="services-grid">
            <?php
            $categories = getServiceCategories($conn);
            foreach ($categories as $category) {
                // Get servisor count for this category
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM servisors WHERE service_category_id = ? AND is_approved = 1 AND is_active = 1");
                $stmt->bind_param('i', $category['id']);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                $stmt->close();
                
                echo '<div class="service-card">';
                echo '<div class="service-icon">';
                echo '<i class="fa ' . htmlspecialchars($category['icon']) . '"></i>';
                echo '</div>';
                echo '<h3>' . htmlspecialchars($category['name']) . '</h3>';
                echo '<p>' . htmlspecialchars($category['description']) . '</p>';
                echo '<div class="service-stats">';
                echo '<span class="servisor-count">' . $count . ' Servisors Available</span>';
                echo '</div>';
                echo '<a href="services.php?category=' . $category['id'] . '" class="btn btn-outline">View Servisors</a>';
                echo '</div>';
            }
            ?>
        </div>
    </section>

    <section class="featured-servisors">
        <div class="section-header">
            <h2>Top Rated Servisors</h2>
            <p>Highly recommended professionals in your area</p>
        </div>
        
        <div class="servisors-grid">
            <?php
            $stmt = $conn->prepare("SELECT * FROM servisor_details WHERE is_approved = 1 AND is_active = 1 AND rating > 0 ORDER BY rating DESC, total_reviews DESC LIMIT 6");
            $stmt->execute();
            $featured_servisors = $stmt->get_result();
            
            if ($featured_servisors->num_rows > 0) {
                while ($servisor = $featured_servisors->fetch_assoc()) {
                    echo '<div class="servisor-card">';
                    
                    // Profile image
                    if (!empty($servisor['profile_image'])) {
                        echo '<div class="servisor-avatar">';
                        echo '<img src="' . htmlspecialchars($servisor['profile_image']) . '" alt="' . htmlspecialchars($servisor['name']) . '">';
                        echo '</div>';
                    } else {
                        echo '<div class="servisor-avatar servisor-avatar-placeholder">';
                        echo '<i class="fa fa-user"></i>';
                        echo '</div>';
                    }
                    
                    echo '<div class="servisor-info">';
                    echo '<h4>' . htmlspecialchars($servisor['name']) . '</h4>';
                    echo '<span class="service-badge">';
                    echo '<i class="fa ' . htmlspecialchars($servisor['service_icon']) . '"></i> ';
                    echo htmlspecialchars($servisor['service_category']);
                    echo '</span>';
                    echo '<p class="servisor-area"><i class="fa fa-map-marker-alt"></i> ' . htmlspecialchars($servisor['area']) . '</p>';
                    
                    // Rating
                    if ($servisor['rating'] > 0) {
                        echo '<div class="rating">';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $servisor['rating']) {
                                echo '<i class="fa fa-star"></i>';
                            } else {
                                echo '<i class="fa fa-star-o"></i>';
                            }
                        }
                        echo '<span class="rating-text">(' . $servisor['total_reviews'] . ' reviews)</span>';
                        echo '</div>';
                    }
                    
                    echo '<div class="servisor-fee">Starting from ' . formatCurrency($servisor['base_fee']) . '</div>';
                    echo '</div>';
                    
                    echo '<div class="servisor-actions">';
                    echo '<a href="servisor_profile.php?id=' . $servisor['id'] . '" class="btn btn-outline btn-sm">View Profile</a>';
                    echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn btn-primary btn-sm">Book Now</a>';
                    echo '</div>';
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="no-results">';
                echo '<i class="fa fa-users fa-3x"></i>';
                echo '<h3>No Featured Servisors Yet</h3>';
                echo '<p>Check back soon for top-rated service providers.</p>';
                echo '</div>';
            }
            $stmt->close();
            ?>
        </div>
        
        <div class="section-footer">
            <a href="services.php" class="btn btn-primary">
                <i class="fa fa-list"></i> View All Services
            </a>
        </div>
    </section>

    <section class="how-it-works">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Simple steps to get your service booked</p>
        </div>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon"><i class="fa fa-search"></i></div>
                <h3>Browse Services</h3>
                <p>Find the right service provider for your needs from our verified professionals.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon"><i class="fa fa-calendar-plus"></i></div>
                <h3>Book Appointment</h3>
                <p>Select your preferred date and time, provide service details, and confirm your booking.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon"><i class="fa fa-credit-card"></i></div>
                <h3>Secure Payment</h3>
                <p>Choose from multiple payment options including cash on delivery or online payment.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">4</div>
                <div class="step-icon"><i class="fa fa-tools"></i></div>
                <h3>Get Service</h3>
                <p>Our professional arrives at your location and completes the service to your satisfaction.</p>
            </div>
        </div>
    </section>
</main>

<style>
.hero {
    background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
    color: #fff;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
}

.hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero p {
    font-size: 1.3rem;
    margin-bottom: 3rem;
    opacity: 0.95;
    line-height: 1.6;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin: 3rem 0;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-item i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
}

.hero-actions {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.services-overview, .featured-servisors, .how-it-works {
    padding: 4rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
}

.section-header p {
    font-size: 1.2rem;
    color: #666;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.service-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2.5rem 2rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,123,255,0.05);
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,123,255,0.15);
}

.service-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #007BFF, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem auto;
}

.service-icon i {
    font-size: 2rem;
    color: #fff;
}

.service-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.service-card p {
    color: #666;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.service-stats {
    margin-bottom: 1.5rem;
}

.servisor-count {
    background: #e3f0ff;
    color: #007BFF;
    padding: 0.4rem 1rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.servisors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.servisor-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.servisor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,123,255,0.15);
}

.servisor-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin-bottom: 1rem;
    overflow: hidden;
}

.servisor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.servisor-avatar-placeholder {
    background: #e3f0ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007BFF;
    font-size: 2rem;
}

.servisor-info h4 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.service-badge {
    background: #007BFF;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    margin-bottom: 0.8rem;
    display: inline-block;
}

.servisor-area {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.rating {
    margin-bottom: 1rem;
}

.rating i {
    color: #ffc107;
    margin-right: 0.2rem;
}

.rating-text {
    color: #666;
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

.servisor-fee {
    font-weight: 600;
    color: #007BFF;
    margin-bottom: 1.5rem;
}

.servisor-actions {
    display: flex;
    gap: 1rem;
    width: 100%;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2.5rem 2rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    position: relative;
}

.step-number {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: #007BFF;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
}

.step-icon {
    font-size: 3rem;
    color: #007BFF;
    margin: 1rem 0;
}

.step-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.step-card p {
    color: #666;
    line-height: 1.6;
}

.section-footer {
    text-align: center;
    margin-top: 3rem;
}

.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-results i {
    color: #ccc;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .hero p {
        font-size: 1.1rem;
    }
    
    .hero-stats {
        gap: 2rem;
    }
    
    .hero-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .servisor-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>