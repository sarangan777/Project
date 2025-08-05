<?php
session_start();
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['servisor_id'])) {
    header('Location: servisor_dashboard.php');
    exit();
}

$signup_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $service_category_id = intval($_POST['service_category_id']);
    $area_id = intval($_POST['area_id']);
    $experience_years = intval($_POST['experience_years']);
    $base_fee = floatval($_POST['base_fee']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || 
        $service_category_id <= 0 || $area_id <= 0) {
        $signup_message = '<div class="message error">All required fields must be filled.</div>';
    } elseif ($password !== $confirm_password) {
        $signup_message = '<div class="message error">Passwords do not match.</div>';
    } elseif (strlen($password) < 6) {
        $signup_message = '<div class="message error">Password must be at least 6 characters long.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_message = '<div class="message error">Please enter a valid email address.</div>';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM servisors WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $signup_message = '<div class="message error">Email already registered. Please use a different email.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO servisors (name, email, phone, password, service_category_id, area_id, description, experience_years, base_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssiisd', $name, $email, $phone, $hashed_password, $service_category_id, $area_id, $description, $experience_years, $base_fee);
            if ($stmt->execute()) {
                $signup_message = '<div class="message success">Signup successful! Please wait for admin approval before you can login.</div>';
                // Clear form data on success
                $_POST = [];
            } else {
                $signup_message = '<div class="message error">Signup failed. Please try again.</div>';
            }
        }
        $stmt->close();
    }
}

// Get service categories and areas
$service_categories = getServiceCategories($conn);
$areas = getAreas($conn);

include 'includes/header.php';
?>

<div class="split-layout">
    <div class="split-left">
        <div>
            <h2>Join as a Service Provider</h2>
            <p>Register your services and connect with customers in Jaffna. Grow your business with our trusted platform.</p>
            <div style="margin-top: 2rem;">
                <i class="fa fa-handshake" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="split-right">
        <div style="width: 100%; max-width: 500px;">
            <form action="servisor_signup.php" method="POST" class="form">
                <h2>Servisor Registration</h2>
                <?php if (!empty($signup_message)) echo $signup_message; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="name">Full Name <span style="color: red;">*</span>:</label>
                        <input type="text" id="name" name="name" required placeholder="Your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div>
                        <label for="phone">Phone Number <span style="color: red;">*</span>:</label>
                        <input type="text" id="phone" name="phone" required placeholder="0771234567" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>
                
                <div>
                    <label for="email">Email Address <span style="color: red;">*</span>:</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="service_category_id">Service Category <span style="color: red;">*</span>:</label>
                        <select id="service_category_id" name="service_category_id" required>
                            <option value="">Select Service</option>
                            <?php foreach ($service_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['service_category_id']) && $_POST['service_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="area_id">Service Area <span style="color: red;">*</span>:</label>
                        <select id="area_id" name="area_id" required>
                            <option value="">Select Area</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['id']; ?>" <?php echo (isset($_POST['area_id']) && $_POST['area_id'] == $area['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="experience_years">Experience (Years):</label>
                        <input type="number" id="experience_years" name="experience_years" min="0" placeholder="0" value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : ''; ?>">
                    </div>
                    <div>
                        <label for="base_fee">Base Fee (LKR):</label>
                        <input type="number" id="base_fee" name="base_fee" min="0" step="0.01" placeholder="2000.00" value="<?php echo isset($_POST['base_fee']) ? htmlspecialchars($_POST['base_fee']) : ''; ?>">
                    </div>
                </div>
                
                <div>
                    <label for="description">Service Description:</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe your services and expertise"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="password">Password <span style="color: red;">*</span>:</label>
                        <input type="password" id="password" name="password" required placeholder="Create password (min 6 chars)">
                    </div>
                    <div>
                        <label for="confirm_password">Confirm Password <span style="color: red;">*</span>:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fa fa-user-plus"></i> Register as Servisor
                </button>
                
                <p>Already have an account? <a href="servisor_login.php">Login here</a></p>
                <p><a href="signup.php">Register as Customer</a></p>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 