<?php
session_start();
include 'includes/db.php';

// Check if servisor is logged in
if (!isset($_SESSION['servisor_id'])) {
    header('Location: servisor_login.php');
    exit();
}

$servisor_id = $_SESSION['servisor_id'];

// Get servisor details
$stmt = $conn->prepare("SELECT * FROM servisor_details WHERE id = ?");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$servisor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get dashboard statistics
$stats = [];

// Total bookings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE servisor_id = ?");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$stats['total_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Pending bookings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE servisor_id = ? AND status_id = 1");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$stats['pending_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// This month's bookings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE servisor_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$stats['monthly_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Monthly earnings
$stmt = $conn->prepare("SELECT COALESCE(SUM(final_cost), 0) as earnings FROM bookings WHERE servisor_id = ? AND status_id = 4 AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->bind_param('i', $servisor_id);
$stmt->execute();
$stats['monthly_earnings'] = $stmt->get_result()->fetch_assoc()['earnings'];
$stmt->close();

$page_title = 'Servisor Dashboard';
include 'includes/header.php';
?>

<main class="container">
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="servisor-info">
                <?php if (!empty($servisor['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="servisor-details">
                    <h1>Welcome, <?php echo htmlspecialchars($servisor['name']); ?>!</h1>
                    <p><?php echo htmlspecialchars($servisor['service_category']); ?> • <?php echo htmlspecialchars($servisor['area']); ?></p>
                    <?php if ($servisor['rating'] > 0): ?>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa fa-star<?php echo $i <= $servisor['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                            <span><?php echo number_format($servisor['rating'], 1); ?> (<?php echo $servisor['total_reviews']; ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="quick-actions">
                <a href="servisor_profile.php?id=<?php echo $servisor['id']; ?>" class="btn btn-outline">
                    <i class="fa fa-eye"></i> View Public Profile
                </a>
                <a href="servisor_profile_edit.php" class="btn btn-primary">
                    <i class="fa fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fa fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fa fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon monthly">
                    <i class="fa fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['monthly_bookings']; ?></h3>
                    <p>This Month</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon earnings">
                    <i class="fa fa-money-bill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo formatCurrency($stats['monthly_earnings']); ?></h3>
                    <p>Monthly Earnings</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fa fa-list"></i> Recent Bookings</h2>
                <a href="servisor_bookings.php" class="btn btn-outline">View All</a>
            </div>
            
            <div class="bookings-list">
                <?php
                $stmt = $conn->prepare("
                    SELECT 
                        b.*,
                        bs.name as status_name,
                        bs.color as status_color
                    FROM bookings b
                    JOIN booking_statuses bs ON b.status_id = bs.id
                    WHERE b.servisor_id = ?
                    ORDER BY b.created_at DESC
                    LIMIT 5
                ");
                $stmt->bind_param('i', $servisor_id);
                $stmt->execute();
                $recent_bookings = $stmt->get_result();
                
                if ($recent_bookings->num_rows > 0) {
                    while ($booking = $recent_bookings->fetch_assoc()) {
                        echo '<div class="booking-item">';
                        echo '<div class="booking-info">';
                        echo '<h4>' . htmlspecialchars($booking['customer_name']) . '</h4>';
                        echo '<p><i class="fa fa-calendar"></i> ' . date('M j, Y', strtotime($booking['booking_date'])) . ' at ' . date('g:i A', strtotime($booking['booking_time'])) . '</p>';
                        echo '<p><i class="fa fa-map-marker-alt"></i> ' . htmlspecialchars($booking['customer_address']) . '</p>';
                        echo '</div>';
                        echo '<div class="booking-status">';
                        echo '<span class="status-badge" style="background: ' . $booking['status_color'] . '">' . htmlspecialchars($booking['status_name']) . '</span>';
                        echo '<div class="booking-actions">';
                        echo '<a href="booking_details.php?id=' . $booking['id'] . '" class="btn btn-sm btn-outline">View Details</a>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-bookings">';
                    echo '<i class="fa fa-calendar-times fa-3x"></i>';
                    echo '<h3>No Bookings Yet</h3>';
                    echo '<p>Your bookings will appear here once customers start booking your services.</p>';
                    echo '</div>';
                }
                $stmt->close();
                ?>
            </div>
        </div>
    </div>
</main>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.dashboard-header {
    background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
    color: #fff;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.servisor-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
}

.profile-avatar-placeholder {
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: rgba(255,255,255,0.8);
}

.servisor-details h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.servisor-details p {
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rating i {
    color: #ffc107;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: #fff;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
}

.stat-icon.total { background: #007BFF; }
.stat-icon.pending { background: #ffc107; }
.stat-icon.monthly { background: #28a745; }
.stat-icon.earnings { background: #6f42c1; }

.stat-info h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.stat-info p {
    color: #666;
    font-weight: 500;
}

.dashboard-section {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.section-header {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bookings-list {
    padding: 2rem;
}

.booking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0.8rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.booking-info h4 {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.booking-info p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.booking-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
}

.status-badge {
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
}

.no-bookings {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-bookings i {
    color: #ccc;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        text-align: center;
    }
    
    .servisor-info {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .booking-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .booking-status {
        align-items: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>