<?php
session_start();
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['servisor_id'])) {
    header('Location: servisor_dashboard.php');
    exit();
}

$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare('SELECT id, name, password, is_approved FROM servisors WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $servisor = $result->fetch_assoc();
            
            if (password_verify($password, $servisor['password'])) {
                if ($servisor['is_approved'] == 1) {
                    $_SESSION['servisor_id'] = $servisor['id'];
                    $_SESSION['servisor_name'] = $servisor['name'];
                    header('Location: servisor_dashboard.php');
                    exit();
                } else {
                    $login_message = '<div class="message error">Your account is pending approval. Please wait for admin approval.</div>';
                }
            } else {
                $login_message = '<div class="message error">Invalid password.</div>';
            }
        } else {
            $login_message = '<div class="message error">No servisor found with that email.</div>';
        }
        $stmt->close();
    } else {
        $login_message = '<div class="message error">Please fill in all fields.</div>';
    }
}

include 'includes/header.php';
?>

<div class="split-layout">
    <div class="split-left">
        <div>
            <h2>Servisor Portal</h2>
            <p>Login to manage your bookings, update your profile, and grow your service business in Jaffna.</p>
            <div style="margin-top: 2rem;">
                <i class="fa fa-tools" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="split-right">
        <div style="width: 100%; max-width: 400px;">
            <form action="servisor_login.php" method="POST" class="form">
                <h2>Servisor Login</h2>
                <?php if (!empty($login_message)) echo $login_message; ?>
                
                <div>
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fa fa-sign-in-alt"></i> Login
                </button>
                
                <p>Don't have an account? <a href="servisor_signup.php">Sign up here</a></p>
                <p><a href="login.php">Customer Login</a></p>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</main>

<?php include 'includes/footer.php'; ?> 