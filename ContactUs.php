<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? '');
$userEmail  = $_SESSION['user_email'] ?? '';

// Generate CSRF token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=2">
    <link rel="stylesheet" href="ContactUs.css?v=2">
</head>
<body>

    <!-- Site Header & Navigation -->
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="images/BitesNow.png" alt="BitesNow Logo">
                <span>BitesNow</span>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
                <span></span><span></span><span></span>
            </button>

            <nav class="site-nav" id="siteNav">
                <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="food.php">Menu</a></li>
    <li><a href="recom.php">Recommended</a></li>
    
    <!-- Admin Kitchen Queue Link (Visible only to administrators) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li><a href="admin_orders.php" style="color: #f59e0b; font-weight: bold;">Kitchen Queue</a></li>
    <?php endif; ?>

    <?php if ($isLoggedIn): ?>
        <li><a href="my_orders.php">My Orders</a></li>
    <?php endif; ?>
    <li><a href="product_review.php">Ratings</a></li>
    <li><a href="location.php">Location</a></li>
    <li><a href="ContactUs.php">Contact Us</a></li>
    <li><a href="AboutUs.php">About Us</a></li>
</ul>
                <div class="nav-actions">
                    <a href="cart.php" class="nav-cart" aria-label="Cart">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    <main class="contact-wrap">
        <h1>Contact Us</h1>
        <p style="color: #9ca3af;">Questions about bulk catering, delivery areas, or your orders? Reach out to our team below.</p>

        <!-- Status Alerts -->
        <?php if ($status === 'success'): ?>
            <div class="alert-box alert-success" role="status">
                ✓ Thank you! Your inquiry has been sent successfully. Our team will get back to you via email shortly.
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="alert-box alert-error" role="alert">
                Submission failed. Please make sure you have filled in your name, a valid email address, and your message.
            </div>
        <?php endif; ?>

        <div class="contact-grid">
            <!-- Contact Details -->
            <section class="contact-card">
                <h2 style="color: #7CFC00; margin-top: 0;">Our Contacts</h2>
                <ul class="contact-list">
                    <li><strong>Facebook:</strong> <a href="https://facebook.com" target="_blank" rel="noopener">fb.com/BitesNowOfficial</a></li>
                    <li><strong>Instagram:</strong> <a href="https://instagram.com" target="_blank" rel="noopener">@BitesNowDelivery</a></li>
                    <li><strong>Business Email:</strong> <a href="mailto:BitesNow@gmail.com">BitesNow@gmail.com</a></li>
                    <li><strong>Contact Number:</strong> <a href="tel:+60172623084">017-262 3084</a></li>
                    <li><strong>Kitchen Address:</strong><br>Block G, 5, G-8, Jalan PJU 1a/4a, Kelana Damansara, 47301 Petaling Jaya, Selangor</li>
                </ul>
            </section>

            <!-- Active Form -->
            <section class="contact-card">
                <h2 style="margin-top: 0;">Send Us a Message</h2>
                <form action="contact.php" method="post">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-field">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" 
                               value="<?= htmlspecialchars($userName) ?>" 
                               placeholder="e.g. John Doe" required>
                    </div>

                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($userEmail) ?>" 
                               placeholder="e.g. john@example.com" required>
                    </div>

                    <div class="form-field">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Write your question, feedback, or catering inquiry here..." required></textarea>
                    </div>

                    <button type="submit" class="cta-btn" style="width: 100%;">Submit Inquiry</button>
                </form>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>Phone: <a href="tel:+60172623084">017-262 3084</a> | Email: <a href="mailto:BitesNow@gmail.com">BitesNow@gmail.com</a></p>
        <p>
            <a href="AboutUs.php">About Us</a> |
            <a href="location.php">Location</a> |
            <a href="ContactUs.php">Contact Us</a> |
            <a href="product_review.php">Ratings</a>
        </p>
        <p>&copy; 2026 BitesNow. All rights reserved.</p>
    </footer>

    <script>
        // Mobile toggle
        const toggle = document.getElementById('navToggle');
        const nav = document.getElementById('siteNav');
        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });
        }

        // Highlight current nav item
        const currentPage = location.pathname.split('/').pop() || 'ContactUs.php';
        document.querySelectorAll('.nav-links a').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Sync header cart count badge with localStorage
        const CART_KEY = 'bitesnowCart';
        try {
            const cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
            const count = cart.reduce((sum, item) => sum + (item.qty || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        } catch (e) {}

        // Logout cart clear
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>