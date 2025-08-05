<?php
session_start();

// Clear servisor session
unset($_SESSION['servisor_id']);
unset($_SESSION['servisor_name']);

// Destroy session if no other session variables exist
if (empty($_SESSION)) {
    session_destroy();
}

header('Location: servisor_login.php');
exit();
?>