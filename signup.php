<?php
session_start();
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$signup_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $signup_message = '<div class="message error">All fields are required.</div>';
    } elseif ($password !== $confirm_password) {
        $signup_message = '<div class="message error">Passwords do not match.</div>';
    } elseif (strlen($password) < 6) {
        $signup_message = '<div class="message error">Password must be at least 6 characters long.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_message = '<div class="message error">Please enter a valid email address.</div>';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $signup_message = '<div class="message error">Email already registered. Please use a different email.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = 0;
            $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, is_admin) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssi', $name, $email, $phone, $hashed_password, $is_admin);
            if ($stmt->execute()) {
                // Auto login after successful signup
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = 0;
                header('Location: index.php');
                exit();
            } else {
                $signup_message = '<div class="message error">Signup failed. Please try again.</div>';
            }
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="split-layout">
    <div class="split-left">
        <div>
            <h2>Join Jaffna Services</h2>
            <p>Create your account to book trusted local services and connect with skilled professionals in Jaffna.</p>
            <div style="margin-top: 2rem;">
                <i class="fa fa-user-plus" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="split-right">
        <div style="width: 100%; max-width: 400px;">
            <form action="signup.php" method="POST" class="form">
                <h2>Create Account</h2>
                <?php if (!empty($signup_message)) echo $signup_message; ?>
                
                <div>
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div>
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div>
                    <label for="phone">Phone Number:</label>
                    <input type="text" id="phone" name="phone" required placeholder="0771234567" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Create a password (min 6 characters)">
                </div>
                
                <div>
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fa fa-user-plus"></i> Create Account
                </button>
                
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="servisor_signup.php">Join as a Service Provider</a></p>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>