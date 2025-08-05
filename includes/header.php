<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaffna Services Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fa fa-tools" style="margin-right:0.5rem;"></i>
                <span>Jaffna Services</span>
            </a>
            <div class="nav-toggle" onclick="toggleNav()">
                <i class="fa fa-bars"></i>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="services.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'class="active"' : ''; ?>>Services</a></li>
                <li><a href="about.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'class="active"' : ''; ?>>About</a></li>
                <li><a href="contact.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'class="active"' : ''; ?>>Contact</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                        <li><a href="admin/dashboard.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) ? 'class="active"' : ''; ?>>Admin Panel</a></li>
                        <li><a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'class="active"' : ''; ?>>Profile</a></li>
                    <?php else: ?>
                        <li><a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'class="active"' : ''; ?>>My Profile</a></li>
                        <li><a href="my_bookings.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'my_bookings.php') ? 'class="active"' : ''; ?>>My Bookings</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" style="background:#dc3545;border-radius:0.5rem;padding:0.4rem 0.8rem;">Logout</a></li>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <li><a href="login.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'class="active"' : ''; ?>>Login</a></li>
                    <li><a href="signup.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'signup.php') ? 'class="active"' : ''; ?>>Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <script>
    function toggleNav() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('open');
    }
    
    // Close nav when clicking outside
    document.addEventListener('click', function(event) {
        const navLinks = document.getElementById('navLinks');
        const navToggle = document.querySelector('.nav-toggle');
        
        if (!navLinks.contains(event.target) && !navToggle.contains(event.target)) {
            navLinks.classList.remove('open');
        }
    });
    </script>
</body>
</html>