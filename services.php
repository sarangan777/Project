<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
    <h2>Our Services</h2>
    <div class="services-list">
    <?php
    $servisors = $conn->query('SELECT * FROM servisors WHERE is_approved = 1 ORDER BY service_type, name ASC');
    if ($servisors && $servisors->num_rows > 0) {
        while ($servisor = $servisors->fetch_assoc()) {
            echo '<div class="card" style="text-decoration:none; margin-bottom: 20px;">';
            echo '<h3>' . htmlspecialchars($servisor['name']) . '</h3>';
            echo '<span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;">' . htmlspecialchars($servisor['service_type']) . '</span>';
            echo '<p><strong>Phone:</strong> ' . htmlspecialchars($servisor['phone']) . '</p>';
            echo '<p><strong>Email:</strong> ' . htmlspecialchars($servisor['email']) . '</p>';
            echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn" style="margin-top: 10px;">Book Now</a>';
            echo '</div>';
        }
    } else {
        echo '<div class="card">No servisors available yet.</div>';
    }
    ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?> 