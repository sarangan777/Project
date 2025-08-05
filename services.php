<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<main class="container">
    <h2>Our Services</h2>
    
    <?php displayFlashMessage(); ?>
    
    <!-- Service Categories Filter -->
    <div style="text-align:center;margin:2rem 0;">
        <a href="services.php" class="btn btn-secondary" style="margin:0.3rem;">All Services</a>
        <?php
        $categories = getServiceCategories($conn);
        $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
        foreach ($categories as $category) {
            $active = ($selected_category == $category['id']) ? 'btn' : 'btn btn-secondary';
            echo '<a href="services.php?category=' . $category['id'] . '" class="' . $active . '" style="margin:0.3rem;">';
            echo '<i class="fa ' . htmlspecialchars($category['icon']) . '"></i> ' . htmlspecialchars($category['name']);
            echo '</a>';
        }
        ?>
    </div>
    
    <div class="services-list">
    <?php
    // Build query based on category filter
    $query = 'SELECT sd.* FROM servisor_details sd WHERE sd.is_approved = 1 AND sd.is_active = 1';
    $params = [];
    $types = '';
    
    if ($selected_category > 0) {
        $query .= ' AND sd.service_category_id = ?';
        $params[] = $selected_category;
        $types .= 'i';
    }
    
    $query .= ' ORDER BY sd.service_category, sd.rating DESC, sd.name ASC';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $servisors = $stmt->get_result();
    
    if ($servisors && $servisors->num_rows > 0) {
        while ($servisor = $servisors->fetch_assoc()) {
            echo '<div class="card" style="text-decoration:none; margin-bottom: 20px;">';
            
            // Profile image
            if (!empty($servisor['profile_image'])) {
                echo '<img src="' . htmlspecialchars($servisor['profile_image']) . '" alt="' . htmlspecialchars($servisor['name']) . '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">';
            } else {
                echo '<i class="fa fa-user fa-3x" style="color:#007BFF;margin-bottom:1rem;"></i>';
            }
            
            echo '<h3>' . htmlspecialchars($servisor['name']) . '</h3>';
            echo '<span style="background:#007bff;color:white;padding:4px 12px;border-radius:8px;font-size:0.9em;margin-bottom:10px;display:inline-block;">';
            echo '<i class="fa ' . htmlspecialchars($servisor['service_icon']) . '"></i> ' . htmlspecialchars($servisor['service_category']);
            echo '</span>';
            
            echo '<p><strong>Area:</strong> ' . htmlspecialchars($servisor['area']) . '</p>';
            echo '<p><strong>Phone:</strong> ' . htmlspecialchars($servisor['phone']) . '</p>';
            echo '<p><strong>Base Fee:</strong> ' . formatCurrency($servisor['base_fee']) . '</p>';
            
            // Rating
            if ($servisor['rating'] > 0) {
                echo '<p><strong>Rating:</strong> ';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $servisor['rating']) {
                        echo '<i class="fa fa-star" style="color:#ffc107;"></i>';
                    } else {
                        echo '<i class="fa fa-star" style="color:#e0e0e0;"></i>';
                    }
                }
                echo ' (' . $servisor['total_reviews'] . ' reviews)</p>';
            }
            
            // Experience
            if ($servisor['experience_years'] > 0) {
                echo '<p><strong>Experience:</strong> ' . $servisor['experience_years'] . ' years</p>';
            }
            
            // Description
            if (!empty($servisor['description'])) {
                echo '<p style="font-size:0.9em;color:#666;">' . htmlspecialchars(substr($servisor['description'], 0, 100)) . '...</p>';
            }
            
            echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn" style="margin-top: 10px;">Book Now</a>';
            echo '<a href="servisor_profile.php?id=' . $servisor['id'] . '" class="btn btn-secondary" style="margin-top: 10px;">View Profile</a>';
            echo '</div>';
        }
        $stmt->close();
    } else {
        $category_text = $selected_category > 0 ? 'in this category' : '';
        echo '<div class="card">No servisors available ' . $category_text . ' yet.</div>';
    }
    ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 