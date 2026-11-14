<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authentication Guard: Must be logged in to view orders
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: login.php?status=login_required&redirect=my_orders.php');
    exit;
}

$isLoggedIn = true;
$userId     = (int)($_SESSION['user_id'] ?? 1);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';

// 2. Database Connection
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$orders = [];
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Fetch all columns safely without risking missing-table/column exceptions
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("My Orders Query Error: " . $e->getMessage());
    $orders = [];
}

$paymentLabels = [
    'cod'  => '💵 Cash on Delivery',
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
    <title>My Orders | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <style>
        .orders-container {
            max-width: 1100px;
            margin: 40px auto 60px;
            padding: 0 20px;
        }
        .orders-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            text-align: left;
        }
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .orders-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #ffffff;
        }
        .orders-table-wrapper {
            overflow-x: auto;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            text-align: left;
        }
        .orders-table th {
            background: rgba(255, 255, 255, 0.04);
            padding: 14px 16px;
            color: #7CFC00;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }
        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            color: #e5e7eb;
            vertical-align: middle;
        }
        .orders-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }
        .empty-history {
            text-align: center;
            padding: 50px 20px;
            color: #9ca3af;
        }
        .empty-history p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .track-btn {
            display: inline-block;
            background: #1e8e2e;
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            white-space: nowrap;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .track-btn:hover {
            background: #27ae3a;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

    <!-- Standardized Master Header -->
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="images/BitesNow.png" alt="BitesNow Logo" height="40" style="height: 40px; width: auto;">
                <span>BitesNow</span>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
                <span></span><span></span><span></span>
            </button>

            <nav class="site-nav" id="siteNav" aria-label="Main navigation">
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="food.php">Menu</a></li>
                    <li><a href="recom.php">Recommended</a></li>

                    <?php if ($userRole === 'admin'): ?>
                        <li><a href="admin_orders.php" style="color: #f59e0b; font-weight: bold;">Kitchen Queue</a></li>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <li><a href="my_orders.php" style="color: #7CFC00;">My Orders</a></li>
                    <?php endif; ?>

                    <li><a href="product_review.php">Ratings</a></li>
                    <li><a href="location.php">Location</a></li>
                    <li><a href="AboutUs.php">About Us</a></li>
                </ul>

                <div class="nav-actions">
                    <a href="cart.php" class="nav-cart" aria-label="Cart">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>
                        </svg>
                        <span class="nav-cart-label">Cart</span>
                        <span class="cart-count" id="cartCount" hidden>0</span>
                    </a>

                    <?php if ($isLoggedIn): ?>
                        <span class="nav-user">Hi, <?= htmlspecialchars($userName) ?></span>
                        <a href="logout.php" class="nav-logout">Log out</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-login">Log in</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="orders-container">
        <section class="orders-card">
            <div class="orders-header">
                <div>
                    <h1>My Order History</h1>
                    <p style="color: #9ca3af; margin: 4px 0 0; font-size: 0.9rem;">Track live deliveries and view past receipts</p>
                </div>
                <a href="food.php" class="cta-btn" style="padding: 8px 18px; font-size: 0.88rem;">+ Order Food</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-history">
                    <p>You haven't placed any orders yet.</p>
                    <a href="food.php" class="cta-btn">Browse Today's Menu</a>
                </div>
            <?php else: ?>
                <div class="orders-table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Placed On</th>
                                <th>Items</th>
                                <th>Total &amp; Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <?php
                                    $rawStatus = strtolower($o['status'] ?? 'pending');
                                    $displayStatus = ucwords(str_replace('_', ' ', $rawStatus));

                                    // Parse payment method from column or fallback text tag
                                    $rawSummary = $o['order_summary'] ?? 'Item(s) recorded';
                                    $rawPayment = strtolower(trim($o['payment_method'] ?? ''));

                                    if (empty($rawPayment) && preg_match('/\[Payment:\s*([A-Z]+)\]/i', $rawSummary, $matches)) {
                                        $rawPayment = strtolower($matches[1]);
                                        $rawSummary = trim(preg_replace('/\n?\[Payment:\s*[A-Z]+\]/i', '', $rawSummary));
                                    }
                                    $displayPayment = $paymentLabels[$rawPayment] ?? '💵 Cash on Delivery';
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($o['id']) ?></strong></td>
                                    <td style="color: #9ca3af; white-space: nowrap;">
                                        <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                    </td>
                                    <td style="line-height: 1.45;">
                                        <?= nl2br(htmlspecialchars($rawSummary)) ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <span style="color: #7CFC00; font-weight: 700; display: block;">
                                            RM <?= number_format((float)($o['total_amount'] ?? 0), 2) ?>
                                        </span>
                                        <small style="color: #9ca3af; font-size: 0.78rem;">
                                            <?= htmlspecialchars($displayPayment) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($rawStatus) ?>">
                                            <?= htmlspecialchars($displayStatus) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="track_order.php?order_id=<?= urlencode($o['id']) ?>" class="track-btn">
                                            Track &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
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

    <script>
        // Mobile menu toggle
        const toggle = document.getElementById('navToggle');
        const nav    = document.getElementById('siteNav');
        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });
        }

        // Cart localStorage badge counter
        const CART_KEY = 'bitesnowCart';
        try {
            const cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
            const count = cart.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        } catch (e) {}

        // Wipe cart from browser on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>