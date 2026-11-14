<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userId     = (int)($_SESSION['user_id'] ?? 0);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';

// 1. Validate Order ID parameter
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    header('Location: my_orders.php');
    exit;
}

// 2. Fetch Order from Database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$order = null;
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Admins can inspect any order; customers can only track their own orders
    if ($userRole === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $orderId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND (user_id = ? OR user_id IS NULL) LIMIT 1");
        $stmt->bind_param("ii", $orderId, $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Tracking Error: " . $e->getMessage());
    $order = null;
}

// If order does not exist or user lacks permission
if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// 3. Status Stepper Mapping
$rawStatus = strtolower($order['status'] ?? 'pending');
$statusSteps = [
    'pending'          => 1,
    'preparing'        => 2,
    'out_for_delivery' => 3,
    'delivered'        => 4,
    'cancelled'        => 0
];
$currentStep = $statusSteps[$rawStatus] ?? 1;

// 4. Dynamic Payment Method Label (supports both column and fallback text)
$rawSummary = $order['order_summary'] ?? 'Items registered';
$rawPayment = strtolower(trim($order['payment_method'] ?? ''));

if (empty($rawPayment) && preg_match('/\[Payment:\s*([A-Z]+)\]/i', $rawSummary, $matches)) {
    $rawPayment = strtolower($matches[1]);
    $rawSummary = trim(preg_replace('/\n?\[Payment:\s*[A-Z]+\]/i', '', $rawSummary));
}

$paymentLabels = [
    'cod'  => '💵 Cash on Delivery (COD)',
    'qr'   => '📱 DuitNow / TnG eWallet QR',
    'card' => '💳 Credit / Debit Card',
    'fpx'  => '🏦 Online Banking (FPX)'
];
$displayPayment = $paymentLabels[$rawPayment] ?? '💵 Cash on Delivery (COD)';

// Address fallback
$deliveryAddress = !empty(trim($order['address'] ?? '')) ? $order['address'] : ($order['delivery_address'] ?? 'No delivery address recorded');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto-refresh every 15 seconds to stream live status changes from the kitchen -->
    <meta http-equiv="refresh" content="15">
    <title>Track Order #<?= htmlspecialchars($order['id']) ?> | BitesNow</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <style>
        .track-container {
            max-width: 800px;
            margin: 40px auto 60px;
            padding: 0 20px;
            text-align: left;
        }
        .track-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }
        .track-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 18px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .track-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #ffffff;
        }
        .track-header p {
            margin: 4px 0 0;
            color: #9ca3af;
            font-size: 0.9rem;
        }
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(124, 252, 0, 0.1);
            color: #7CFC00;
            border: 1px solid rgba(124, 252, 0, 0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #7CFC00;
            border-radius: 50%;
            box-shadow: 0 0 8px #7CFC00;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Progress Stepper */
        .stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0 45px;
        }
        .stepper::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            height: 4px;
            background: #374151;
            z-index: 1;
        }
        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 44px;
            height: 44px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #1f2937;
            border: 2px solid #4b5563;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .step-label {
            font-size: 0.82rem;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Active / Completed Stepper States */
        .step-item.completed .step-circle {
            background: #7CFC00;
            border-color: #7CFC00;
            color: #000000;
        }
        .step-item.completed .step-label {
            color: #7CFC00;
        }
        .step-item.active .step-circle {
            background: #111827;
            border-color: #7CFC00;
            color: #7CFC00;
            box-shadow: 0 0 12px rgba(124, 252, 0, 0.5);
        }
        .step-item.active .step-label {
            color: #ffffff;
            font-weight: 700;
        }

        /* Cancelled Banner */
        .cancelled-banner {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 18px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
        }

        /* Order Details Breakdown */
        .order-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .meta-col h3 {
            margin: 0 0 8px;
            font-size: 0.8rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-col p {
            margin: 0;
            font-size: 0.95rem;
            color: #e5e7eb;
            line-height: 1.5;
        }
        .total-highlight {
            font-size: 1.3rem !important;
            color: #7CFC00 !important;
            font-weight: bold;
            margin-bottom: 4px !important;
        }

        @media (max-width: 600px) {
            .step-label {
                font-size: 0.7rem;
            }
            .step-circle {
                width: 36px;
                height: 36px;
                font-size: 0.95rem;
            }
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
                        <li><a href="my_orders.php">My Orders</a></li>
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

    <main class="track-container">
        <div class="track-card">
            
            <div class="track-header">
                <div>
                    <h1>Tracking Order #<?= htmlspecialchars($order['id']) ?></h1>
                    <p>Ordered on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                </div>
                <div class="live-badge">
                    <span class="pulse-dot"></span>
                    <span>Status: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $rawStatus))) ?></span>
                </div>
            </div>

            <!-- Progress Tracker -->
            <?php if ($rawStatus === 'cancelled'): ?>
                <div class="cancelled-banner">
                    ⚠️ This order was cancelled. If you have inquiries, please contact our dispatch hotline.
                </div>
            <?php else: ?>
                <div class="stepper" role="progressbar" aria-valuenow="<?= $currentStep ?>" aria-valuemin="1" aria-valuemax="4">
                    
                    <div class="step-item <?= $currentStep > 1 ? 'completed' : ($currentStep === 1 ? 'active' : '') ?>">
                        <div class="step-circle"><?= $currentStep > 1 ? '✓' : '1' ?></div>
                        <div class="step-label">Order Placed</div>
                    </div>

                    <div class="step-item <?= $currentStep > 2 ? 'completed' : ($currentStep === 2 ? 'active' : '') ?>">
                        <div class="step-circle"><?= $currentStep > 2 ? '✓' : '2' ?></div>
                        <div class="step-label">In Kitchen</div>
                    </div>

                    <div class="step-item <?= $currentStep > 3 ? 'completed' : ($currentStep === 3 ? 'active' : '') ?>">
                        <div class="step-circle"><?= $currentStep > 3 ? '✓' : '3' ?></div>
                        <div class="step-label">Out for Delivery</div>
                    </div>

                    <div class="step-item <?= $currentStep === 4 ? 'completed active' : '' ?>">
                        <div class="step-circle"><?= $currentStep === 4 ? '✓' : '4' ?></div>
                        <div class="step-label">Delivered</div>
                    </div>

                </div>
            <?php endif; ?>

            <!-- Breakdown Details -->
            <div class="order-meta-grid">
                <div class="meta-col">
                    <h3>Dishes Ordered</h3>
                    <p><?= nl2br(htmlspecialchars($rawSummary)) ?></p>
                </div>

                <div class="meta-col">
                    <h3>Delivery To</h3>
                    <p>
                        <strong><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></strong><br>
                        <?= nl2br(htmlspecialchars($deliveryAddress)) ?><br>
                        <small style="color: #9ca3af;"><?= htmlspecialchars($order['phone'] ?? ($order['customer_phone'] ?? '')) ?></small>
                    </p>
                </div>

                <div class="meta-col">
                    <h3>Payment Total</h3>
                    <p class="total-highlight">RM <?= number_format((float)($order['total_amount'] ?? 0), 2) ?></p>
                    <small style="color: #9ca3af;"><?= htmlspecialchars($displayPayment) ?></small>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 28px; flex-wrap: wrap; gap: 12px;">
                <a href="my_orders.php" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;">&larr; Back to Order History</a>
                <a href="track_order.php?order_id=<?= urlencode($order['id']) ?>" class="cta-btn" style="padding: 8px 18px; font-size: 0.88rem;">Refresh Status ↻</a>
            </div>

        </div>
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
        // 1. Mobile Menu Toggle
        const toggle = document.getElementById('navToggle');
        const nav    = document.getElementById('siteNav');
        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });
        }

        // 2. Clear Cart on Checkout Redirect & Clean URL
        const CART_KEY = 'bitesnowCart';
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('clear_cart')) {
            localStorage.removeItem(CART_KEY);
            urlParams.delete('clear_cart');
            const newQuery = urlParams.toString();
            const cleanUrl = window.location.pathname + (newQuery ? '?' + newQuery : '');
            window.history.replaceState({}, '', cleanUrl);
        }

        // 3. Sync Cart Badge
        try {
            const cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
            const count = cart.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        } catch (e) {}

        // 4. Wipe cart on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>