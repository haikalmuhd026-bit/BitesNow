<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 2. Sanitize redirect destination
$rawRedirect = basename(trim($_POST['redirect'] ?? ''));
$allowedRedirects = ['index.php', 'food.php', 'recom.php', 'checkout.php', 'my_orders.php', 'cart.php', 'product_review.php', 'admin_orders.php'];
$redirect = in_array($rawRedirect, $allowedRedirects, true) ? $rawRedirect : '';
$redirectParam = !empty($redirect) ? '&redirect=' . urlencode($redirect) : '';

// 3. Verify CSRF token
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: login.php?status=csrf' . $redirectParam);
    exit;
}

// 4. Retrieve form inputs
$username = trim($_POST['user'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: login.php?status=invalid' . $redirectParam);
    exit;
}

// 5. Database Configuration & Lookup
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Detect optional columns (email, role) so query never fails on minimal schemas
    $cols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM `users`");
    while ($c = $colRes->fetch_assoc()) {
        $cols[] = $c['Field'];
    }
    $selectEmail = in_array('email', $cols, true) ? 'email' : "'' AS email";
    $selectRole  = in_array('role', $cols, true) ? "IFNULL(role, 'customer') AS role" : "'customer' AS role";

    $stmt = $conn->prepare("SELECT id, username, password, {$selectEmail}, {$selectRole} FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($row) {
        // Supports both bcrypt hashed passwords and legacy plaintext dev accounts
        $isValid = password_verify($password, $row['password']) || hash_equals((string)$row['password'], $password);

        if ($isValid) {
            session_regenerate_id(true);

            $_SESSION['user_id']    = (int)$row['id'];
            $_SESSION['user']       = $row['username'];
            $_SESSION['user_name']  = $row['username'];
            $_SESSION['user_email'] = $row['email'] ?? '';
            $_SESSION['role']       = strtolower($row['role'] ?? 'customer');

            unset($_SESSION['csrf_token']);

            if ($_SESSION['role'] === 'admin') {
                header('Location: admin_orders.php');
            } elseif (!empty($redirect)) {
                header("Location: $redirect");
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }

    // Authentication failed
    header('Location: login.php?status=invalid' . $redirectParam);
    exit;

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: login.php?status=error' . $redirectParam);
    exit;
}