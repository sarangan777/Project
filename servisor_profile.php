<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare('SELECT * FROM servisors WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $servisor = $result->fetch_assoc();
        // Fetch service details
        $service_icon = '';
        $service_desc = '';
        if (!empty($servisor['service_type'])) {
            $stmt2 = $conn->prepare('SELECT icon, description FROM services WHERE name = ?');
            $stmt2->bind_param('s', $servisor['service_type']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2 && $result2->num_rows > 0) {
                $service = $result2->fetch_assoc();
                $service_icon = $service['icon'];
                $service_desc = $service['description'];
            }
            $stmt2->close();
        }
        echo '<div class="card" style="max-width:400px;margin:2rem auto;">';
        if (!empty($servisor['photo'])) {
            echo '<img src="' . htmlspecialchars($servisor['photo']) . '" alt="' . htmlspecialchars($servisor['name']) . '" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">';
        } else {
            echo '<i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>';
        }
        echo '<h2>' . htmlspecialchars($servisor['name']) . '</h2>';
        echo '<p><strong>Service:</strong> ';
        if ($service_icon) echo '<i class="fa ' . htmlspecialchars($service_icon) . ' fa-lg" style="color:#007BFF;margin-right:0.5rem;"></i> ';
        echo htmlspecialchars($servisor['service_type']) . '</p>';
        if ($service_desc) echo '<p style="font-size:0.97em;color:#444;margin-bottom:0.7rem;">' . nl2br(htmlspecialchars($service_desc)) . '</p>';
        if (!empty($servisor['description'])) echo '<p style="font-size:0.97em;color:#333;margin-bottom:0.7rem;"><strong>About:</strong> ' . nl2br(htmlspecialchars($servisor['description'])) . '</p>';
        if (!empty($servisor['phone'])) echo '<p><strong>Contact:</strong> ' . htmlspecialchars($servisor['phone']) . '</p>';
        if (!empty($servisor['email'])) echo '<p><strong>Email:</strong> ' . htmlspecialchars($servisor['email']) . '</p>';
        if (!empty($servisor['area'])) echo '<p><strong>Area:</strong> ' . htmlspecialchars($servisor['area']) . '</p>';
        if (!empty($servisor['fee'])) echo '<p><strong>Fees:</strong> ' . htmlspecialchars($servisor['fee']) . '</p>';
        if (!empty($servisor['rating'])) echo '<p><strong>Rating:</strong> ' . htmlspecialchars($servisor['rating']) . '/5</p>';
        echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn mt-2">Book Now</a>';
        echo '</div>';
    } else {
        echo '<div class="card">Servisor not found.</div>';
    }
    $stmt->close();
} else {
    echo '<div class="card">No servisor selected.</div>';
}
?>
</main>
<?php include 'includes/footer.php'; ?> 