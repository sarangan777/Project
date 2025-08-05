<?php 
session_start();
$page_title = 'Services';
include 'includes/db.php'; 
include 'includes/header.php'; 
?>

<main class="container">
    <div class="page-header">
        <h1><i class="fa fa-list"></i> Our Services</h1>
        <p>Find trusted service providers in Jaffna for all your needs</p>
    </div>
    
    <?php displayFlashMessage(); ?>
    
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-tabs">
            <?php
            $categories = getServiceCategories($conn);
            $areas = getAreas($conn);
            $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
            $selected_area = isset($_GET['area']) ? intval($_GET['area']) : 0;
            $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
            ?>
            
            <div class="filter-group">
                <label>Service Category:</label>
                <select id="categoryFilter" onchange="applyFilters()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($selected_category == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Area:</label>
                <select id="areaFilter" onchange="applyFilters()">
                    <option value="">All Areas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>" <?php echo ($selected_area == $area['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" id="searchFilter" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="applyFilters()">
            </div>
        </div>
    </div>
    
    <!-- Results -->
    <div class="results-section">
        <?php
        // Build query
        $query = "SELECT * FROM servisor_details WHERE is_approved = 1 AND is_active = 1";
        $params = [];
        $types = '';
        
        if ($selected_category > 0) {
            $query .= " AND service_category_id = ?";
            $params[] = $selected_category;
            $types .= 'i';
        }
        
        if ($selected_area > 0) {
            $query .= " AND area_id = ?";
            $params[] = $selected_area;
            $types .= 'i';
        }
        
        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR service_category LIKE ? OR area LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $query .= " ORDER BY rating DESC, total_reviews DESC, name ASC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $servisors = $stmt->get_result();
        
        if ($servisors->num_rows > 0) {
            echo '<div class="results-header">';
            echo '<h3>Found ' . $servisors->num_rows . ' service providers</h3>';
            echo '</div>';
            
            echo '<div class="servisors-grid">';
            while ($servisor = $servisors->fetch_assoc()) {
                echo '<div class="servisor-card">';
                
                // Profile image
                if (!empty($servisor['profile_image'])) {
                    echo '<div class="servisor-avatar">';
                    echo '<img src="' . htmlspecialchars($servisor['profile_image']) . '" alt="' . htmlspecialchars($servisor['name']) . '">';
                    echo '</div>';
                } else {
                    echo '<div class="servisor-avatar servisor-avatar-placeholder">';
                    echo '<i class="fa fa-user"></i>';
                    echo '</div>';
                }
                
                echo '<div class="servisor-info">';
                echo '<h4>' . htmlspecialchars($servisor['name']) . '</h4>';
                
                echo '<div class="servisor-badges">';
                echo '<span class="service-badge">';
                echo '<i class="fa ' . htmlspecialchars($servisor['service_icon']) . '"></i> ';
                echo htmlspecialchars($servisor['service_category']);
                echo '</span>';
                echo '<span class="area-badge">';
                echo '<i class="fa fa-map-marker-alt"></i> ' . htmlspecialchars($servisor['area']);
                echo '</span>';
                echo '</div>';
                
                // Rating
                if ($servisor['rating'] > 0) {
                    echo '<div class="rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $servisor['rating']) {
                            echo '<i class="fa fa-star"></i>';
                        } else {
                            echo '<i class="fa fa-star-o"></i>';
                        }
                    }
                    echo '<span class="rating-text">' . number_format($servisor['rating'], 1) . ' (' . $servisor['total_reviews'] . ' reviews)</span>';
                    echo '</div>';
                }
                
                // Experience and fee
                echo '<div class="servisor-details">';
                if ($servisor['experience_years'] > 0) {
                    echo '<div class="detail-item">';
                    echo '<i class="fa fa-clock"></i> ' . $servisor['experience_years'] . ' years experience';
                    echo '</div>';
                }
                echo '<div class="detail-item fee">';
                echo '<i class="fa fa-money-bill"></i> Starting from ' . formatCurrency($servisor['base_fee']);
                echo '</div>';
                echo '</div>';
                
                // Description
                if (!empty($servisor['description'])) {
                    $description = strlen($servisor['description']) > 100 ? 
                        substr($servisor['description'], 0, 100) . '...' : 
                        $servisor['description'];
                    echo '<p class="servisor-description">' . htmlspecialchars($description) . '</p>';
                }
                
                echo '</div>';
                
                // Actions
                echo '<div class="servisor-actions">';
                echo '<a href="servisor_profile.php?id=' . $servisor['id'] . '" class="btn btn-outline btn-sm">';
                echo '<i class="fa fa-eye"></i> View Profile';
                echo '</a>';
                echo '<a href="booking.php?servisor_id=' . $servisor['id'] . '" class="btn btn-primary btn-sm">';
                echo '<i class="fa fa-calendar-plus"></i> Book Now';
                echo '</a>';
                echo '</div>';
                
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="no-results">';
            echo '<i class="fa fa-search fa-3x"></i>';
            echo '<h3>No Service Providers Found</h3>';
            echo '<p>Try adjusting your search criteria or browse all categories.</p>';
            echo '<a href="services.php" class="btn btn-primary">View All Services</a>';
            echo '</div>';
        }
        
        $stmt->close();
        ?>
    </div>
</main>

<style>
.page-header {
    text-align: center;
    padding: 2rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    margin-bottom: 3rem;
    border-radius: 1rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.page-header p {
    font-size: 1.2rem;
    color: #666;
}

.filters-section {
    background: #fff;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0,123,255,0.08);
    margin-bottom: 2rem;
}

.filter-tabs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.filter-group select,
.filter-group input {
    padding: 0.8rem;
    border: 2px solid #e3f0ff;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.filter-group select:focus,
.filter-group input:focus {
    border-color: #007BFF;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.results-section {
    margin-top: 2rem;
}

.results-header {
    margin-bottom: 2rem;
}

.results-header h3 {
    font-size: 1.3rem;
    color: #333;
    font-weight: 600;
}

.servisors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.servisor-card {
    background: #fff;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,123,255,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0,123,255,0.05);
}

.servisor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,123,255,0.15);
}

.servisor-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 1.5rem auto;
    overflow: hidden;
}

.servisor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.servisor-avatar-placeholder {
    background: #e3f0ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #007BFF;
    font-size: 2rem;
}

.servisor-info {
    text-align: center;
    flex-grow: 1;
}

.servisor-info h4 {
    font-size: 1.4rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.servisor-badges {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.service-badge {
    background: #007BFF;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.area-badge {
    background: #28a745;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.rating {
    margin-bottom: 1rem;
}

.rating i {
    color: #ffc107;
    margin-right: 0.2rem;
}

.rating-text {
    color: #666;
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

.servisor-details {
    margin-bottom: 1rem;
}

.detail-item {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.detail-item.fee {
    font-weight: 600;
    color: #007BFF;
}

.servisor-description {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 1.5rem;
    text-align: left;
}

.servisor-actions {
    display: flex;
    gap: 1rem;
    margin-top: auto;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.no-results i {
    color: #ccc;
    margin-bottom: 1rem;
}

.no-results h3 {
    color: #333;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .filter-tabs {
        grid-template-columns: 1fr;
    }
    
    .servisors-grid {
        grid-template-columns: 1fr;
    }
    
    .servisor-actions {
        flex-direction: column;
    }
}
</style>

<script>
function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const area = document.getElementById('areaFilter').value;
    const search = document.getElementById('searchFilter').value;
    
    let url = 'services.php?';
    const params = [];
    
    if (category) params.push('category=' + category);
    if (area) params.push('area=' + area);
    if (search) params.push('search=' + encodeURIComponent(search));
    
    url += params.join('&');
    window.location.href = url;
}

// Debounce search input
let searchTimeout;
document.getElementById('searchFilter').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});
</script>

<?php include 'includes/footer.php'; ?>