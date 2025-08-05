<?php
session_start();
include 'includes/db.php';

// Check if servisor_id is provided
$servisor_id = isset($_GET['servisor_id']) ? intval($_GET['servisor_id']) : 0;
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

$page_title = 'Book ' . $servisor['name'];
include 'includes/header.php';

// Fetch user data if logged in
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT name, email, phone, address FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitizeInput($_POST['name'] ?? '');
    $customer_phone = sanitizeInput($_POST['phone'] ?? '');
    $customer_email = sanitizeInput($_POST['email'] ?? '');
    $customer_address = sanitizeInput($_POST['address'] ?? '');
    $booking_date = sanitizeInput($_POST['date'] ?? '');
    $booking_time = sanitizeInput($_POST['time'] ?? '');
    $service_description = sanitizeInput($_POST['message'] ?? '');
    $payment_method_id = intval($_POST['payment_method'] ?? 1);
    
    // Validation
    if (empty($customer_name) || empty($customer_phone) || empty($customer_address) || 
        empty($booking_date) || empty($booking_time) || empty($service_description)) {
        $message = '<div class="message error">All required fields must be filled.</div>';
    } elseif (!isValidPhone($customer_phone)) {
        $message = '<div class="message error">Invalid phone number format. Use +94 or 0 followed by 9 digits.</div>';
    } elseif (!empty($customer_email) && !isValidEmail($customer_email)) {
        $message = '<div class="message error">Invalid email address.</div>';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $message = '<div class="message error">Booking date cannot be in the past.</div>';
    } else {
        // Generate booking number
        $booking_number = generateBookingNumber();
        
        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                booking_number, user_id, servisor_id, customer_name, customer_phone,
                customer_email, customer_address, booking_date, booking_time,
                service_description, estimated_cost, payment_method_id, status_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        if ($stmt) {
            $userId = $_SESSION['user_id'] ?? null;
            $estimated_cost = $servisor['base_fee'];
            
            $stmt->bind_param('siisssssssdii',
                $booking_number,
                $userId,
                $servisor_id,
                $customer_name,
                $customer_phone,
                $customer_email,
                $customer_address,
                $booking_date,
                $booking_time,
                $service_description,
                $estimated_cost,
                $payment_method_id
            );
            
            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id;
                $stmt->close();
                
                // Store booking data for payment/confirmation
                $_SESSION['booking_data'] = [
                    'booking_id' => $booking_id,
                    'booking_number' => $booking_number,
                    'servisor_id' => $servisor_id,
                    'servisor_name' => $servisor['name'],
                    'service_name' => $servisor['service_category'],
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                    'customer_email' => $customer_email,
                    'customer_address' => $customer_address,
                    'booking_date' => $booking_date,
                    'booking_time' => $booking_time,
                    'service_description' => $service_description,
                    'estimated_cost' => $estimated_cost,
                    'payment_method_id' => $payment_method_id
                ];
                
                // Redirect to payment or confirmation
                if ($payment_method_id == 1) { // COD
                    $_SESSION['booking_success'] = [
                        'booking_number' => $booking_number,
                        'payment_method' => 'Cash on Delivery',
                        'message' => 'Your booking has been confirmed! Pay cash when the service is completed.'
                    ];
                    unset($_SESSION['booking_data']);
                    header('Location: booking_success.php');
                    exit();
                } else {
                    header('Location: payment.php');
                    exit();
                }
            } else {
                $message = '<div class="message error">Failed to create booking. Please try again.</div>';
                $stmt->close();
            }
        } else {
            $message = '<div class="message error">Database error. Please try again later.</div>';
        }
    }
}

// Get payment methods - fallback if function doesn't exist
$payment_methods = [];
try {
    $payment_methods = getPaymentMethods($conn);
} catch (Exception $e) {
    // Fallback payment methods
    $result = $conn->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $payment_methods[] = $row;
        }
    } else {
        // Default payment methods if table doesn't exist
        $payment_methods = [
            ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'COD', 'description' => 'Pay cash when service is completed'],
            ['id' => 2, 'name' => 'Credit/Debit Card', 'code' => 'CARD', 'description' => 'Pay online using credit or debit card']
        ];
    }
}
?>

<main class="container">
    <div class="booking-container">
        <!-- Servisor Info -->
        <div class="servisor-summary">
            <div class="servisor-card">
                <?php if (!empty($servisor['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="<?php echo htmlspecialchars($servisor['name']); ?>" class="servisor-avatar">
                <?php else: ?>
                    <div class="servisor-avatar servisor-avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <div class="servisor-info">
                    <h3><?php echo htmlspecialchars($servisor['name']); ?></h3>
                    <div class="servisor-badges">
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
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa fa-star<?php echo $i <= $servisor['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                            <span class="rating-text"><?php echo number_format($servisor['rating'], 1); ?> (<?php echo $servisor['total_reviews']; ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="servisor-details">
                        <div class="detail-item">
                            <i class="fa fa-phone"></i>
                            <span><?php echo htmlspecialchars($servisor['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa fa-money-bill"></i>
                            <span>Base Fee: <?php echo formatCurrency($servisor['base_fee']); ?></span>
                        </div>
                        <?php if ($servisor['experience_years'] > 0): ?>
                            <div class="detail-item">
                                <i class="fa fa-clock"></i>
                                <span><?php echo $servisor['experience_years']; ?> years experience</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Form -->
        <div class="booking-form-section">
            <div class="form-header">
                <h2><i class="fa fa-calendar-plus"></i> Book This Service</h2>
                <p>Fill in the details below to book your service</p>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>
            <?php displayFlashMessage(); ?>
            
            <form method="POST" class="booking-form">
                <div class="form-section">
                    <h4><i class="fa fa-user"></i> Customer Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($user_data['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" required placeholder="0771234567 or +94771234567"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($user_data['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Service Address <span class="required">*</span></label>
                        <textarea id="address" name="address" required rows="3" 
                                  placeholder="Enter the complete address where service is needed"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4><i class="fa fa-calendar"></i> Booking Details</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Preferred Date <span class="required">*</span></label>
                            <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Preferred Time <span class="required">*</span></label>
                            <input type="time" id="time" name="time" required
                                   value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Service Description <span class="required">*</span></label>
                        <textarea id="message" name="message" required rows="4" 
                                  placeholder="Describe your specific requirements, any issues you're facing, or additional details about the service needed"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4><i class="fa fa-credit-card"></i> Payment Method</h4>
                    
                    <div class="payment-methods">
                        <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-option" onclick="selectPayment(<?php echo $method['id']; ?>)">
                                <label>
                                    <input type="radio" name="payment_method" value="<?php echo $method['id']; ?>" 
                                           <?php echo $method['id'] == 1 ? 'checked' : ''; ?> required>
                                    <div class="payment-info">
                                        <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                                        <?php if ($method['code'] === 'COD'): ?>
                                            <span class="recommended-badge">Recommended</span>
                                        <?php endif; ?>
                                        <p><?php echo htmlspecialchars($method['description']); ?></p>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-calendar-check"></i> Confirm Booking
                    </button>
                    <a href="servisor_profile.php?id=<?php echo $servisor['id']; ?>" class="btn btn-secondary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.booking-container {
    max-width: 1200px;
    margin: 2rem auto;
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.servisor-summary {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.servisor-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    text-align: center;
}

.servisor-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 1rem auto;
    object-fit: cover;
}

.servisor-avatar-placeholder {
    background: #e3f0ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007BFF;
    font-size: 2.5rem;
}

.servisor-info h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.servisor-badges {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.service-badge {
    background: #007BFF;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.area-badge {
    background: #28a745;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.rating {
    margin-bottom: 1.5rem;
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

.servisor-details {
    text-align: left;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 0.8rem;
    color: #666;
    font-size: 0.95rem;
}

.detail-item i {
    color: #007BFF;
    width: 16px;
    text-align: center;
}

.booking-form-section {
    background: #fff;
    border-radius: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
    color: #fff;
    padding: 2rem;
    text-align: center;
}

.form-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.form-header p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.booking-form {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h4 {
    color: #333;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section h4 i {
    color: #007BFF;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.required {
    color: #dc3545;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e3f0ff;
    border-radius: 0.8rem;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #007BFF;
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-option {
    border: 2px solid #e3f0ff;
    border-radius: 0.8rem;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.payment-option:hover {
    border-color: #007BFF;
    background: #fff;
}

.payment-option.selected {
    border-color: #007BFF;
    background: #f8f9ff;
}

.payment-option label {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    margin: 0;
}

.payment-option input[type="radio"] {
    margin-top: 0.2rem;
    width: auto;
}

.payment-info strong {
    display: block;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 0.3rem;
}

.recommended-badge {
    background: #28a745;
    color: #fff;
    padding: 0.2rem 0.6rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.payment-info p {
    color: #666;
    margin: 0;
    font-size: 0.95rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .booking-container {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin: 1rem;
    }
    
    .servisor-summary {
        position: static;
    }
    
    .form-row {
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
function selectPayment(methodId) {
    // Update radio button
    document.querySelector(`input[value="${methodId}"]`).checked = true;
    
    // Update visual selection
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
}

// Initialize first payment method as selected
document.addEventListener('DOMContentLoaded', function() {
    const firstPaymentOption = document.querySelector('.payment-option');
    if (firstPaymentOption) {
        firstPaymentOption.classList.add('selected');
    }
});

// Add click handlers to payment options
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            selectPayment(radio.value);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
            $booking_id = $stmt->insert_id;
            $stmt->close();
            
            // Store booking data for payment/confirmation
            $_SESSION['booking_data'] = [
                'booking_id' => $booking_id,
                'booking_number' => $booking_number,
                'servisor_id' => $servisor_id,
                'servisor_name' => $servisor['name'],
                'service_name' => $servisor['service_category'],
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'customer_address' => $customer_address,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'service_description' => $service_description,
                'estimated_cost' => $estimated_cost,
                'payment_method_id' => $payment_method_id
            ];
            
            // Redirect to payment or confirmation
            if ($payment_method_id == 1) { // COD
                $_SESSION['booking_success'] = [
                    'booking_number' => $booking_number,
                    'payment_method' => 'Cash on Delivery',
                    'message' => 'Your booking has been confirmed! Pay cash when the service is completed.'
                ];
                unset($_SESSION['booking_data']);
                header('Location: booking_success.php');
                exit();
            } else {
                header('Location: payment.php');
                exit();
            }
        } else {
            $message = '<div class="message error">Failed to create booking. Please try again.</div>';
            $stmt->close();
        }
    }
}

// Get payment methods
$payment_methods = getPaymentMethods($conn);
?>

<main class="container">
    <div class="booking-container">
        <!-- Servisor Info -->
        <div class="servisor-summary">
            <div class="servisor-card">
                <?php if (!empty($servisor['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="<?php echo htmlspecialchars($servisor['name']); ?>" class="servisor-avatar">
                <?php else: ?>
                    <div class="servisor-avatar servisor-avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <div class="servisor-info">
                    <h3><?php echo htmlspecialchars($servisor['name']); ?></h3>
                    <div class="servisor-badges">
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
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa fa-star<?php echo $i <= $servisor['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                            <span class="rating-text"><?php echo number_format($servisor['rating'], 1); ?> (<?php echo $servisor['total_reviews']; ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="servisor-details">
                        <div class="detail-item">
                            <i class="fa fa-phone"></i>
                            <span><?php echo htmlspecialchars($servisor['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa fa-money-bill"></i>
                            <span>Base Fee: <?php echo formatCurrency($servisor['base_fee']); ?></span>
                        </div>
                        <?php if ($servisor['experience_years'] > 0): ?>
                            <div class="detail-item">
                                <i class="fa fa-clock"></i>
                                <span><?php echo $servisor['experience_years']; ?> years experience</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Form -->
        <div class="booking-form-section">
            <div class="form-header">
                <h2><i class="fa fa-calendar-plus"></i> Book This Service</h2>
                <p>Fill in the details below to book your service</p>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>
            <?php displayFlashMessage(); ?>
            
            <form method="POST" class="booking-form">
                <div class="form-section">
                    <h4><i class="fa fa-user"></i> Customer Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($user_data['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" required placeholder="0771234567 or +94771234567"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($user_data['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Service Address <span class="required">*</span></label>
                        <textarea id="address" name="address" required rows="3" 
                                  placeholder="Enter the complete address where service is needed"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4><i class="fa fa-calendar"></i> Booking Details</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Preferred Date <span class="required">*</span></label>
                            <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Preferred Time <span class="required">*</span></label>
                            <input type="time" id="time" name="time" required
                                   value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Service Description <span class="required">*</span></label>
                        <textarea id="message" name="message" required rows="4" 
                                  placeholder="Describe your specific requirements, any issues you're facing, or additional details about the service needed"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4><i class="fa fa-credit-card"></i> Payment Method</h4>
                    
                    <div class="payment-methods">
                        <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-option" onclick="selectPayment(<?php echo $method['id']; ?>)">
                                <label>
                                    <input type="radio" name="payment_method" value="<?php echo $method['id']; ?>" 
                                           <?php echo $method['id'] == 1 ? 'checked' : ''; ?> required>
                                    <div class="payment-info">
                                        <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                                        <?php if ($method['code'] === 'COD'): ?>
                                            <span class="recommended-badge">Recommended</span>
                                        <?php endif; ?>
                                        <p><?php echo htmlspecialchars($method['description']); ?></p>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-calendar-check"></i> Confirm Booking
                    </button>
                    <a href="servisor_profile.php?id=<?php echo $servisor['id']; ?>" class="btn btn-secondary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.booking-container {
    max-width: 1200px;
    margin: 2rem auto;
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.servisor-summary {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.servisor-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    text-align: center;
}

.servisor-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 1rem auto;
    object-fit: cover;
}

.servisor-avatar-placeholder {
    background: #e3f0ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007BFF;
    font-size: 2.5rem;
}

.servisor-info h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.servisor-badges {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.service-badge {
    background: #007BFF;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.area-badge {
    background: #28a745;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.rating {
    margin-bottom: 1.5rem;
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

.servisor-details {
    text-align: left;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 0.8rem;
    color: #666;
    font-size: 0.95rem;
}

.detail-item i {
    color: #007BFF;
    width: 16px;
    text-align: center;
}

.booking-form-section {
    background: #fff;
    border-radius: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
    color: #fff;
    padding: 2rem;
    text-align: center;
}

.form-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.form-header p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.booking-form {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h4 {
    color: #333;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section h4 i {
    color: #007BFF;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.required {
    color: #dc3545;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e3f0ff;
    border-radius: 0.8rem;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #007BFF;
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-option {
    border: 2px solid #e3f0ff;
    border-radius: 0.8rem;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.payment-option:hover {
    border-color: #007BFF;
    background: #fff;
}

.payment-option.selected {
    border-color: #007BFF;
    background: #f8f9ff;
}

.payment-option label {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    margin: 0;
}

.payment-option input[type="radio"] {
    margin-top: 0.2rem;
    width: auto;
}

.payment-info strong {
    display: block;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 0.3rem;
}

.recommended-badge {
    background: #28a745;
    color: #fff;
    padding: 0.2rem 0.6rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.payment-info p {
    color: #666;
    margin: 0;
    font-size: 0.95rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .booking-container {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin: 1rem;
    }
    
    .servisor-summary {
        position: static;
    }
    
    .form-row {
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
function selectPayment(methodId) {
    // Update radio button
    document.querySelector(`input[value="${methodId}"]`).checked = true;
    
    // Update visual selection
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
}

// Initialize first payment method as selected
document.addEventListener('DOMContentLoaded', function() {
    const firstPaymentOption = document.querySelector('.payment-option');
    if (firstPaymentOption) {
        firstPaymentOption.classList.add('selected');
    }
});

// Add click handlers to payment options
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            selectPayment(radio.value);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>