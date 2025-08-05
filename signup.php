<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
    <h2>User Signup</h2>
    <?php
    $signup_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $signup_message = '<div class="card" style="color:red;">Email already registered.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = 0;
            $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, is_admin) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssi', $name, $email, $phone, $hashed_password, $is_admin);
            if ($stmt->execute()) {
                $signup_message = '<div class="card" style="color:green;">Signup successful! Redirecting to home...</div>';
                echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 1500);</script>';
            } else {
                $signup_message = '<div class="card" style="color:red;">Signup failed. Please try again.</div>';
            }
        }
        $stmt->close();
    }
    ?>
    <form action="signup.php" method="POST" class="form">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn">Sign Up</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
</main>

<?php include 'includes/footer.php'; ?> 