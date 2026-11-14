<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: location.php');
    exit;
}

// 2. Strict CSRF Token Verification
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: location.php?status=error');
    exit;
}

// 3. Extract and Validate Inputs
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: location.php?status=error');
    exit;
}

// 4. Database Connection & Save Inquiry
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Ensure table exists so admin_orders.php can display inquiries immediately
    $conn->query("
        CREATE TABLE IF NOT EXISTS `inquiries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `message` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $conn->prepare("INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    unset($_SESSION['csrf_token']);

    header('Location: location.php?status=success');
    exit;

} catch (Exception $e) {
    error_log("Contact Form DB Error: " . $e->getMessage());
    header('Location: location.php?status=error');
    exit;
}