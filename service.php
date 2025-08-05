<?php 
session_start();
include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
<?php
if (isset($_GET['servisor_id'])) {
    $servisor_id = intval($_GET['servisor_id']);
    $stmt = $conn->prepare('SELECT * FROM servisors WHERE id = ? AND is_approved = 1');
    $stmt->bind_param('i', $servisor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $servisor = $result->fetch_assoc();
        echo '<div class="card" style="max-width:600px;margin:2rem auto;">';
        if (!empty($servisor['profile_image'])) {
            echo '<img src="assets/img/servisors/' . htmlspecialchars($servisor['profile_image']) . '" alt="' . htmlspecialchars($servisor['name']) . '" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">';
        } else {
            echo '<i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>';
        }
        echo '<h2>' . htmlspecialchars($servisor['name']) . '</h2>';
        echo '<span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;">' . htmlspecialchars($servisor['service_type']) . '</span>';
        echo '<p><strong>Phone:</strong> ' . htmlspecialchars($servisor['phone']) . '</p>';
        echo '<p><strong>Email:</strong> ' . htmlspecialchars($servisor['email']) . '</p>';
        echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn" style="margin-top: 10px;">Book This Servisor</a>';
        echo '</div>';
    } else {
        echo '<div class="card">Servisor not found or not approved.</div>';
    }
    $stmt->close();
} else {
    // Show all servisors grouped by service type
    echo '<h2>Available Services</h2>';
    $service_types = ['Plumber', 'Mason', 'Electrician', 'Carpenter', 'Technician'];
    
    foreach ($service_types as $service_type) {
        $stmt = $conn->prepare('SELECT * FROM servisors WHERE service_type = ? AND is_approved = 1');
        $stmt->bind_param('s', $service_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            echo '<h3>' . htmlspecialchars($service_type) . 's</h3>';
            echo '<div class="services-list">';
            while ($servisor = $result->fetch_assoc()) {
                echo '<div class="card" style="max-width:220px;text-decoration:none; margin-bottom: 20px;">';
                if (!empty($servisor['profile_image'])) {
                    echo '<img src="assets/img/servisors/' . htmlspecialchars($servisor['profile_image']) . '" alt="' . htmlspecialchars($servisor['name']) . '" style="width:70px;height:70px;border-radius:50%;object-fit:cover;margin-bottom:0.7rem;">';
                } else {
                    echo '<i class="fa fa-user fa-2x" style="color:#007BFF;margin-bottom:0.7rem;"></i>';
                }
                echo '<h3>' . htmlspecialchars($servisor['name']) . '</h3>';
                echo '<p><strong>Phone:</strong> ' . htmlspecialchars($servisor['phone']) . '</p>';
                echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn" style="margin-top: 10px;">Book Now</a>';
                echo '</div>';
            }
            echo '</div>';
        }
        $stmt->close();
    }
}
?>
</main>
<?php include 'includes/footer.php'; ?> 