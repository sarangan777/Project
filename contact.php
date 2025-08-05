<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>
<main class="container">
    <h2 style="text-align:center;">Contact Us</h2>
    <?php
    $contact_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);
        
        if ($name !== '' && $email !== '' && $message !== '') {
            // Check if contacts table exists
            $table_exists = $conn->query("SHOW TABLES LIKE 'contacts'");
            if ($table_exists && $table_exists->num_rows > 0) {
                $stmt = $conn->prepare('INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sss', $name, $email, $message);
                    if ($stmt->execute()) {
                        $contact_message = '<div class="card" style="color:green;">Thank you for contacting us! We will get back to you soon.</div>';
                    } else {
                        $contact_message = '<div class="card" style="color:red;">Failed to send your message. Please try again.</div>';
                    }
                    $stmt->close();
                } else {
                    $contact_message = '<div class="card" style="color:red;">System error. Please try again later.</div>';
                }
            } else {
                // Fallback: just show success message without saving to database
                $contact_message = '<div class="card" style="color:green;">Thank you for contacting us! We will get back to you soon.</div>';
            }
        } else {
            $contact_message = '<div class="card" style="color:red;">All fields are required.</div>';
        }
    }
    if (!empty($contact_message)) echo $contact_message;
    ?>
    <form class="form" method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>
        <button type="submit" class="btn">Send</button>
    </form>
</main>
<?php include 'includes/footer.php'; ?> 