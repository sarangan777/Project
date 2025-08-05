<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
    <section class="hero">
        <h1>Welcome to Jaffna Services Booking</h1>
        <p>Find trusted servisors in Jaffna for all your home and business needs.</p>
        <a href="login.php" class="btn">Login</a>
        <a href="signup.php" class="btn btn-secondary">Sign Up</a>
    </section>
    <section class="featured-services">
        <h2>Featured Services</h2>
        <div class="services-list">
        <?php
        $featured = $conn->query('SELECT id, name, service_type, phone, email FROM servisors WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 5');
        if ($featured && $featured->num_rows > 0) {
            while ($row = $featured->fetch_assoc()) {
                echo '<div class="card" style="text-decoration:none; margin-bottom: 20px;">';
                echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                echo '<span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;">' . htmlspecialchars($row['service_type']) . '</span>';
                echo '<p><strong>Phone:</strong> ' . htmlspecialchars($row['phone']) . '</p>';
                echo '<p><strong>Email:</strong> ' . htmlspecialchars($row['email']) . '</p>';
                echo '<a href="booking.php?servisor_id=' . $row['id'] . '" class="btn" style="margin-top: 10px;">Book Now</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="card">No featured services available yet.</div>';
        }
        ?>
        </div>
    </section>
    <section class="featured-services">
        <h2>All Services</h2>
        <div class="services-list">
        <?php
        $result = $conn->query('SELECT id, name, service_type, phone, email FROM servisors WHERE is_approved = 1 ORDER BY service_type, name ASC');
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="card" style="text-decoration:none; margin-bottom: 20px;">';
                echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                echo '<span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;">' . htmlspecialchars($row['service_type']) . '</span>';
                echo '<p><strong>Phone:</strong> ' . htmlspecialchars($row['phone']) . '</p>';
                echo '<p><strong>Email:</strong> ' . htmlspecialchars($row['email']) . '</p>';
                echo '<a href="booking.php?servisor_id=' . $row['id'] . '" class="btn" style="margin-top: 10px;">Book Now</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="card">No services available yet.</div>';
        }
        ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?> 