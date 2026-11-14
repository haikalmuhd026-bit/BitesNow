<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? 'Account');
$userRole   = $_SESSION['role'] ?? 'customer';

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

$ratingsSummary = [];
$recentReviews  = [];

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // 1. Calculate average rating and total counts per item
    $sqlSummary = "SELECT item_id, 
                          ROUND(AVG(rating), 1) AS avg_rating, 
                          COUNT(id) AS total_reviews 
                   FROM product_reviews 
                   GROUP BY item_id";
    $resSummary = $conn->query($sqlSummary);
    if ($resSummary) {
        while ($row = $resSummary->fetch_assoc()) {
            $ratingsSummary[$row['item_id']] = [
                'avg'   => (float)$row['avg_rating'],
                'count' => (int)$row['total_reviews']
            ];
        }
    }

    // 2. Fetch the latest customer reviews
    $sqlReviews = "SELECT customer_name, item_id, rating, comment, created_at 
                   FROM product_reviews 
                   ORDER BY created_at DESC 
                   LIMIT 6";
    $resReviews = $conn->query($sqlReviews);
    if ($resReviews) {
        $recentReviews = $resReviews->fetch_all(MYSQLI_ASSOC);
    }

    $conn->close();
} catch (Exception $e) {
    error_log("Recom DB Error: " . $e->getMessage());
}

// Recommended Menu Items
$recommendedItems = [
    ['id' => 'laksa', 'name' => 'Laksa Mee', 'price' => 13, 'image' => 'images/Laksa.jpg', 'badge' => 'Chef Special'],
    ['id' => 'pandan-chicken', 'name' => 'Pandan Chicken', 'price' => 12, 'image' => 'images/Pandan Chicken.jpg', 'badge' => 'Top Seller'],
    ['id' => 'fried-rice', 'name' => 'Fried Rice', 'price' => 12, 'image' => 'images/fried-rice.jpg', 'badge' => 'Popular'],
    ['id' => 'strawberry-falooda', 'name' => 'Strawberry Falooda', 'price' => 8, 'image' => 'images/strawberry-falooda.jpg', 'badge' => 'Customer Favorite']
];

$itemLabels = [
    'laksa'              => 'Laksa Mee',
    'pandan-chicken'     => 'Pandan Chicken',
    'fried-rice'         => 'Fried Rice',
    'fried-tofu'         => 'Fried Tofu',
    'coffee'             => 'Coffee',
    'milk'               => 'Milk',
    'tea'                => 'Tea',
    'strawberry-falooda' => 'Strawberry Falooda'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=11">
    <style>
        .rating-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 6px 0 12px;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .rating-link:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(250, 204, 21, 0.4);
        }
        .rating-link .star { color: #facc15; font-size: 0.95rem; }
        .rating-link .score { color: #ffffff; font-weight: 700; font-size: 0.88rem; }
        .rating-link .count { color: #9ca3af; font-size: 0.8rem; }
        
        .reviews-section {
            margin-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 40px;
        }
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        .review-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: left;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .reviewer-info strong { display: block; color: #ffffff; font-size: 0.95rem; }
        .reviewer-info span { display: block; color: #7CFC00; font-size: 0.8rem; margin-top: 2px; }
        .review-stars { color: #facc15; font-size: 0.9rem; letter-spacing: 1px; }
        .review-comment { color: #d1d5db; font-size: 0.9rem; line-height: 1.5; margin: 0; }
    </style>
</head>
<body>

    <!-- Standardized Master Header -->
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
        <!-- Success Confirmation Toast -->
        <?php if (isset($_GET['reviewed'])): ?>
            <div style="max-width: 1000px; margin: 20px auto 0; padding: 14px 20px; background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; border-radius: 8px; text-align: center; font-weight: 600;">
                ✓ Thank you! Your review has been recorded and updated below.
            </div>
        <?php endif; ?>

        <!-- Section 1: Recommended Dishes -->
        <section class="section">
            <span class="eyebrow" style="color: #7CFC00; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Chef Picks</span>
            <h1 class="page-title">Recommended For You</h1>
            <p class="menu-intro">Our most popular dishes, rated and approved by hungry diners.</p>

            <div class="cards menu-cards">
                <?php foreach ($recommendedItems as $item): ?>
                    <?php 
                        $ratingData = $ratingsSummary[$item['id']] ?? ['avg' => 5.0, 'count' => 0];
                        $avgScore   = $ratingData['avg'];
                        $revCount   = $ratingData['count'];
                    ?>
                    <div class="card" id="<?= htmlspecialchars($item['id']) ?>">
                        <div class="card-img-wrap">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>

                            <!-- Clickable Star Rating -->
                            <a href="product_review.php?item=<?= urlencode($item['id']) ?>" 
                               class="rating-link" 
                               title="Read customer reviews for <?= htmlspecialchars($item['name']) ?>">
                                <span class="star">★</span>
                                <span class="score"><?= number_format($avgScore, 1) ?></span>
                                <span class="count">(<?= $revCount ?> <?= $revCount === 1 ? 'review' : 'reviews' ?>)</span>
                            </a>

                            <p class="price">RM <?= number_format($item['price'], 2) ?></p>

                            <button class="add-btn" 
                                    data-name="<?= htmlspecialchars($item['name']) ?>" 
                                    data-price="<?= htmlspecialchars((string)$item['price']) ?>">
                                Add to cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 2: Real Customer Feedback -->
        <section class="section reviews-section" aria-labelledby="reviewsTitle">
            <div class="reviews-top">
                <h2 id="reviewsTitle" style="margin-bottom: 6px; font-size: 1.6rem; color: #ffffff;">Customer Reviews &amp; Ratings</h2>
                <p style="color: #9ca3af; margin: 0;">Verified feedback from community diners.</p>
            </div>

            <?php if (empty($recentReviews)): ?>
                <div style="padding: 40px 20px; text-align: center; color: #9c9c9c;">
                    <p style="font-size: 1.05rem; margin-bottom: 16px;">No customer reviews yet. Be the first to share your thoughts!</p>
                    <a href="product_review.php" class="cta-btn">Leave a Review</a>
                </div>
            <?php else: ?>
                <div class="reviews-grid">
                    <?php foreach ($recentReviews as $rev): 
                        $stars = max(1, min(5, (int)$rev['rating']));
                        $dishName = $itemLabels[$rev['item_id']] ?? ucwords(str_replace('-', ' ', $rev['item_id']));
                    ?>
                        <article class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <strong><?= htmlspecialchars($rev['customer_name'] ?? 'Verified Diner') ?></strong>
                                    <span>Ordered: <?= htmlspecialchars($dishName) ?></span>
                                </div>
                                <div class="review-stars" aria-label="<?= $stars ?> out of 5 stars">
                                    <?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?>
                                </div>
                            </div>
                            <p class="review-comment">"<?= nl2br(htmlspecialchars($rev['comment'])) ?>"</p>
                            <small style="color: #6b7280; display: block; margin-top: 10px; font-size: 0.78rem;">
                                <?= date('d M Y', strtotime($rev['created_at'])) ?>
                            </small>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin-top: 36px;">
                    <a href="product_review.php" class="cta-btn">Write a Review</a>
                </div>
            <?php endif; ?>
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
        const currentPage = location.pathname.split('/').pop() || 'recom.php';
        document.querySelectorAll('.nav-links a').forEach((link) => {
            if (link.getAttribute('href') === currentPage) {
                link.setAttribute('aria-current', 'page');
            }
        });

        // Cart localStorage handling
        const CART_KEY = 'bitesnowCart';
        function getCart() {
            try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
            catch (e) { return []; }
        }
        function saveCart(cart) {
            try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch (e) {}
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
                
                btn.textContent = 'Added \u2713';
                btn.disabled = true;
                setTimeout(() => { 
                    btn.textContent = 'Add to cart'; 
                    btn.disabled = false;
                }, 1000);
            });
        });

        updateBadge();

        document.querySelectorAll('.nav-logout').forEach((btn) => {
            btn.addEventListener('click', () => {
                localStorage.removeItem(CART_KEY);
            });
        });
    </script>
</body>
</html>