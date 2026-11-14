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
    <title>About Us | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <link rel="stylesheet" href="AboutUs.css?v=2">
    <style>
        .about-section {
            max-width: 1000px;
            margin: 40px auto 60px;
            padding: 0 20px;
            text-align: left;
        }
        .about-section h1 {
            font-size: 2.2rem;
            color: #ffffff;
            margin: 0 0 6px;
        }
        .about-section .menu-intro {
            color: #9ca3af;
            margin: 0 0 30px;
            font-size: 0.95rem;
        }
        .about-story {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 32px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            margin-bottom: 28px;
        }
        @media (max-width: 720px) {
            .about-story {
                flex-direction: column;
                text-align: center;
                padding: 24px 20px;
            }
        }
        .about-photo.logo-photo {
            width: 180px;
            height: 180px;
            object-fit: contain;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px;
            flex-shrink: 0;
        }
        .about-text h2 {
            margin-top: 0;
            color: #7CFC00;
            font-size: 1.45rem;
            margin-bottom: 12px;
        }
        .about-text p {
            color: #d1d5db;
            font-size: 0.98rem;
            line-height: 1.65;
            margin: 0 0 14px;
        }
        .about-text p:last-child {
            margin-bottom: 0;
        }
        .about-facts {
            list-style: none;
            margin: 0 0 28px;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 680px) {
            .about-facts {
                grid-template-columns: 1fr;
            }
        }
        .about-facts li {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 22px 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
        }
        .about-facts li strong {
            display: block;
            font-size: 1.45rem;
            color: #7CFC00;
            margin-bottom: 4px;
        }
        .about-facts li span {
            font-size: 0.88rem;
            color: #9ca3af;
        }
        .about-panel {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }
        .about-panel h2 {
            margin-top: 0;
            color: #7CFC00;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-size: 1.35rem;
        }
        .contact-list {
            list-style: none;
            margin: 0 0 24px;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .contact-list li {
            display: flex;
            align-items: baseline;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .contact-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        @media (max-width: 600px) {
            .contact-list li {
                flex-direction: column;
                gap: 4px;
            }
        }
        .contact-list .label {
            min-width: 100px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            font-weight: 700;
        }
        .contact-list a {
            color: #7CFC00;
            text-decoration: none;
            font-weight: 600;
        }
        .contact-list a:hover {
            text-decoration: underline;
        }
        .contact-list span:not(.label) {
            color: #e5e7eb;
            font-size: 0.95rem;
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

    <main>
        <section class="section about-section" aria-labelledby="aboutTitle">
            <h1 id="aboutTitle">About Us</h1>
            <p class="menu-intro">Malaysian comfort food, made fresh and delivered to you.</p>

            <!-- Story -->
            <div class="about-story">
                <img class="about-photo logo-photo" src="images/BitesNow.png" alt="BitesNow logo" width="180" height="180" loading="lazy">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>
                        Founded in 2019, our humble kitchen serves delicious Malaysian cuisine such as
                        laksa mee and fried rice, as well as drinks and desserts such as coffee, milk
                        and our very special Strawberry Falooda.
                    </p>
                    <p>
                        Located in the Klang Valley, we provide tasty food and drinks at an attractive
                        price, all under RM30.00.
                    </p>
                </div>
            </div>

            <!-- Quick facts -->
            <ul class="about-facts">
                <li><strong>2019</strong><span>Year we started</span></li>
                <li><strong>Klang Valley</strong><span>Where we serve</span></li>
                <li><strong>Under RM30</strong><span>For a full meal</span></li>
            </ul>

            <!-- Contacts -->
            <div class="about-panel">
                <h2>Our Contacts</h2>
                <ul class="contact-list">
                    <li>
                        <span class="label">Facebook</span>
                        <a href="https://www.facebook.com/BitesNow" target="_blank" rel="noopener noreferrer">facebook.com/BitesNow</a>
                    </li>
                    <li>
                        <span class="label">Instagram</span>
                        <a href="https://www.instagram.com/BitesNow" target="_blank" rel="noopener noreferrer">instagram.com/BitesNow</a>
                    </li>
                    <li>
                        <span class="label">Email</span>
                        <a href="mailto:BitesNow@gmail.com">BitesNow@gmail.com</a>
                    </li>
                    <li>
                        <span class="label">Phone</span>
                        <a href="tel:+60172623084">+60 17-262 3084</a>
                    </li>
                    <li>
                        <span class="label">Address</span>
                        <span>Block G, 5, G-8, Jalan PJU 1A/4A, Kelana Idaman, 47301 Petaling Jaya, Selangor</span>
                    </li>
                </ul>
                <a href="location.php" class="cta-btn">See map &amp; directions</a>
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
                toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            });
        }

        // Highlight the current page link
        const currentPage = location.pathname.split('/').pop() || 'AboutUs.php';
        document.querySelectorAll('.nav-links a, .nav-cart').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Cart badge
        const CART_KEY = 'bitesnowCart';
        function getCart() {
            try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
            catch (e) { return []; }
        }
        const badge = document.getElementById('cartCount');
        const total = getCart().reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
        if (badge) { 
            badge.textContent = total; 
            badge.hidden = total === 0; 
        }

        // Wipe cart on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>