<?php
include 'includes/header.php';
include 'includes/db.php';
session_start();

$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    // Default admin credentials
    $admin_username = 'admin@gmail.com';
    $admin_password = '12345678';
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['user_id'] = 1;
        $_SESSION['is_admin'] = 1;
        $login_message = '<div class="card" style="color:green;">Admin login successful! Redirecting to dashboard...</div>';
        echo '<script>setTimeout(function(){ window.location.href = "admin/dashboard.php"; }, 1500);</script>';
    } else {
        // Check for regular user in database
        $stmt = $conn->prepare('SELECT id, password, is_admin FROM users WHERE email = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $is_admin);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['is_admin'] = $is_admin;
                $login_message = '<div class="card" style="color:green;">Login successful! Redirecting to home...</div>';
                echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 1500);</script>';
            } else {
                $login_message = '<div class="card" style="color:red;">Invalid password.</div>';
            }
        } else {
            $login_message = '<div class="card" style="color:red;">No user found with that email.</div>';
        }
        $stmt->close();
    }
}
?>

<main class="container">
    <h2>Login</h2>
    <?php if (!empty($login_message)) echo $login_message; ?>
    <form action="login.php" method="POST" class="form">
        <label for="username">Email or Admin Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn">Login</button>
        <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    </form>
</main>

<?php include 'includes/footer.php'; ?> 