<?php
session_start();
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Default admin credentials
    $admin_username = 'admin@gmail.com';
    $admin_password = '12345678';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Admin';
        $_SESSION['is_admin'] = 1;
        header('Location: admin/dashboard.php');
        exit();
    } else {
        // Check for regular user in database
        $stmt = $conn->prepare('SELECT id, name, password, is_admin FROM users WHERE email = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $id = $user['id'];
            $name = $user['name'];
            $hashed_password = $user['password'];
            $is_admin = $user['is_admin'];
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = $is_admin;
                
                if ($is_admin == 1) {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $login_message = '<div class="message error">Invalid password.</div>';
            }
        } else {
            $login_message = '<div class="message error">No user found with that email.</div>';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="split-layout">
    <div class="split-left">
        <div>
            <h2>Welcome Back!</h2>
            <p>Sign in to access your account and book trusted services in Jaffna.</p>
            <div style="margin-top: 2rem;">
                <i class="fa fa-tools" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
    <div class="split-right">
        <div style="width: 100%; max-width: 400px;">
            <form action="login.php" method="POST" class="form">
                <h2>Login</h2>
                <?php if (!empty($login_message)) echo $login_message; ?>
                
                <div>
                    <label for="username">Email Address:</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your email">
                </div>
                
                <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fa fa-sign-in-alt"></i> Login
                </button>
                
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                <p><a href="servisor_signup.php">Join as a Service Provider</a></p>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>