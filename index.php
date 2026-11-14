<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userId     = (int)($_SESSION['user_id'] ?? 0);
$userRole   = $_SESSION['role'] ?? 'customer';
$status     = $_GET['status'] ?? '';

// Query active orders (pending, preparing, or out for delivery)
$activeOrder = null;
if ($userId > 0) {
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'bitesnow';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        $conn->set_charset('utf8mb4');

        $stmt = $conn->prepare("
            SELECT id, status, total_amount, created_at 
            FROM orders 
            WHERE user_id = ? AND status IN ('pending', 'preparing', 'out_for_delivery') 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $activeOrder = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Active order fetch error: " . $e->getMessage());
        $activeOrder = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BitesNow | Fresh Food Delivered Fast</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <style>
        /* Active order live banner */
        .active-order-banner {
            max-width: 1100px;
            margin: 24px auto 0 auto;
            padding: 14px 22px;
            background: linear-gradient(90deg, #14532d 0%, #166534 100%);
            border: 1px solid #22c55e;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.4);
            color: #ffffff;
            animation: fadeIn 0.4s ease-out;
        }
        .banner-left {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.95rem;
            text-align: left;
        }
        .pulse-indicator {
            width: 12px;
            height: 12px;
            background-color: #7CFC00;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            box-shadow: 0 0 0 0 rgba(124, 252, 0, 0.7);
            animation: pulseAnimation 1.6s infinite;
        }
        @keyframes pulseAnimation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(124, 252, 0, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(124, 252, 0, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(124, 252, 0, 0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .banner-btn {
            background: #000000;
            color: #7CFC00;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .banner-btn:hover {
            background: #111827;
            transform: translateY(-1px);
        }

        /* Hero Section */
        .hero {
            max-width: 1100px;
            margin: 40px auto 40px auto;
            padding: 60px 24px;
            text-align: center;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }
        .hero h1 {
            font-size: 3rem;
            margin-top: 0;
            margin-bottom: 16px;
            color: #ffffff;
        }
        .hero h1 span {
            color: #7CFC00;
        }
        .hero p {
            font-size: 1.15rem;
            color: #d1d5db;
            max-width: 650px;
            margin: 0 auto 32px auto;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Features Section */
        .features-grid {
            max-width: 1100px;
            margin: 0 auto 60px auto;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .feature-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 28px 24px;
            text-align: left;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .feature-card h3 {
            color: #7CFC00;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        .feature-card p {
            color: #d1d5db;
            font-size: 0.95rem;
            line-height: 1.55;
            margin: 0;
        }

        @media (max-width: 1140px) {
            .hero, .features-grid, .active-order-banner {
                margin-left: 20px;
                margin-right: 20px;
            }
        }
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.2rem; }
            .hero { padding: 40px 18px; margin: 20px 16px 30px 16px; }
            .features-grid { margin: 0 16px 40px 16px; }
            .active-order-banner {
                flex-direction: column;
                gap: 12px;
                text-align: center;
                margin: 16px;
            }
            .banner-left {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Master Site Header -->
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

    <main>
        <?php if ($status === 'loggedout'): ?>
            <div class="notice success" role="status" style="max-width: 1100px; margin: 20px auto 0;">
                ✓ You have been logged out safely.
            </div>
        <?php endif; ?>

        <!-- Persistent Live Order Tracker Banner for Active Orders -->
        <?php if ($activeOrder): ?>
            <?php 
                $cleanStatus = ucwords(str_replace('_', ' ', $activeOrder['status'])); 
            ?>
            <section class="active-order-banner" aria-label="Active order tracking">
                <div class="banner-left">
                    <span class="pulse-indicator"></span>
                    <div>
                        <strong>Your meal is underway!</strong>
                        <span style="color: #e5e7eb; display: block; font-size: 0.88rem;">
                            Order #<?= htmlspecialchars($activeOrder['id']) ?> is currently <?= htmlspecialchars($cleanStatus) ?>
                        </span>
                    </div>
                </div>
                <a href="track_order.php?order_id=<?= urlencode($activeOrder['id']) ?>" class="banner-btn">
                    Track Live Order &rarr;
                </a>
            </section>
        <?php endif; ?>

        <!-- Hero Banner -->
        <section class="hero">
            <h1>Cravings Delivered <span>Hot &amp; Fresh</span></h1>
            <p>
                Explore authentic local delights, chef specialties, and comfort favorites delivered straight to your doorstep with real-time tracking.
            </p>
            <div class="hero-actions">
                <a href="food.php" class="cta-btn" style="padding: 12px 28px; font-size: 1rem;">Explore Menu</a>
                <a href="recom.php" class="tbl-btn" style="padding: 12px 24px; font-size: 1rem; border: 1px solid rgba(255,255,255,0.2);">Chef's Picks</a>
            </div>
        </section>

        <!-- Service Features -->
        <section class="features-grid">
            <div class="feature-card">
                <h3>⚡ Fast Kitchen Prep</h3>
                <p>Every order is prepped fresh upon placement, ensuring hot meals and quick delivery handover.</p>
            </div>
            <div class="feature-card">
                <h3>📍 Live Order Tracking</h3>
                <p>Monitor your meal from receipt to kitchen prep and delivery right inside your account dashboard.</p>
            </div>
            <div class="feature-card">
                <h3>💳 Flexible Checkout</h3>
                <p>Choose from Cash on Delivery, DuitNow / TnG QR, Card, or FPX online banking with end-to-end security.</p>
            </div>
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

        // Highlight current nav item
        const currentPage = location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.nav-links a').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Failsafe: clear cart if redirected here after logout
        const CART_KEY = 'bitesnowCart';
        if (new URLSearchParams(window.location.search).get('status') === 'loggedout') {
            localStorage.removeItem(CART_KEY);
        }

        // Cart badge count
        try {
            const cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
            const count = cart.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        } catch (e) {}

        // Clear cart on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>