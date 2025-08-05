<?php include '../includes/header.php'; ?>
<?php include '../includes/db.php'; ?>
<main class="container">
    <h2>Add New Servisor</h2>
    <?php
    $servisor_message = '';
    $new_servisor_id = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['servisor_name'])) {
        $name = trim($_POST['servisor_name']);
        $service_type = trim($_POST['servisor_service_type']);
        $phone = trim($_POST['servisor_phone']);
        $email = trim($_POST['servisor_email']);
        $area = trim($_POST['servisor_area']);
        $fee = trim($_POST['servisor_fee']);
        $rating = trim($_POST['servisor_rating']);
        $photo = trim($_POST['servisor_photo']);
        if ($name !== '' && $service_type !== '') {
            $stmt = $conn->prepare('INSERT INTO servisors (name, service_type, phone, email, area, fee, rating, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssds', $name, $service_type, $phone, $email, $area, $fee, $rating, $photo);
            if ($stmt->execute()) {
                $new_servisor_id = $stmt->insert_id;
                // Modal popup markup
                echo '<div id="successModal" class="modal" style="display:block;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);">';
                echo '<div style="background:#fff;padding:2rem 2.5rem;border-radius:1rem;max-width:400px;margin:10vh auto;box-shadow:0 8px 32px rgba(37,99,235,0.10);text-align:center;">';
                echo '<div style="font-size:2.5rem;color:green;"><i class="fa fa-check-circle"></i></div>';
                echo '<h3 style="margin:1rem 0 0.5rem 0;">Servisor Added Successfully!</h3>';
                echo '<p>You will be redirected to the dashboard shortly.</p>';
                echo '</div></div>';
                echo '<script>setTimeout(function(){ window.location.href = "dashboard.php"; }, 2000);</script>';
            } else {
                $servisor_message = '<div class="card" style="color:red;">Failed to add servisor.</div>';
            }
            $stmt->close();
        } else {
            $servisor_message = '<div class="card" style="color:red;">Name and Service Type are required.</div>';
        }
    }
    if (!empty($servisor_message)) echo $servisor_message;
    ?>
    <form method="POST" class="form" style="max-width:500px;">
        <label for="servisor_name">Name:</label>
        <input type="text" id="servisor_name" name="servisor_name" required>
        <label for="servisor_service_type">Service Type:</label>
        <input type="text" id="servisor_service_type" name="servisor_service_type" required>
        <label for="servisor_phone">Phone:</label>
        <input type="text" id="servisor_phone" name="servisor_phone">
        <label for="servisor_email">Email:</label>
        <input type="email" id="servisor_email" name="servisor_email">
        <label for="servisor_area">Area:</label>
        <input type="text" id="servisor_area" name="servisor_area">
        <label for="servisor_fee">Fee:</label>
        <input type="text" id="servisor_fee" name="servisor_fee">
        <label for="servisor_rating">Rating:</label>
        <input type="number" step="0.1" min="0" max="5" id="servisor_rating" name="servisor_rating">
        <label for="servisor_photo">Photo URL:</label>
        <input type="text" id="servisor_photo" name="servisor_photo" placeholder="https://...">
        <button type="submit" class="btn">Add Servisor</button>
    </form>
    <hr>
    <h3>All Servisors</h3>
    <div style="overflow-x:auto;">
    <table style="width:100%;max-width:900px;margin:1.5rem auto;border-collapse:collapse;">
        <thead>
            <tr style="background:#e3f0ff;color:#007BFF;">
                <th style="padding:0.7rem 0.5rem;">Name</th>
                <th style="padding:0.7rem 0.5rem;">Service Type</th>
                <th style="padding:0.7rem 0.5rem;">Profile</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query('SELECT id, name, service_type FROM servisors ORDER BY id DESC');
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr style="background:#fff;border-bottom:1px solid #e3f0ff;">';
                echo '<td style="padding:0.6rem 0.5rem;">' . htmlspecialchars($row['name']) . '</td>';
                echo '<td style="padding:0.6rem 0.5rem;">' . htmlspecialchars($row['service_type']) . '</td>';
                echo '<td style="padding:0.6rem 0.5rem;"><a href="../servisor_profile.php?id=' . $row['id'] . '" class="btn btn-secondary" style="padding:0.3rem 1.1rem;font-size:0.95em;">View</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">No servisors found.</td></tr>';
        }
        ?>
        </tbody>
    </table>
    </div>
</main>
<?php include '../includes/footer.php'; ?> 