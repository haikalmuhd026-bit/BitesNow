<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <link rel="stylesheet" href="cart.css?v=2">
    <style>
        /* Ensure HTML [hidden] works even on grid/flex containers */
        [hidden] {
            display: none !important;
        }

        .cart-section {
            max-width: 1040px;
            margin: 40px auto 60px;
            padding: 0 20px;
            text-align: left;
        }

        .cart-section h1 {
            font-size: 2.2rem;
            color: #ffffff;
            margin: 0 0 6px;
        }

        .cart-section .menu-intro {
            color: #9ca3af;
            margin: 0 0 28px;
            font-size: 0.95rem;
        }

        .cart-empty {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 50px 24px;
            text-align: center;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.5);
        }

        .cart-empty p {
            font-size: 1.15rem;
            color: #d1d5db;
            margin: 0 0 20px;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 820px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
        }

        .cart-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cart-item {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .item-info {
            flex: 1 1 180px;
        }

        .item-info strong {
            display: block;
            font-size: 1.05rem;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .item-info span {
            font-size: 0.86rem;
            color: #9ca3af;
        }

        .qty {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 8px;
            padding: 4px 8px;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: #7CFC00;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            padding: 2px 8px;
            border-radius: 4px;
            transition: background 0.15s ease;
        }

        .qty-btn:hover {
            background: rgba(124, 252, 0, 0.15);
        }

        .qty-num {
            font-weight: 700;
            color: #ffffff;
            min-width: 20px;
            text-align: center;
        }

        .line-total {
            font-weight: 700;
            color: #7CFC00;
            font-size: 1rem;
            min-width: 85px;
            text-align: right;
        }

        .remove-btn {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remove-btn:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .cart-summary {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            position: sticky;
            top: 90px;
        }

        .cart-summary h2 {
            margin: 0 0 18px;
            color: #7CFC00;
            font-size: 1.3rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
        }

        .sum-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #d1d5db;
            font-size: 0.95rem;
        }

        .sum-row.total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
        }

        .sum-row.total strong {
            color: #7CFC00;
        }

        .checkout-btn, .more-btn {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 12px;
            box-sizing: border-box;
        }

        .link-btn {
            display: block;
            width: 100%;
            background: transparent;
            border: none;
            color: #9ca3af;
            font-size: 0.85rem;
            margin-top: 14px;
            cursor: pointer;
            text-decoration: underline;
        }

        .link-btn:hover {
            color: #fca5a5;
        }
    </style>
</head>
<body>

    <!-- Synchronized Master Header -->
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
        <section class="section cart-section" aria-labelledby="cartTitle">
            <h1 id="cartTitle">Your Cart</h1>
            <p class="menu-intro">Review your order before checking out.</p>

            <!-- Shown when the cart has nothing in it -->
            <div id="cartEmpty" class="cart-empty" hidden>
                <p>Your cart is empty.</p>
                <a href="food.php" class="cta-btn">Browse the menu</a>
            </div>

            <!-- Shown when the cart has items -->
            <div id="cartContent" class="cart-grid" hidden>
                <ul id="cartItems" class="cart-list" aria-label="Items in your cart"></ul>

                <aside class="cart-summary" aria-labelledby="summaryTitle">
                    <h2 id="summaryTitle">Order Summary</h2>
                    <div class="sum-row"><span>Items</span><span id="itemCount">0</span></div>
                    <div class="sum-row total"><span>Total</span><strong id="cartTotal" aria-live="polite">RM 0.00</strong></div>

                    <a href="checkout.php" class="cta-btn checkout-btn">Check out</a>
                    <a href="food.php" class="tbl-btn more-btn">Add more food</a>
                    <button type="button" id="clearCart" class="link-btn">Clear cart</button>
                </aside>
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
        const nav = document.getElementById('siteNav');

        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            });
        }

        // Highlight the current page link
        const currentPage = location.pathname.split('/').pop() || 'cart.php';
        document.querySelectorAll('.nav-links a, .nav-cart').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Cart (same storage key as the menu page)
        const CART_KEY = 'bitesnowCart';

        function getCart() {
            try {
                const data = JSON.parse(localStorage.getItem(CART_KEY)) || [];
                return data.filter(i => i && i.name && i.price >= 0 && i.qty > 0);
            } catch (e) { return []; }
        }

        function saveCart(cart) {
            try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
            catch (e) { console.error('Could not save cart', e); }
        }

        function money(n) { return 'RM ' + n.toFixed(2); }

        function makeButton(text, label, className, onClick) {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = text;
            b.className = className;
            b.setAttribute('aria-label', label);
            b.addEventListener('click', onClick);
            return b;
        }

        function render() {
            const cart = getCart();
            const list = document.getElementById('cartItems');
            const empty = document.getElementById('cartEmpty');
            const content = document.getElementById('cartContent');
            const badge = document.getElementById('cartCount');

            const count = cart.reduce((s, i) => s + i.qty, 0);
            const total = cart.reduce((s, i) => s + i.price * i.qty, 0);

            if (badge) { badge.textContent = count; badge.hidden = count === 0; }
            empty.hidden = cart.length > 0;
            content.hidden = cart.length === 0;

            list.replaceChildren();

            cart.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'cart-item';

                const info = document.createElement('div');
                info.className = 'item-info';
                const name = document.createElement('strong');
                name.textContent = item.name;
                const unit = document.createElement('span');
                unit.textContent = money(item.price) + ' each';
                info.append(name, unit);

                const qty = document.createElement('div');
                qty.className = 'qty';
                const num = document.createElement('span');
                num.className = 'qty-num';
                num.textContent = item.qty;
                qty.append(
                    makeButton('−', 'Decrease quantity of ' + item.name, 'qty-btn', () => changeQty(index, -1)),
                    num,
                    makeButton('+', 'Increase quantity of ' + item.name, 'qty-btn', () => changeQty(index, 1))
                );

                const line = document.createElement('div');
                line.className = 'line-total';
                line.textContent = money(item.price * item.qty);

                const remove = makeButton('Remove', 'Remove ' + item.name + ' from cart', 'remove-btn', () => removeItem(index));

                li.append(info, qty, line, remove);
                list.appendChild(li);
            });

            document.getElementById('itemCount').textContent = count;
            document.getElementById('cartTotal').textContent = money(total);
        }

        function changeQty(index, delta) {
            const cart = getCart();
            if (!cart[index]) return;
            cart[index].qty += delta;
            if (cart[index].qty < 1) cart.splice(index, 1);
            saveCart(cart);
            render();
        }

        function removeItem(index) {
            const cart = getCart();
            cart.splice(index, 1);
            saveCart(cart);
            render();
        }

        document.getElementById('clearCart').addEventListener('click', () => {
            if (confirm('Remove everything from your cart?')) {
                saveCart([]);
                render();
            }
        });

        render();

        document.querySelectorAll('.nav-logout').forEach(function (btn) {
            btn.addEventListener('click', function () {
                localStorage.removeItem('bitesnowCart');
            });
        });
    </script>
</body>
</html>