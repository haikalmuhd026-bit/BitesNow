<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict Admin Gatekeeper: Only users with role === 'admin' may access this dashboard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php?status=admin_required');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Generate CSRF token for secure admin actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Handle Admin POST Actions (Status Update & Clearing Completed Orders)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: admin_orders.php?error=csrf');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $orderId   = intval($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];

        if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin_orders.php?updated=1');
        exit;
    }

    if ($action === 'clear_order') {
        $orderId = intval($_POST['order_id'] ?? 0);
        if ($orderId > 0) {
            // Clean up child items if order_items table exists
            $itemsTbl = $conn->query("SHOW TABLES LIKE 'order_items'");
            if ($itemsTbl && $itemsTbl->num_rows > 0) {
                $delItems = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                $delItems->bind_param("i", $orderId);
                $delItems->execute();
                $delItems->close();
            }

            // Only allow clearing orders that are already delivered or cancelled
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND status IN ('delivered', 'cancelled')");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin_orders.php?cleared=1');
        exit;
    }

    if ($action === 'clear_delivered') {
        $itemsTbl = $conn->query("SHOW TABLES LIKE 'order_items'");
        if ($itemsTbl && $itemsTbl->num_rows > 0) {
            $conn->query("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE o.status IN ('delivered', 'cancelled')");
        }

        $conn->query("DELETE FROM orders WHERE status IN ('delivered', 'cancelled')");
        header('Location: admin_orders.php?cleared=1');
        exit;
    }
}

// Fetch all orders (active orders first, then completed, newest first)
$orders = [];
$completedCount = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'orders'");
if ($tableCheck->num_rows > 0) {
    $result = $conn->query("SELECT * FROM orders ORDER BY FIELD(status, 'pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'), created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
        if (in_array(strtolower($row['status'] ?? ''), ['delivered', 'cancelled'], true)) {
            $completedCount++;
        }
    }
}

// Fetch recent customer inquiries if table exists
$inquiries = [];
$inqCheck = $conn->query("SHOW TABLES LIKE 'inquiries'");
if ($inqCheck->num_rows > 0) {
    $inqResult = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
    while ($iRow = $inqResult->fetch_assoc()) {
        $inquiries[] = $iRow;
    }
}
$conn->close();

$paymentLabels = [
    'cod'  => '💵 COD (Collect Cash)',
    'qr'   => '📱 DuitNow / TnG QR',
    'card' => '💳 Credit / Debit Card',
    'fpx'  => '🏦 Online Banking (FPX)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Queue &amp; Orders | BitesNow Admin</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <style>
        .admin-wrap {
            max-width: 1180px;
            margin: 30px auto 60px;
            padding: 0 20px;
            text-align: left;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 18px;
        }
        .admin-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .card-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 8px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 0.92rem;
        }
        .admin-table th, .admin-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: left;
            vertical-align: top;
        }
        .admin-table th {
            color: #7CFC00;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.03);
        }
        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .pay-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 8px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 6px;
            font-size: 0.78rem;
            color: #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }
        .status-pending { background: rgba(234, 179, 8, 0.2); color: #fde047; border: 1px solid rgba(234, 179, 8, 0.4); }
        .status-preparing { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4); }
        .status-out_for_delivery { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.4); }
        .status-delivered { background: rgba(34, 197, 94, 0.2); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.4); }
        .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
        .status-select {
            background: #1f2937;
            border: 1px solid #374151;
            color: #fff;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .status-select:focus {
            outline: none;
            border-color: #7CFC00;
        }
        .action-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .clear-btn {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.45);
            color: #fca5a5;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clear-btn:hover {
            background: #ef4444;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="images/BitesNow.png" alt="BitesNow Logo" height="40" style="height: 40px; width: auto;">
                <span>BitesNow Admin</span>
            </a>
            <div class="nav-actions">
                <span class="nav-user" style="color: #f59e0b; font-weight: 600;">Admin: <?= htmlspecialchars($adminName) ?></span>
                <a href="food.php" class="tbl-btn" style="padding: 6px 14px; font-size: 0.85rem;">View Storefront</a>
                <a href="logout.php" class="nav-logout">Log out</a>
            </div>
        </div>
    </header>

    <main class="admin-wrap">
        <div class="admin-header">
            <div>
                <h1 style="margin: 0; font-size: 2rem; color: #ffffff;">Kitchen Order Queue</h1>
                <p style="margin: 4px 0 0; color: #9ca3af;">Live order stream and fulfillment dispatcher.</p>
            </div>
            <a href="admin_orders.php" class="cta-btn" style="font-size: 0.88rem; padding: 10px 18px;">Refresh Queue ↻</a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="notice success" style="margin-bottom: 22px;">
                ✓ Order status updated successfully. Customer tracker has been synced.
            </div>
        <?php elseif (isset($_GET['cleared'])): ?>
            <div class="notice success" style="margin-bottom: 22px;">
                ✓ Completed order(s) cleared from the kitchen queue.
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <section class="admin-card">
            <div class="card-top-bar">
                <h2 style="margin: 0; color: #7CFC00;">Current Kitchen Orders</h2>

                <?php if ($completedCount > 0): ?>
                    <form action="admin_orders.php" method="post" onsubmit="return confirm('Clear all <?= $completedCount ?> delivered/cancelled order(s) from the queue?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="clear_delivered">
                        <button type="submit" class="clear-btn">
                            Clear All Delivered (<?= $completedCount ?>)
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($orders)): ?>
                <p style="color: #9ca3af; margin: 16px 0;">No active orders in the queue right now.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer &amp; Dispatch</th>
                                <th>Items &amp; Payment</th>
                                <th>Current Status</th>
                                <th>Update Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                    $rawSummary = $ord['order_summary'] ?? 'Items';
                                    $rawPayment = strtolower(trim($ord['payment_method'] ?? ''));

                                    if (empty($rawPayment) && preg_match('/\[Payment:\s*([A-Z]+)\]/i', $rawSummary, $matches)) {
                                        $rawPayment = strtolower($matches[1]);
                                        $rawSummary = trim(preg_replace('/\n?\[Payment:\s*[A-Z]+\]/i', '', $rawSummary));
                                    }
                                    $displayPayment = $paymentLabels[$rawPayment] ?? '💵 COD (Collect Cash)';
                                    $addressText = !empty(trim($ord['address'] ?? '')) ? $ord['address'] : ($ord['delivery_address'] ?? '');
                                    $isCompleted = in_array(strtolower($ord['status'] ?? ''), ['delivered', 'cancelled'], true);
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?= htmlspecialchars($ord['id']) ?></strong><br>
                                        <small style="color: #9ca3af;"><?= date('d M, h:i A', strtotime($ord['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ord['customer_name'] ?? ('User #' . ($ord['user_id'] ?? 'Guest'))) ?></strong><br>
                                        <?php if (!empty($ord['phone'])): ?>
                                            <small style="color: #d1d5db;"><?= htmlspecialchars($ord['phone']) ?></small><br>
                                        <?php endif; ?>
                                        <?php if (!empty($addressText)): ?>
                                            <small style="color: #9ca3af; display: inline-block; max-width: 240px; margin-top: 2px;">
                                                <?= htmlspecialchars($addressText) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="line-height: 1.45; margin-bottom: 6px;">
                                            <?= nl2br(htmlspecialchars($rawSummary)) ?>
                                        </div>
                                        <strong style="color: #7CFC00;">RM <?= number_format((float)($ord['total_amount'] ?? 0), 2) ?></strong><br>
                                        <span class="pay-pill"><?= htmlspecialchars($displayPayment) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($ord['status']) ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', $ord['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <form action="admin_orders.php" method="post" style="display: flex; gap: 8px; align-items: center;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($ord['id']) ?>">
                                                <select name="status" class="status-select" aria-label="Order status">
                                                    <option value="pending" <?= $ord['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="preparing" <?= $ord['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                    <option value="out_for_delivery" <?= $ord['status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                                    <option value="delivered" <?= $ord['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $ord['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" class="cta-btn" style="padding: 7px 14px; font-size: 0.82rem;">Update</button>
                                            </form>

                                            <?php if ($isCompleted): ?>
                                                <form action="admin_orders.php" method="post" onsubmit="return confirm('Clear completed Order #<?= htmlspecialchars($ord['id']) ?> from the queue?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="clear_order">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($ord['id']) ?>">
                                                    <button type="submit" class="clear-btn" title="Remove completed order">Clear</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Inquiries Table (Only shown if inquiries exist) -->
        <?php if (!empty($inquiries)): ?>
            <section class="admin-card">
                <h2 style="margin-top: 0; color: #60a5fa;">Recent Customer Inquiries</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Email</th>
                                <th>Message</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiries as $inq): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inq['name']) ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($inq['email']) ?>" style="color: #60a5fa;"><?= htmlspecialchars($inq['email']) ?></a></td>
                                    <td><?= htmlspecialchars($inq['message']) ?></td>
                                    <td style="color: #9ca3af; font-size: 0.8rem;"><?= htmlspecialchars($inq['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Standardized Master Footer -->
    <footer>
        <p>Phone: <a href="tel:+60172623084">017-262 3084</a> | Email: <a href="mailto:BitesNow@gmail.com">BitesNow@gmail.com</a></p>
        <p>
            <a href="AboutUs.php">About Us</a> |
            <a href="location.php">Location</a> |
            <a href="product_review.php">Ratings</a>
        </p>
        <p>&copy; 2026 BitesNow. All rights reserved.</p>
    </footer>

</body>
</html>