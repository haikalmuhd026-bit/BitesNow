<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId     = (int)($_SESSION['user_id'] ?? 0);
$isLoggedIn = $userId > 0 || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';

$menuItems = [
    ['id' => 'laksa', 'name' => 'Laksa Mee', 'price' => 13.00, 'image' => 'images/Laksa.jpg'],
    ['id' => 'pandan-chicken', 'name' => 'Pandan Chicken', 'price' => 12.00, 'image' => 'images/Pandan Chicken.jpg'],
    ['id' => 'fried-rice', 'name' => 'Fried Rice', 'price' => 12.00, 'image' => 'images/fried-rice.jpg'],
    ['id' => 'fried-tofu', 'name' => 'Fried Tofu', 'price' => 9.00, 'image' => 'images/fried-tofu.jpg'],
    ['id' => 'coffee', 'name' => 'Coffee', 'price' => 8.00, 'image' => 'images/Coffee.jpg'],
    ['id' => 'milk', 'name' => 'Milk', 'price' => 4.00, 'image' => 'images/Milk.jpg'],
    ['id' => 'tea', 'name' => 'Tea', 'price' => 8.00, 'image' => 'images/tea.jpg'],
    ['id' => 'strawberry-falooda', 'name' => 'Strawberry Falooda', 'price' => 8.00, 'image' => 'images/strawberry-falooda.jpg']
];
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" type="text/css" href="styles.css?v=4">
    <link rel="stylesheet" type="text/css" href="styles.css?v=10">
</head>
<body>

    <!-- Header / Navigation -->
    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="images/BitesNow.png" alt="BitesNow Logo">
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

    <main class="page-container">
        <!-- Menu Dishes Grid -->
        <section class="section">
            <h1 class="page-title">Our Menu</h1>
            <p class="menu-intro">Freshly made and delivered hot to your door.</p>

            <div class="cards menu-cards">
                <?php foreach ($menuItems as $item): ?>
                    <div class="card" id="<?= htmlspecialchars($item['id']) ?>">
                        <div class="card-img-wrap">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="price">RM <?= number_format($item['price'], 2) ?></p>
                            <button class="add-btn" 
                                    data-id="<?= htmlspecialchars($item['id']) ?>"
                                    data-name="<?= htmlspecialchars($item['name']) ?>" 
                                    data-price="<?= htmlspecialchars((string)$item['price']) ?>">
                                Add to cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Customer Support Directory (Card Grid) -->
        <section class="section support-section" aria-labelledby="supportTitle">
            <div class="support-header">
                <div class="support-title-group">
                    <h2 id="supportTitle">Customer Support Directory</h2>
                    <p class="support-subtext">Find the right channel for order updates, dietary inquiries, or kitchen visits.</p>
                </div>
                <div class="live-status-pill" data-state="open">
                    <span class="status-pulse" aria-hidden="true"></span>
                    <span>Kitchen open · 10:00 AM – 10:00 PM daily</span>
                </div>
            </div>

            <div class="support-grid">
                <!-- WhatsApp -->
                <article class="support-card highlight">
                    <div class="card-badge live">Instant Reply</div>
                    <div class="card-icon-wrap icon-wa">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        </svg>
                    </div>
                    <h3>WhatsApp Chat</h3>
                    <p class="card-desc">Fastest for ongoing deliveries &amp; live rider tracking.</p>
                    <p class="card-detail">+60 17-262 3084</p>
                    <a href="https://wa.me/60172623084" target="_blank" rel="noopener noreferrer" class="support-btn btn-primary" aria-label="Chat with BitesNow on WhatsApp">Chat Now</a>
                </article>

                <!-- Phone Dispatch -->
                <article class="support-card">
                    <div class="card-badge">Daily 10 AM – 10 PM</div>
                    <div class="card-icon-wrap">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <h3>Phone Dispatch</h3>
                    <p class="card-desc">Direct line to our kitchen floor for urgent edits.</p>
                    <p class="card-detail">+60 17-262 3084</p>
                    <a href="tel:+60172623084" class="support-btn" aria-label="Call BitesNow kitchen hotline">Call Us</a>
                </article>

                <!-- Email -->
                <article class="support-card">
                    <div class="card-badge">Replies &lt; 24h</div>
                    <div class="card-icon-wrap">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <h3>Email Inquiries</h3>
                    <p class="card-desc">Catering, bulk orders, and business partnerships.</p>
                    <p class="card-detail">BitesNow@gmail.com</p>
                    <a href="mailto:BitesNow@gmail.com?subject=Catering%20Enquiry" class="support-btn" aria-label="Send an email inquiry">Send Email</a>
                </article>

                <!-- Kitchen Pickup -->
                <article class="support-card">
                    <div class="card-badge">Self Pickup</div>
                    <div class="card-icon-wrap">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h3>Store Kitchen</h3>
                    <p class="card-desc">Kelana Idaman pickup counter &amp; takeaway.</p>
                    <p class="card-detail truncate">Jalan PJU 1A/4A, PJ</p>
                    <a href="location.php" class="support-btn" aria-label="View kitchen location and directions">Directions</a>
                </article>
            </div>
        </section>
    </main>

    <!-- Footer -->
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

        // Highlight current nav item
        const currentPage = location.pathname.split('/').pop() || 'food.php';
        document.querySelectorAll('.nav-links a').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Cart localStorage handling
        const CART_KEY = 'bitesnowCart';

        function getCart() {
            try { 
                const raw = JSON.parse(localStorage.getItem(CART_KEY));
                return Array.isArray(raw) ? raw : [];
            } catch (e) { 
                return []; 
            }
        }

        function saveCart(cart) {
            try { 
                localStorage.setItem(CART_KEY, JSON.stringify(cart)); 
            } catch (e) { 
                console.error('Could not save cart', e); 
            }
        }

        function updateBadge() {
            const total = getCart().reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = total;
                badge.hidden = total === 0;
            }
        }

        document.querySelectorAll('.add-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const cart = getCart();
                const name = btn.dataset.name;
                const price = parseFloat(btn.dataset.price);
                const item = cart.find(i => i.name === name);
                
                if (item) { 
                    item.qty = (Number(item.qty) || 0) + 1; 
                } else { 
                    cart.push({ name, price, qty: 1 }); 
                }
                
                saveCart(cart);
                updateBadge();
                
                // Button feedback animation
                btn.textContent = 'Added \u2713';
                btn.disabled = true;
                setTimeout(() => { 
                    btn.textContent = 'Add to cart'; 
                    btn.disabled = false;
                }, 1000);
            });
        });

        // Auto-scroll to item if passed in URL query param (?item=laksa)
        const urlParams = new URLSearchParams(window.location.search);
        const targetItem = urlParams.get('item');
        if (targetItem) {
            const el = document.getElementById(targetItem);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.outline = '2px solid #7CFC00';
                setTimeout(() => { el.style.outline = 'none'; }, 2000);
            }
        }

        updateBadge();

        // Clear cart on logout
        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>