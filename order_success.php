<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? 'Account';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Fetch order details
    $orderStmt = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $orderStmt->bind_param("i", $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        header('Location: index.php');
        exit;
    }

    // Fetch ordered food items
    $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

} catch (Exception $e) {
    error_log("Receipt error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | BitesNow</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css">
    <style>
        .receipt-card {
            max-width: 650px;
            margin: 40px auto;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 30px;
            text-align: left;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h1 { color: #7CFC00; margin: 0 0 8px 0; }
        .order-tag { font-size: 0.95rem; color: #d1d5db; }
        .receipt-items { list-style: none; padding: 0; margin: 0 0 20px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .receipt-items li { display: flex; justify-content: space-between; padding: 10px 0; color: #f3f4f6; }
        .receipt-total { display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: bold; color: #7CFC00; margin-bottom: 24px; }
        .delivery-summary { background: rgba(255, 255, 255, 0.05); padding: 16px; border-radius: 8px; margin-bottom: 25px; font-size: 0.9rem; line-height: 1.6; }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="images/BitesNow.png" alt="BitesNow">
                <span>BitesNow</span>
            </a>
            <nav class="site-nav">
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="food.php">Menu</a></li>
                    <li><a href="my_orders.php">My Orders</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="receipt-card">
            <div class="receipt-header">
                <h1>✓ Order Confirmed!</h1>
                <p class="order-tag">Order #<?= htmlspecialchars($order['id']) ?> · <?= htmlspecialchars(date('d M Y, h:i A', strtotime($order['created_at']))) ?></p>
            </div>

            <h3>Ordered Items</h3>
            <ul class="receipt-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['quantity']) ?> × <?= htmlspecialchars($item['item_name']) ?></span>
                        <span>RM <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="receipt-total">
                <span>Total Amount</span>
                <span>RM <?= number_format($order['total_amount'], 2) ?></span>
            </div>

            <div class="delivery-summary">
                <strong>Delivery & Payment Details:</strong><br>
                Recipient: <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['phone']) ?>)<br>
                <?= nl2br(htmlspecialchars($order['address'])) ?>
            </div>

            <div style="text-align: center;">
                <a href="index.php" class="cta-btn">Back to Home</a>
            </div>
        </section>
    </main>

    <script>
        // Clear the cart on successful order confirmation
        localStorage.removeItem('bitesnowCart');
    </script>
</body>
</html>