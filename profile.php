<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    if (!empty($name) && !empty($phone)) {
        $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $message = '<div class="message success">Profile updated successfully!</div>';
        } else {
            $message = '<div class="message error">Failed to update profile.</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="message error">Name and phone are required.</div>';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = '<div class="message error">New passwords do not match.</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="message error">New password must be at least 6 characters long.</div>';
    } else {
        // Verify current password
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="message success">Password changed successfully!</div>';
            } else {
                $message = '<div class="message error">Failed to change password.</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="message error">Current password is incorrect.</div>';
        }
    }
}

// Fetch user data
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's booking statistics
$stmt = $conn->prepare('SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$booking_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<main class="container">
    <div class="profile-container">
        <?php if ($message) echo $message; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fa fa-user"></i>
            </div>
            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
            <p><?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1 ? 'Administrator' : 'Customer'; ?></p>
        </div>
        
        <div class="profile-content">
            <!-- Profile Information -->
            <div class="profile-section">
                <h3><i class="fa fa-user"></i> Profile Information</h3>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="profile-info">
                        <div class="info-item">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Email Address</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Member Since</label>
                            <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem;">
                        <label>Address</label>
                        <textarea name="address" rows="3" class="form-control" placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button type="submit" class="btn">
                            <i class="fa fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Statistics -->
            <div class="profile-section">
                <h3><i class="fa fa-chart-bar"></i> Account Statistics</h3>
                <div class="profile-info">
                    <div class="info-item">
                        <label>Total Bookings</label>
                        <span><?php echo $booking_stats['total_bookings']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Status</label>
                        <span style="color: #28a745; font-weight: 600;">Active</span>
                    </div>
                    <div class="info-item">
                        <label>Account Type</label>
                        <span><?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1 ? 'Administrator' : 'Customer'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="profile-section">
                <h3><i class="fa fa-lock"></i> Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="profile-info">
                        <div class="info-item">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required class="form-control">
                        </div>
                        <div class="info-item">
                            <label>New Password</label>
                            <input type="password" name="new_password" required class="form-control" minlength="6">
                        </div>
                        <div class="info-item">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="form-control" minlength="6">
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button type="submit" class="btn">
                            <i class="fa fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Quick Actions -->
            <div class="profile-section">
                <h3><i class="fa fa-bolt"></i> Quick Actions</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                    <a href="services.php" class="btn">
                        <i class="fa fa-search"></i> Browse Services
                    </a>
                    <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1): ?>
                    <a href="my_bookings.php" class="btn btn-secondary">
                        <i class="fa fa-calendar"></i> My Bookings
                    </a>
                    <?php endif; ?>
                    <a href="contact.php" class="btn btn-secondary">
                        <i class="fa fa-envelope"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.form-control {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #e3f0ff;
    border-radius: 0.5rem;
    font-size: 1rem;
    background: #fff;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #007BFF;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.info-item input.form-control,
.info-item textarea.form-control {
    margin-top: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>