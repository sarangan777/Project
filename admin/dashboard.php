<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

include '../includes/header.php';
include '../includes/db.php';

// Get dashboard statistics
$stats = [];

// Total servisors
$result = $conn->query("SELECT COUNT(*) as count FROM servisors WHERE is_approved = 1");
$stats['total_servisors'] = $result->fetch_assoc()['count'];

// Total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Today's bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE()");
$stats['todays_bookings'] = $result->fetch_assoc()['count'];

// Pending approvals
$result = $conn->query("SELECT COUNT(*) as count FROM servisors WHERE is_approved = 0");
$stats['pending_approvals'] = $result->fetch_assoc()['count'];

// Revenue this month (completed bookings)
$result = $conn->query("SELECT COALESCE(SUM(final_cost), 0) as revenue FROM bookings WHERE status_id = 4 AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['monthly_revenue'] = $result->fetch_assoc()['revenue'];

// Handle Add Servisor form submission
$servisor_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_servisor'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $category_id = intval($_POST['category_id']);
    $area_id = intval($_POST['area_id']);
    $fee = floatval($_POST['fee']);
    $experience = intval($_POST['experience']);
    $description = sanitizeInput($_POST['description']);
    $password = password_hash('servisor123', PASSWORD_DEFAULT); // Default password
    
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
            $servisor_message = '<div class="card" style="color:red;background:#ffebee;margin-bottom:1rem;">Email already exists.</div>';
        } else {
            $stmt = $conn->prepare('INSERT INTO servisors (name, email, phone, password, service_category_id, area_id, base_fee, experience_years, description, profile_image, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
            $stmt->bind_param('ssssiiidss', $name, $email, $phone, $password, $category_id, $area_id, $fee, $experience, $description, $photo_path);
        }
        if ($stmt->execute()) {
            // Log admin activity
            logAdminActivity($conn, $_SESSION['user_id'], 'Added servisor', 'servisors', $stmt->insert_id, null, [
                'name' => $name,
                'email' => $email,
                'service_category_id' => $category_id,
                'area_id' => $area_id
            ]);
            
            $servisor_message = '<div class="card" style="color:green;background:#e8f5e9;margin-bottom:1rem;">Servisor added successfully!</div>';
        } else {
            $servisor_message = '<div class="card" style="color:red;background:#ffebee;margin-bottom:1rem;">Failed to add servisor.</div>';
        }
        $stmt->close();
    } else {
        $servisor_message = '<div class="card" style="color:red;background:#ffebee;margin-bottom:1rem;">All required fields must be filled.</div>';
    }
}

// Handle Delete Servisor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_servisor_id'])) {
    $servisor_id = intval($_POST['delete_servisor_id']);
    
    // Get servisor details before deletion for logging
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
            $servisor_message = '<div class="card" style="color:green;background:#e8f5e9;margin-bottom:1rem;">Servisor deleted successfully!</div>';
        } else {
            $servisor_message = '<div class="card" style="color:red;background:#ffebee;margin-bottom:1rem;">Failed to delete servisor.</div>';
        }
        $stmt->close();
    }
}

// Get service categories and areas for form
$service_categories = getServiceCategories($conn);
$areas = getAreas($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK SERVICES - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #e0e7ef url('https://www.transparenttextures.com/patterns/cubes.png');
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
        }
        .admin-sidebar {
            background: linear-gradient(180deg, #2563eb 0%, #1e40af 100%);
            color: #fff;
            min-height: 100vh;
            width: 240px;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .admin-sidebar .sidebar-title {
            font-size: 1.4rem;
            font-weight: 700;
            padding: 2rem 1.5rem 1rem 1.5rem;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .admin-badge {
            background: #fff;
            color: #2563eb;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 0.7rem;
            padding: 0.2rem 0.8rem;
            margin-left: 0.7rem;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            letter-spacing: 1px;
        }
        .admin-sidebar ul {
            list-style: none;
            padding: 0 1.5rem;
            margin: 0;
            flex: 1;
        }
        .admin-sidebar ul li {
            margin-bottom: 1.2rem;
        }
        .admin-sidebar ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 1.08rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.6rem 1rem;
            border-radius: 0.7rem;
            transition: background 0.18s;
        }
        .admin-sidebar ul li a.active, .admin-sidebar ul li a:hover {
            background: rgba(255,255,255,0.13);
        }
        .admin-sidebar .logout-btn {
            margin: 2rem 1.5rem 1.5rem 1.5rem;
            background: #fff;
            color: #2563eb;
            border: none;
            border-radius: 0.7rem;
            font-weight: 600;
            padding: 0.7rem 1.2rem;
            width: 100%;
            transition: background 0.18s, color 0.18s;
        }
        .admin-sidebar .logout-btn:hover {
            background: #2563eb;
            color: #fff;
        }
        .admin-main {
            margin-left: 240px;
            padding: 0;
            min-height: 100vh;
            position: relative;
        }
        .admin-main-boxed {
            max-width: 1100px;
            margin: 2.5rem auto 2.5rem auto;
            background: rgba(255,255,255,0.98);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10);
            padding: 2.5rem 2.5rem 2.5rem 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .admin-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-20deg);
            font-size: 7rem;
            color: #2563eb10;
            font-weight: 900;
            pointer-events: none;
            user-select: none;
            z-index: 0;
        }
        .admin-topbar {
            background: #fff;
            box-shadow: 0 2px 8px rgba(37,99,235,0.06);
            padding: 1.1rem 2.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .admin-topbar .panel-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .admin-topbar .admin-badge {
            margin-left: 1rem;
        }
        .admin-topbar .profile-icon {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #e0e7ef;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            color: #2563eb;
        }
        .dashboard-cards {
            display: flex;
            gap: 2rem;
            margin: 2.5rem 0 2rem 0;
            flex-wrap: wrap;
            z-index: 1;
            position: relative;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 16px rgba(37,99,235,0.08);
            padding: 2rem 2.2rem 1.5rem 2.2rem;
            min-width: 220px;
            flex: 1 1 220px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .dashboard-card .card-title {
            font-size: 1.1rem;
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .dashboard-card .card-value {
            font-size: 2.1rem;
            font-weight: 700;
            color: #1e40af;
        }
        .dashboard-card .card-icon {
            font-size: 2rem;
            color: #2563eb;
            margin-bottom: 0.7rem;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2563eb;
            margin: 2.5rem 0 1.2rem 0;
        }
        .add-servisor-form, .delete-servisor-section {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 16px rgba(37,99,235,0.08);
            padding: 2rem 2.2rem 1.5rem 2.2rem;
            margin-bottom: 2rem;
            z-index: 1;
            position: relative;
        }
        .form-label {
            font-weight: 500;
            color: #2563eb;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.08);
        }
        .servisor-list {
            margin-top: 1.5rem;
        }
        .servisor-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e7ef;
        }
        .servisor-item:last-child { border-bottom: none; }
        .servisor-info {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        .servisor-photo {
            width: 48px; height: 48px;
            border-radius: 50%;
            object-fit: cover;
            background: #e0e7ef;
        }
        .servisor-name {
            font-weight: 600;
            color: #1e40af;
        }
        .delete-btn {
            background: #e11d48;
            color: #fff;
            border: none;
            border-radius: 0.7rem;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            transition: background 0.18s;
        }
        .delete-btn:hover {
            background: #be123c;
        }
        @media (max-width: 900px) {
            .admin-main { margin-left: 0; }
            .admin-sidebar { position: static; width: 100%; min-height: unset; flex-direction: row; align-items: center; justify-content: space-between; border-radius: 0; }
            .admin-sidebar ul { flex-direction: row; gap: 1.2rem; padding: 0; }
            .admin-sidebar .sidebar-title { padding: 1rem; margin-bottom: 0; }
        }
        @media (max-width: 600px) {
            .dashboard-cards { flex-direction: column; gap: 1rem; }
            .admin-main-boxed { padding: 0.5rem; }
            .add-servisor-form, .delete-servisor-section { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="sidebar-title">VK SERVICES <span class="admin-badge">ADMIN PANEL</span></div>
        <ul>
            <li><a href="#" class="active"><i class="fa fa-home"></i> Dashboard</a></li>
            <li><a href="#add-servisor"><i class="fa fa-user-plus"></i> Add Servisor</a></li>
            <li><a href="#delete-servisor"><i class="fa fa-user-minus"></i> Delete Servisor</a></li>
        </ul>
        <a class="logout-btn" href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="admin-main">
        <div class="admin-main-boxed">
            <div class="admin-watermark">ADMIN</div>
            <div class="admin-topbar">
                <div class="panel-title">VK SERVICES - Admin Panel <span class="admin-badge">ADMIN</span></div>
                <div class="profile-icon"><i class="fa fa-user-circle"></i></div>
            </div>
            <div class="container-fluid">
                <!-- Dashboard Stats -->
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-icon"><i class="fa fa-users"></i></div>
                        <div class="card-title">Total Servisors</div>
                        <div class="card-value"><?php echo $stats['total_servisors']; ?></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-icon"><i class="fa fa-calendar"></i></div>
                        <div class="card-title">Total Bookings</div>
                        <div class="card-value"><?php echo $stats['total_bookings']; ?></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-icon"><i class="fa fa-calendar-check"></i></div>
                        <div class="card-title">Today's Bookings</div>
                        <div class="card-value"><?php echo $stats['todays_bookings']; ?></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-icon"><i class="fa fa-clock"></i></div>
                        <div class="card-title">Pending Approvals</div>
                        <div class="card-value"><?php echo $stats['pending_approvals']; ?></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-icon"><i class="fa fa-money-bill"></i></div>
                        <div class="card-title">Monthly Revenue</div>
                        <div class="card-value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
                    </div>
                </div>
                
                <!-- Add Servisor Form -->
                <div class="add-servisor-form" id="add-servisor">
                    <div class="section-title"><i class="fa fa-user-plus"></i> Add Servisor</div>
                    <?php if (!empty($servisor_message)) echo $servisor_message; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_servisor" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Service Category</label>
                                <select class="form-control" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($service_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Area</label>
                                <select class="form-control" name="area_id" required>
                                    <option value="">Select Area</option>
                                    <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" required placeholder="077XXXXXXX">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Base Fee (LKR)</label>
                                <input type="number" class="form-control" name="fee" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" class="form-control" name="experience" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary" style="background:#2563eb;border:none;">Add Servisor</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Delete Servisor Section -->
                <div class="delete-servisor-section" id="delete-servisor">
                    <div class="section-title"><i class="fa fa-user-minus"></i> Delete Servisor</div>
                    <input type="text" class="form-control" id="searchServisor" placeholder="Search Servisor by name or city...">
                    <div class="servisor-list" id="servisorList">
<?php
$result = $conn->query('SELECT sd.* FROM servisor_details sd ORDER BY sd.id DESC LIMIT 10');
if ($result && $result->num_rows > 0) {
    while ($servisor = $result->fetch_assoc()) {
        echo '<div class="servisor-item">';
        echo '<div class="servisor-info">';
        if (!empty($servisor['profile_image'])) {
            echo '<img src="../' . htmlspecialchars($servisor['profile_image']) . '" class="servisor-photo" alt="">';
        } else {
            echo '<div class="servisor-photo" style="display:flex;align-items:center;justify-content:center;background:#e0e7ef;"><i class="fa fa-user"></i></div>';
        }
        echo '<div>';
        echo '<div class="servisor-name">' . htmlspecialchars($servisor['name']) . '</div>';
        echo '<div style="font-size:0.97em;color:#2563eb;">' . htmlspecialchars($servisor['service_category']) . ', ' . htmlspecialchars($servisor['area']) . '</div>';
        echo '<div style="font-size:0.9em;color:#666;">Fee: ' . formatCurrency($servisor['base_fee']) . '</div>';
        echo '</div></div>';
        echo '<form method="POST" style="margin:0;">';
        echo '<input type="hidden" name="delete_servisor_id" value="' . $servisor['id'] . '">';
        echo '<button type="submit" class="delete-btn" onclick="return confirm(\'Are you sure you want to delete this servisor?\')"><i class="fa fa-trash"></i> Delete</button>';
        echo '</form>';
        echo '</div>';
    }
} else {
    echo '<div>No servisors found.</div>';
}
?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Sidebar navigation active state
    document.querySelectorAll('.admin-sidebar ul li a').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('.admin-sidebar ul li a').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
    // Search/filter servisors
    document.getElementById('searchServisor').addEventListener('input', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.servisor-item').forEach(item => {
            const name = item.querySelector('.servisor-name').textContent.toLowerCase();
            const city = item.querySelector('.servisor-info div div:last-child').textContent.toLowerCase();
            item.style.display = (name.includes(val) || city.includes(val)) ? '' : 'none';
        });
    });
    // Delete servisor (demo only)
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            return confirm('Are you sure you want to delete this servisor? This action cannot be undone.');
        });
    });
    </script>
</body>
</html> 