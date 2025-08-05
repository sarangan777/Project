<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
    <h2>Book a Service</h2>
    
    <?php
    $message = '';
    $servisor_id = isset($_GET['servisor_id']) ? intval($_GET['servisor_id']) : 0;
    
    // Get servisor details if ID provided
    $servisor = null;
    if ($servisor_id) {
        $stmt = $conn->prepare('SELECT sd.*, sc.name as service_category FROM servisor_details sd JOIN service_categories sc ON sd.service_category = sc.name WHERE sd.id = ? AND sd.is_approved = 1');
        $stmt->bind_param('i', $servisor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $servisor = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_name = sanitizeInput($_POST['name']);
        $customer_phone = sanitizeInput($_POST['phone']);
        $customer_email = sanitizeInput($_POST['email']);
        $customer_address = sanitizeInput($_POST['address']);
        $booking_date = sanitizeInput($_POST['date']);
        $booking_time = sanitizeInput($_POST['time']);
        $service_description = sanitizeInput($_POST['message']);
        $selected_servisor_id = intval($_POST['servisor']);
        
        // Validation
        if (empty($customer_name) || empty($customer_phone) || empty($customer_address) || 
            empty($booking_date) || empty($booking_time) || !$selected_servisor_id) {
            $message = '<div class="card" style="color:red;">All required fields must be filled.</div>';
        } elseif (!isValidPhone($customer_phone)) {
            $message = '<div class="card" style="color:red;">Please enter a valid phone number.</div>';
        } elseif (!empty($customer_email) && !isValidEmail($customer_email)) {
            $message = '<div class="card" style="color:red;">Please enter a valid email address.</div>';
        } else {
            // Get servisor details for booking
            $stmt = $conn->prepare('SELECT sd.*, sc.name as service_category FROM servisor_details sd JOIN service_categories sc ON sd.service_category = sc.name WHERE sd.id = ? AND sd.is_approved = 1');
            $stmt->bind_param('i', $selected_servisor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $selected_servisor = $result->fetch_assoc();
                
                // Store booking data in session for payment processing
                $_SESSION['booking_data'] = [
                    'servisor_id' => $selected_servisor_id,
                    'servisor_name' => $selected_servisor['name'],
                    'service_name' => $selected_servisor['service_category'],
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                    'customer_email' => $customer_email,
                    'customer_address' => $customer_address,
                    'booking_date' => $booking_date,
                    'booking_time' => $booking_time,
                    'service_description' => $service_description,
                    'estimated_cost' => $selected_servisor['base_fee']
                ];
                
                // Redirect to payment page
                header('Location: payment.php');
                exit();
            } else {
                $message = '<div class="card" style="color:red;">Selected servisor is not available.</div>';
            }
            $stmt->close();
        }
    }
    
    displayFlashMessage();
    if ($message) echo $message;
    ?>
    
    <?php if ($servisor): ?>
    <div class="card" style="max-width:500px;margin:2rem auto;text-align:center;">
        <?php if (!empty($servisor['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($servisor['profile_image']); ?>" alt="<?php echo htmlspecialchars($servisor['name']); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
        <?php else: ?>
            <i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($servisor['name']); ?></h3>
        <span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;"><?php echo htmlspecialchars($servisor['service_category']); ?></span>
        <p><strong>Area:</strong> <?php echo htmlspecialchars($servisor['area']); ?></p>
        <p><strong>Base Fee:</strong> <?php echo formatCurrency($servisor['base_fee']); ?></p>
        <?php if ($servisor['rating'] > 0): ?>
        <p><strong>Rating:</strong> <?php echo $servisor['rating']; ?>/5 (<?php echo $servisor['total_reviews']; ?> reviews)</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="form">
        <h3>Booking Details</h3>
        
        <label for="name">Your Name: <span style="color:red;">*</span></label>
        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        
        <label for="phone">Phone Number: <span style="color:red;">*</span></label>
        <input type="text" id="phone" name="phone" required placeholder="077XXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        
        <label for="address">Service Address: <span style="color:red;">*</span></label>
        <textarea id="address" name="address" required placeholder="Enter the full address where service is needed"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
        
        <label for="date">Preferred Date: <span style="color:red;">*</span></label>
        <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
        
        <label for="time">Preferred Time: <span style="color:red;">*</span></label>
        <input type="time" id="time" name="time" required value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
        
        <label for="servisor">Select Servisor: <span style="color:red;">*</span></label>
        <select id="servisor" name="servisor" required>
            <option value="">Choose a servisor</option>
            <?php
            $servisors = $conn->query('SELECT sd.* FROM servisor_details sd WHERE sd.is_approved = 1 AND sd.is_active = 1 ORDER BY sd.service_category, sd.name');
            $current_category = '';
            while ($row = $servisors->fetch_assoc()) {
                if ($current_category !== $row['service_category']) {
                    if ($current_category !== '') echo '</optgroup>';
                    echo '<optgroup label="' . htmlspecialchars($row['service_category']) . '">';
                    $current_category = $row['service_category'];
                }
                $selected = ($servisor_id && $servisor_id == $row['id']) ? 'selected' : '';
                $rating_text = $row['rating'] > 0 ? ' - ' . $row['rating'] . '/5' : '';
                echo '<option value="' . $row['id'] . '" ' . $selected . '>' . 
                     htmlspecialchars($row['name'] . ' (' . $row['area'] . ')' . $rating_text) . '</option>';
            }
            if ($current_category !== '') echo '</optgroup>';
            ?>
        </select>
        
        <label for="message">Additional Requirements:</label>
        <textarea id="message" name="message" placeholder="Describe your specific requirements or any additional details"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
        
        <button type="submit" class="btn">Proceed to Payment</button>
    </form>
    
    <div style="text-align:center;margin-top:2rem;">
        <a href="services.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Services
        </a>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 