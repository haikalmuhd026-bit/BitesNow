<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn      = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$displayUserName = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$formUserName    = $_SESSION['user_name'] ?? '';
$userEmail       = $_SESSION['user_email'] ?? '';
$userRole        = $_SESSION['role'] ?? 'customer';

// Generate CSRF token for secure message submission
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Contact form submission status feedback
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <link rel="stylesheet" href="location.css?v=2">
    <style>
        .location-section {
            max-width: 1100px;
            margin: 40px auto 60px;
            padding: 0 20px;
            text-align: left;
        }
        .location-section h1 {
            font-size: 2.2rem;
            color: #ffffff;
            margin: 0 0 6px;
        }
        .location-section .menu-intro {
            color: #9ca3af;
            margin: 0 0 28px;
            font-size: 0.95rem;
        }
        .location-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 820px) {
            .location-grid {
                grid-template-columns: 1fr;
            }
        }
        .map-panel {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        .map-frame {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            background: #111827;
            height: 280px;
        }
        .map-frame iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        .panel {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }
        .panel h2 {
            margin-top: 0;
            color: #7CFC00;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-size: 1.35rem;
        }
        .store-info dl {
            margin: 0 0 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .store-info dl div {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .store-info dt {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            font-weight: 700;
        }
        .store-info dd {
            margin: 0;
            font-size: 0.98rem;
            color: #f3f4f6;
        }
        .store-info dd a {
            color: #7CFC00;
            text-decoration: none;
            font-weight: 600;
        }
        .store-info dd a:hover {
            text-decoration: underline;
        }
        .info-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .contact-form .field {
            margin-bottom: 18px;
        }
        .contact-form label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            color: #e5e7eb;
            font-weight: 600;
        }
        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 12px 14px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 6px;
            color: #ffffff;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.92rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .contact-form input:focus,
        .contact-form textarea:focus {
            outline: none;
            border-color: #7CFC00;
            box-shadow: 0 0 0 3px rgba(124, 252, 0, 0.2);
        }
        .contact-submit {
            width: 100%;
            margin-top: 6px;
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
                        <span class="nav-user">Hi, <?= htmlspecialchars($displayUserName) ?></span>
                        <a href="logout.php" class="nav-logout">Log out</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-login">Log in</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="section location-section" aria-labelledby="locTitle">
            <h1 id="locTitle">Find Us</h1>
            <p class="menu-intro">Come pick up your order, or send us a message.</p>

            <?php if ($status === 'success'): ?>
                <div class="notice success" role="alert" style="margin-bottom: 24px;">
                    ✓ Thank you! Your message has been sent successfully. We will get back to you shortly.
                </div>
            <?php elseif ($status === 'error'): ?>
                <div class="notice error" role="alert" style="margin-bottom: 24px;">
                    An error occurred while sending your message. Please verify your entries and try again.
                </div>
            <?php endif; ?>

            <div class="location-grid">

                <!-- Map + store info -->
                <div class="map-panel">
                    <div class="map-frame">
                        <iframe
                            title="Map showing the BitesNow store location"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15935.769295193042!2d101.57733077952776!3d3.109962490131466!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc4dcedcd010e3%3A0x34dd1560f8ddea56!2sEvolve%20Mall!5e0!3m2!1sen!2smy!4v1624949000209!5m2!1sen!2smy"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                    <div class="panel store-info">
                        <h2>Store Kitchen</h2>
                        <dl>
                            <div><dt>Address</dt><dd>Jalan PJU 1A/4A, Petaling Jaya, Selangor</dd></div>
                            <div><dt>Hours</dt><dd>Daily, 10:00 AM – 10:00 PM</dd></div>
                            <div><dt>Phone</dt><dd><a href="tel:+60172623084">+60 17-262 3084</a></dd></div>
                        </dl>
                        <div class="info-actions">
                            <a href="https://www.google.com/maps/search/?api=1&amp;query=Evolve%20Mall" target="_blank" rel="noopener noreferrer" class="cta-btn">Get Directions</a>
                            <a href="tel:+60172623084" class="tbl-btn">Call Us</a>
                        </div>
                    </div>
                </div>

                <!-- Contact form -->
                <div class="panel">
                    <h2>Send Us a Message</h2>

                    <form class="contact-form" method="post" action="contact.php">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="field">
                            <label for="name">Name</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   autocomplete="name" 
                                   placeholder="Your name"
                                   value="<?= htmlspecialchars($formUserName) ?>" 
                                   required>
                        </div>

                        <div class="field">
                            <label for="email">Email</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   autocomplete="email" 
                                   placeholder="you@example.com"
                                   value="<?= htmlspecialchars($userEmail) ?>" 
                                   required>
                        </div>

                        <div class="field">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="How can we help you?" required></textarea>
                        </div>

                        <button type="submit" class="cta-btn contact-submit">Send message</button>
                    </form>
                </div>

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
        const currentPage = location.pathname.split('/').pop() || 'location.php';
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

        // Wipe cart from browser on logout click
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>