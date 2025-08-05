<?php include 'includes/header.php'; ?>

<main class="container">
    <h2>Servisor Login</h2>
    <form action="servisor_login.php" method="POST" class="form">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn">Login</button>
        <p>Don't have an account? <a href="servisor_signup.php">Sign up</a></p>
    </form>
</main>

<?php include 'includes/footer.php'; ?> 