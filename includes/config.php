<?php
// Configuration file for Jaffna Services
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'service');

// Site configuration
define('SITE_NAME', 'Jaffna Services');
define('SITE_URL', 'http://localhost/service');
define('ADMIN_EMAIL', 'admin@jaffnaservices.com');

// Payment configuration
define('ENABLE_ONLINE_PAYMENT', true);
define('ENABLE_COD', true);
define('DEFAULT_CURRENCY', 'LKR');

// File upload configuration
define('UPLOAD_DIR', 'assets/img/servisors/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Pagination
define('ITEMS_PER_PAGE', 10);

// Email settings (configure as needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@jaffnaservices.com');
define('FROM_NAME', 'Jaffna Services');
?>