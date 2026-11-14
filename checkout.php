<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Guard: Check both user_id and username sessions
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: login.php?status=login_required&redirect=checkout.php');
    exit;
}

$isLoggedIn = true;
$userId     = (int)($_SESSION['user_id'] ?? 1); // Fallback to 1 if user_id is missing to prevent FK failure
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// 2. Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verify CSRF token
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security token mismatch. Please refresh and try again.";
    } else {
        $dbHost = 'localhost';
        $dbUser = 'root';
        $dbPass = '';
        $dbName = 'bitesnow';
        
        $customerName  = trim($_POST['customer_name'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $address       = trim($_POST['address'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'cod');
        
        // Decode the JSON cart passed from JavaScript
        $cartData      = json_decode($_POST['cart_data'] ?? '[]', true);

        if (empty($customerName) || empty($phone) || empty($address)) {
            $error = "Please fill in your name, phone number, and delivery address.";
        } elseif (empty($cartData)) {
            $error = "Your cart is empty. Please add items from the menu first.";
        } else {
            // Calculate totals and format summary
            $totalAmount  = 0;
            $summaryItems = [];
            
            foreach ($cartData as $item) {
                $qty   = (int)($item['qty'] ?? 1);
                $price = (float)($item['price'] ?? 0);
                $name  = trim($item['name'] ?? 'Item');
                
                $totalAmount += ($qty * $price);
                $summaryItems[] = "{$qty}x {$name}";
            }
            $orderSummary = implode("\n", $summaryItems);

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
                $conn->set_charset('utf8mb4');

                // Check if 'payment_method' column exists in database
                $colCheck = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'payment_method'");
                $hasPaymentCol = ($colCheck && $colCheck->num_rows > 0);

                if ($hasPaymentCol) {
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, phone, address, order_summary, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->bind_param("issssds", $userId, $customerName, $phone, $address, $orderSummary, $totalAmount, $paymentMethod);
                } else {
                    // Fallback: append payment method into order summary text so MySQL doesn't fail
                    $orderSummary .= "\n[Payment: " . strtoupper($paymentMethod) . "]";
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, phone, address, order_summary, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->bind_param("issssd", $userId, $customerName, $phone, $address, $orderSummary, $totalAmount);
                }

                $stmt->execute();
                $newOrderId = (int)$stmt->insert_id;
                $stmt->close();
                $conn->close();

                // Redirect to tracker with a flag to clear the browser cart
                header("Location: track_order.php?order_id=$newOrderId&clear_cart=1");
                exit;

            } catch (Exception $e) {
                error_log("Checkout Error: " . $e->getMessage());
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <style>
        .checkout-container {
            max-width: 1040px;
            margin: 40px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 28px;
            align-items: start;
            box-sizing: border-box;
        }
        @media (max-width: 820px) {
            .checkout-container {
                grid-template-columns: 1fr;
                margin: 20px auto 40px;
            }
        }
        .checkout-card {
            background: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            text-align: left;
        }
        .checkout-card h2 {
            margin-top: 0;
            color: #7CFC00;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 14px;
            margin-bottom: 22px;
            font-size: 1.35rem;
        }
        .field {
            margin-bottom: 18px;
        }
        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            color: #e5e7eb;
            font-weight: 600;
        }
        .field input, .field textarea {
            width: 100%;
            padding: 12px 14px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 6px;
            color: #ffffff;
            box-sizing: border-box;
            font-family: inherit;
        }
        .field input:focus, .field textarea:focus {
            outline: none;
            border-color: #7CFC00;
            box-shadow: 0 0 0 3px rgba(124, 252, 0, 0.2);
        }
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #d1d5db;
        }
        .receipt-total {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 1.25rem;
            font-weight: bold;
            color: #7CFC00;
        }
        .empty-cart-msg {
            text-align: center;
            color: #fca5a5;
            padding: 20px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 6px;
        }
        @media (max-width: 580px) {
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
        .pay-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.88rem;
            color: #e5e7eb;
            user-select: none;
            transition: all 0.2s ease;
        }
        .pay-option:hover {
            border-color: #7CFC00;
            background: rgba(124, 252, 0, 0.05);
        }
        .pay-option input[type="radio"] {
            width: auto;
            margin: 0;
            accent-color: #7CFC00;
        }
        .payment-notice {
            margin: 12px 0 18px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.04);
            border-left: 3px solid #7CFC00;
            border-radius: 4px;
            font-size: 0.84rem;
            color: #d1d5db;
            line-height: 1.45;
        }
    </style>
</head>
<body>

    <!-- Header / Navigation -->
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

    <main class="checkout-container">
        
        <!-- Left Column: Delivery Details & Payment -->
        <section class="checkout-card">
            <h2>Delivery Details</h2>
            
            <?php if (!empty($error)): ?>
                <div class="notice error" style="margin-bottom: 20px; padding: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 6px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="checkout.php" method="post" id="checkoutForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="cart_data" id="cartDataPayload">

                <div class="field">
                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($userName) ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="e.g., 012-3456789" required>
                </div>

                <div class="field">
                    <label for="address">Full Delivery Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Unit, Building, Street, Area..." required></textarea>
                </div>

                <!-- Payment Method Selector -->
                <div class="field">
                    <label>Payment Method</label>
                    <div class="payment-methods">
                        <label class="pay-option">
                            <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentNotice(this.value)">
                            <span>💵 Cash on Delivery</span>
                        </label>
                        <label class="pay-option">
                            <input type="radio" name="payment_method" value="qr" onchange="togglePaymentNotice(this.value)">
                            <span>📱 DuitNow / TnG QR</span>
                        </label>
                        <label class="pay-option">
                            <input type="radio" name="payment_method" value="card" onchange="togglePaymentNotice(this.value)">
                            <span>💳 Credit / Debit Card</span>
                        </label>
                        <label class="pay-option">
                            <input type="radio" name="payment_method" value="fpx" onchange="togglePaymentNotice(this.value)">
                            <span>🏦 Online Banking (FPX)</span>
                        </label>
                    </div>
                </div>

                <div id="paymentNotice" class="payment-notice">
                    Please prepare exact cash for our dispatch rider upon delivery.
                </div>

                <button type="submit" class="cta-btn" id="submitBtn" style="width: 100%; margin-top: 10px;">Place Order</button>
            </form>
        </section>

        <!-- Right Column: Live Order Summary -->
        <section class="checkout-card">
            <h2>Order Summary</h2>
            
            <div id="emptyCartWarning" class="empty-cart-msg" hidden>
                Your cart is empty. <br><br>
                <a href="food.php" class="cta-btn" style="padding: 8px 16px; font-size: 0.85rem;">Browse Menu to Add Food</a>
            </div>

            <div id="receiptContainer">
                <div id="receiptItems"></div>
                <div class="receipt-total">
                    <span>Total</span>
                    <span id="receiptTotal">RM 0.00</span>
                </div>
            </div>
            
            <p id="paymentFooterNote" style="font-size: 0.8rem; color: #9ca3af; margin-top: 20px; text-align: center;">
                Selected method: Cash on Delivery (COD)
            </p>
        </section>

    </main>

    <!-- Master Footer -->
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

        // 2. Toggle Payment Notices
        function togglePaymentNotice(method) {
            const notice = document.getElementById('paymentNotice');
            const footerNote = document.getElementById('paymentFooterNote');
            
            const messages = {
                'cod': {
                    notice: "Please prepare exact cash for our dispatch rider upon delivery.",
                    footer: "Selected method: Cash on Delivery (COD)"
                },
                'qr': {
                    notice: "DuitNow / Touch 'n Go QR code will be presented by the rider or online.",
                    footer: "Selected method: DuitNow / TnG eWallet QR"
                },
                'card': {
                    notice: "Secure card payment simulated. No physical cash required upon delivery.",
                    footer: "Selected method: Credit / Debit Card"
                },
                'fpx': {
                    notice: "Direct Malaysian online bank transfer (Maybank2u, CIMB Clicks, Public Bank, etc.).",
                    footer: "Selected method: Online Banking (FPX)"
                }
            };

            if (messages[method]) {
                if (notice) notice.textContent = messages[method].notice;
                if (footerNote) footerNote.textContent = messages[method].footer;
            }
        }

        // 3. Load, Sync, and Safeguard Cart
        const CART_KEY = 'bitesnowCart';
        
        function getCartItems() {
            try {
                return JSON.parse(localStorage.getItem(CART_KEY)) || [];
            } catch (e) {
                return [];
            }
        }

        function loadCheckoutCart() {
            const cart = getCartItems();
            const receiptItems = document.getElementById('receiptItems');
            const receiptTotal = document.getElementById('receiptTotal');
            const payloadInput = document.getElementById('cartDataPayload');
            const submitBtn    = document.getElementById('submitBtn');
            const emptyWarning = document.getElementById('emptyCartWarning');
            const receiptDiv   = document.getElementById('receiptContainer');
            const cartBadge    = document.getElementById('cartCount');

            // Populate hidden payload
            payloadInput.value = JSON.stringify(cart);

            const totalQty = cart.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            if (cartBadge) {
                cartBadge.textContent = totalQty;
                cartBadge.hidden = totalQty === 0;
            }

            if (cart.length === 0) {
                emptyWarning.hidden = false;
                receiptDiv.hidden = true;
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.textContent = 'Cart is empty';
                return;
            }

            // Cart has items: re-enable button and calculate breakdown
            emptyWarning.hidden = true;
            receiptDiv.hidden = false;
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.textContent = 'Place Order';

            let total = 0;
            receiptItems.innerHTML = '';
            
            cart.forEach(item => {
                const subtotal = (Number(item.qty) || 1) * (parseFloat(item.price) || 0);
                total += subtotal;
                
                receiptItems.innerHTML += `
                    <div class="receipt-item">
                        <span>${item.qty}x ${item.name}</span>
                        <span>RM ${subtotal.toFixed(2)}</span>
                    </div>
                `;
            });

            receiptTotal.textContent = `RM ${total.toFixed(2)}`;
        }

        // 4. Form Submit Handler: Guarantee payload sync & visual feedback
        const form = document.getElementById('checkoutForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                const cart = getCartItems();
                if (cart.length === 0) {
                    e.preventDefault();
                    alert('Your cart is empty! Please add food from the menu before placing an order.');
                    return false;
                }
                
                // Final sync before sending to PHP
                document.getElementById('cartDataPayload').value = JSON.stringify(cart);

                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.textContent = 'Placing Order...';
            });
        }

        document.addEventListener('DOMContentLoaded', loadCheckoutCart);

        // Wipe cart from browser on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>