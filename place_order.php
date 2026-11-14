<?php
session_start();

// 1. Enforce POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

// 2. Enforce authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: login.php?status=login_required');
    exit;
}

// 3. CSRF token validation
if (!empty($_SESSION['csrf_token'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: checkout.php?status=failed');
        exit;
    }
}

// 4. Database Connection
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("Connection failed: " . $e->getMessage());
    header('Location: checkout.php?status=failed');
    exit;
}

// 5. Sanitize customer inputs
$userId        = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$customerName  = trim($_POST['fullname'] ?? '');
$email         = trim($_POST['email'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$address       = trim($_POST['address'] ?? '');
$city          = trim($_POST['city'] ?? '');
$state         = trim($_POST['state'] ?? '');
$postcode      = trim($_POST['postcode'] ?? '');
$notes         = trim($_POST['notes'] ?? '');
$paymentMethod = ($_POST['payment_method'] ?? 'card') === 'cod' ? 'Cash on Delivery (COD)' : 'Paid by Card';

if (empty($customerName) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($postcode)) {
    header('Location: checkout.php?status=failed');
    exit;
}

// Combine delivery parameters and payment info into the single address field
$fullAddress = trim("$address, $city, $state $postcode [Payment: $paymentMethod]" . (!empty($notes) ? " (Notes: $notes)" : ""));

// 6. Decode and validate cart items
$rawCart = $_POST['order'] ?? '';
$cart = json_decode($rawCart, true);

if (empty($cart) || !is_array($cart)) {
    header('Location: checkout.php?status=empty');
    exit;
}

// Compute total
$totalAmount = 0.00;
foreach ($cart as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0.00;
    $qty   = isset($item['qty']) ? (int)$item['qty'] : 0;
    if ($price > 0 && $qty > 0) {
        $totalAmount += ($price * $qty);
    }
}

if ($totalAmount <= 0) {
    header('Location: checkout.php?status=empty');
    exit;
}

// 7. Insert order using transaction
try {
    $conn->begin_transaction();

    // Insert into orders using your exact schema columns
    $orderSql = "INSERT INTO orders (user_id, customer_name, phone, address, total_amount, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($orderSql);
    $stmt->bind_param("isssd", $userId, $customerName, $phone, $fullAddress, $totalAmount);
    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();

    // Insert items into order_items
    $itemSql = "INSERT INTO order_items (order_id, item_name, price, quantity) VALUES (?, ?, ?, ?)";
    $itemStmt = $conn->prepare($itemSql);

    foreach ($cart as $item) {
        $name  = trim($item['name'] ?? 'Dish Item');
        $price = (float)($item['price'] ?? 0.00);
        $qty   = (int)($item['qty'] ?? 1);

        if ($qty > 0 && $price >= 0) {
            $itemStmt->bind_param("isdi", $orderId, $name, $price, $qty);
            $itemStmt->execute();
        }
    }
    $itemStmt->close();

    $conn->commit();

    // Reset CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: order_success.php?order_id=" . urlencode($orderId));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order failed: " . $e->getMessage());
    header('Location: checkout.php?status=failed');
    exit;
}