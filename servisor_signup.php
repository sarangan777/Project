<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
    <h2>Servisor Signup</h2>
    <?php
    $signup_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $service_type = trim($_POST['service_type']);
        
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM servisors WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $signup_message = '<div class="card" style="color:red;">Email already registered.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO servisors (name, email, phone, password, service_type) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $name, $email, $phone, $hashed_password, $service_type);
            if ($stmt->execute()) {
                $signup_message = '<div class="card" style="color:green;">Signup successful! Please wait for admin approval.</div>';
                echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 1500);</script>';
            } else {
                $signup_message = '<div class="card" style="color:red;">Signup failed. Please try again.</div>';
            }
        }
        $stmt->close();
    }
    ?>
    <form action="servisor_signup.php" method="POST" class="form">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="service_type">Service Type:</label>
        <select id="service_type" name="service_type" required>
            <option value="">Select Service</option>
            <option value="Plumber">Plumber</option>
            <option value="Mason">Mason</option>
            <option value="Electrician">Electrician</option>
            <option value="Carpenter">Carpenter</option>
            <option value="Technician">Technician</option>
        </select>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn">Sign Up</button>
        <p>Already have an account? <a href="servisor_login.php">Login</a></p>
    </form>
</main>

<?php include 'includes/footer.php'; ?> 