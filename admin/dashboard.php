<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get dashboard statistics
$stats = getDashboardStats($conn);

// Handle form submissions
$message = '';

// Handle Add Servisor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_servisor'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $category_id = intval($_POST['category_id']);
    $area_id = intval($_POST['area_id']);
    $fee = floatval($_POST['fee']);
    $experience = intval($_POST['experience']);
    $description = sanitizeInput($_POST['description']);
    $password = password_hash('servisor123', PASSWORD_DEFAULT);
    
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = uploadFile($_FILES['photo'], '../' . UPLOAD_DIR);
        if ($uploaded_file) {
            $photo_path = UPLOAD_DIR . $uploaded_file;
        }
    }
    
    if ($name && $email && $phone && $category_id && $area_id && $fee) {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM servisors WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = '<div class="message error">Email already exists.</div>';
        } else {
            $stmt = $conn->prepare('INSERT INTO servisors (name, email, phone, password, service_category_id, area_id, base_fee, experience_years, description, profile_image, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
            $stmt->bind_param('ssssiiidss', $name, $email, $phone, $password, $category_id, $area_id, $fee, $experience, $description, $photo_path);
            
            if ($stmt->execute()) {
                logAdminActivity($conn, $_SESSION['user_id'], 'Added servisor', 'servisors', $stmt->insert_id, null, [
                    'name' => $name,
                    'email' => $email,
                    'service_category_id' => $category_id,
                    'area_id' => $area_id
                ]);
                
                $message = '<div class="message success">Servisor added successfully!</div>';
            } else {
                $message = '<div class="message error">Failed to add servisor.</div>';
            }
        }
        $stmt->close();
    } else {
        $message = '<div class="message error">All required fields must be filled.</div>';
    }
}

// Handle Delete Servisor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_servisor_id'])) {
    $servisor_id = intval($_POST['delete_servisor_id']);
    
    // Get servisor details before deletion
    $stmt = $conn->prepare('SELECT name, email FROM servisors WHERE id = ?');
    $stmt->bind_param('i', $servisor_id);
    $stmt->execute();
    $servisor_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($servisor_data) {
        $stmt = $conn->prepare('DELETE FROM servisors WHERE id = ?');
        $stmt->bind_param('i', $servisor_id);
        if ($stmt->execute()) {
            logAdminActivity($conn, $_SESSION['user_id'], 'Deleted servisor', 'servisors', $servisor_id, $servisor_data, null);
            $message = '<div class="message success">Servisor deleted successfully!</div>';
        } else {
            $message = '<div class="message error">Failed to delete servisor.</div>';
        }
        $stmt->close();
    }
}

// Get service categories and areas
$service_categories = getServiceCategories($conn);
$areas = getAreas($conn);

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Jaffna Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2563eb 0%, #1e40af 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #fff;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            width: 100%;
            padding: 1rem;
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .admin-header {
            background: #fff;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,123,255,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #e3f0ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007BFF;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,123,255,0.08);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .stat-icon.users { background: #007BFF; }
        .stat-icon.bookings { background: #28a745; }
        .stat-icon.today { background: #ffc107; }
        .stat-icon.pending { background: #fd7e14; }
        .stat-icon.revenue { background: #6f42c1; }
        
        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .stat-info p {
            color: #666;
            font-weight: 500;
        }
        
        .admin-section {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,123,255,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-content {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.8rem;
            border: 2px solid #e3f0ff;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007BFF;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #007BFF;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .servisors-list {
            display: grid;
            gap: 1rem;
        }
        
        .servisor-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.8rem;
            border: 1px solid #e9ecef;
        }
        
        .servisor-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .servisor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: #e3f0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007BFF;
        }
        
        .servisor-details h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .servisor-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .message {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <i class="fa fa-tools"></i>
                    Jaffna Services
                    <span class="admin-badge">ADMIN</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="#dashboard" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fa fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#add-servisor" class="nav-link" onclick="showSection('add-servisor')">
                        <i class="fa fa-user-plus"></i>
                        Add Servisor
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#manage-servisors" class="nav-link" onclick="showSection('manage-servisors')">
                        <i class="fa fa-users"></i>
                        Manage Servisors
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#bookings" class="nav-link" onclick="showSection('bookings')">
                        <i class="fa fa-calendar-alt"></i>
                        Bookings
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fa fa-globe"></i>
                        View Website
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="fa fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Admin Dashboard</h1>
                <div class="admin-user">
                    <div class="user-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                </div>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fa fa-chart-bar"></i>
                        Dashboard Overview
                    </h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon users">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_servisors']; ?></h3>
                                <p>Total Servisors</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="fa fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_bookings']; ?></h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon today">
                                <i class="fa fa-calendar-day"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['todays_bookings']; ?></h3>
                                <p>Today's Bookings</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fa fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_approvals']; ?></h3>
                                <p>Pending Approvals</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="fa fa-money-bill"></i>
                            </div>
                            <div class="stat-info">
                                <h3>LKR <?php echo number_format($stats['monthly_revenue'], 0); ?></h3>
                                <p>Monthly Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Servisor Section -->
            <div id="add-servisor-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fa fa-user-plus"></i>
                        Add New Servisor
                    </h2>
                </div>
                <div class="section-content">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_servisor" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="text" name="phone" required placeholder="0771234567">
                            </div>
                            
                            <div class="form-group">
                                <label>Service Category *</label>
                                <select name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($service_categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Service Area *</label>
                                <select name="area_id" required>
                                    <option value="">Select Area</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Base Fee (LKR) *</label>
                                <input type="number" name="fee" required min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label>Experience (Years) *</label>
                                <input type="number" name="experience" required min="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Profile Photo</label>
                                <input type="file" name="photo" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Brief description of services offered"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-plus"></i>
                            Add Servisor
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Manage Servisors Section -->
            <div id="manage-servisors-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fa fa-users"></i>
                        Manage Servisors
                    </h2>
                </div>
                <div class="section-content">
                    <div class="servisors-list">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM servisor_details ORDER BY created_at DESC LIMIT 20");
                        $stmt->execute();
                        $servisors = $stmt->get_result();
                        
                        if ($servisors->num_rows > 0) {
                            while ($servisor = $servisors->fetch_assoc()) {
                                echo '<div class="servisor-item">';
                                echo '<div class="servisor-info">';
                                
                                if (!empty($servisor['profile_image'])) {
                                    echo '<img src="../' . htmlspecialchars($servisor['profile_image']) . '" class="servisor-avatar" alt="">';
                                } else {
                                    echo '<div class="servisor-avatar"><i class="fa fa-user"></i></div>';
                                }
                                
                                echo '<div class="servisor-details">';
                                echo '<h4>' . htmlspecialchars($servisor['name']) . '</h4>';
                                echo '<div class="servisor-meta">';
                                echo htmlspecialchars($servisor['service_category']) . ' • ' . htmlspecialchars($servisor['area']);
                                echo ' • ' . formatCurrency($servisor['base_fee']);
                                if ($servisor['rating'] > 0) {
                                    echo ' • ' . number_format($servisor['rating'], 1) . '★';
                                }
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="servisor-actions">';
                                echo '<form method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this servisor?\')">';
                                echo '<input type="hidden" name="delete_servisor_id" value="' . $servisor['id'] . '">';
                                echo '<button type="submit" class="btn btn-danger">';
                                echo '<i class="fa fa-trash"></i> Delete';
                                echo '</button>';
                                echo '</form>';
                                echo '</div>';
                                
                                echo '</div>';
                            }
                        } else {
                            echo '<div style="text-align: center; padding: 2rem; color: #666;">';
                            echo '<i class="fa fa-users fa-3x" style="margin-bottom: 1rem; opacity: 0.3;"></i>';
                            echo '<h3>No Servisors Found</h3>';
                            echo '<p>Add some servisors to get started.</p>';
                            echo '</div>';
                        }
                        $stmt->close();
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Bookings Section -->
            <div id="bookings-section" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fa fa-calendar-alt"></i>
                        Recent Bookings
                    </h2>
                </div>
                <div class="section-content">
                    <div class="bookings-list">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM booking_details ORDER BY created_at DESC LIMIT 20");
                        $stmt->execute();
                        $bookings = $stmt->get_result();
                        
                        if ($bookings->num_rows > 0) {
                            echo '<div style="overflow-x: auto;">';
                            echo '<table style="width: 100%; border-collapse: collapse;">';
                            echo '<thead>';
                            echo '<tr style="background: #f8f9fa;">';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Booking #</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Customer</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Servisor</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Service</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Date</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Status</th>';
                            echo '<th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e9ecef;">Amount</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            while ($booking = $bookings->fetch_assoc()) {
                                echo '<tr style="border-bottom: 1px solid #e9ecef;">';
                                echo '<td style="padding: 1rem;">' . htmlspecialchars($booking['booking_number']) . '</td>';
                                echo '<td style="padding: 1rem;">' . htmlspecialchars($booking['customer_name']) . '</td>';
                                echo '<td style="padding: 1rem;">' . htmlspecialchars($booking['servisor_name']) . '</td>';
                                echo '<td style="padding: 1rem;">' . htmlspecialchars($booking['service_category']) . '</td>';
                                echo '<td style="padding: 1rem;">' . date('M j, Y', strtotime($booking['booking_date'])) . '</td>';
                                echo '<td style="padding: 1rem;">';
                                echo '<span style="background: ' . $booking['status_color'] . '; color: white; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.8rem;">';
                                echo htmlspecialchars($booking['status']);
                                echo '</span>';
                                echo '</td>';
                                echo '<td style="padding: 1rem;">' . formatCurrency($booking['estimated_cost'] ?? 0) . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                        } else {
                            echo '<div style="text-align: center; padding: 2rem; color: #666;">';
                            echo '<i class="fa fa-calendar-times fa-3x" style="margin-bottom: 1rem; opacity: 0.3;"></i>';
                            echo '<h3>No Bookings Found</h3>';
                            echo '<p>Bookings will appear here once customers start booking services.</p>';
                            echo '</div>';
                        }
                        $stmt->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.admin-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            event.target.classList.add('active');
        }
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>