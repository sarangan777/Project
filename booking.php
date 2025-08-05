<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
    <h2>Book a Service</h2>
    <?php
    $success = false;
    $message = '';
    $servisor_id = isset($_GET['servisor_id']) ? intval($_GET['servisor_id']) : 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_name = trim($_POST['name']);
        $customer_phone = trim($_POST['phone']);
        $customer_address = trim($_POST['address']);
        $booking_date = $_POST['date'];
        $booking_time = $_POST['time'];
        $message_text = trim($_POST['message']);
        $selected_servisor_id = intval($_POST['servisor']);
        
        // Get user_id if user is logged in (you can implement session management)
        $user_id = null; // For now, we'll set this to null
        
        $stmt = $conn->prepare('INSERT INTO bookings (user_id, servisor_id, customer_name, customer_phone, customer_address, booking_date, booking_time, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iissssss', $user_id, $selected_servisor_id, $customer_name, $customer_phone, $customer_address, $booking_date, $booking_time, $message_text);
        
        if ($stmt->execute()) {
            $success = true;
            $message = 'Booking submitted successfully! We will contact you soon.';
            $redirect_id = $selected_servisor_id;
        } else {
            $message = 'Booking failed. Please try again.';
        }
        $stmt->close();
    }
    
    if ($success) {
        echo '<div class="card" style="color:green;">' . $message . '</div>';
        if (isset($redirect_id) && $redirect_id) {
            echo '<script>setTimeout(function(){ window.location.href = "add_card.php?servisor_id=' . $redirect_id . '"; }, 2000);</script>';
        }
    } else {
        if ($message) {
            echo '<div class="card" style="color:red;">' . $message . '</div>';
        }
    ?>
    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="form">
        <label for="name">Your Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>
        <label for="address">Address:</label>
        <input type="text" id="address" name="address" required>
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>
        <label for="time">Time:</label>
        <input type="time" id="time" name="time" required>
        <label for="servisor">Select Servisor:</label>
        <select id="servisor" name="servisor" required>
            <option value="">Select</option>
            <?php
            $servisors = $conn->query('SELECT id, name, service_type FROM servisors WHERE is_approved = 1');
            while ($row = $servisors->fetch_assoc()) {
                $selected = ($servisor_id && $servisor_id == $row['id']) ? 'selected' : '';
                echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name'] . ' (' . $row['service_type'] . ')') . '</option>';
            }
            ?>
        </select>
        <label for="message">Message:</label>
        <textarea id="message" name="message"></textarea>
        <button type="submit" class="btn">Book Now</button>
    </form>
    <?php } ?>
</main>
<?php include 'includes/footer.php'; ?> 