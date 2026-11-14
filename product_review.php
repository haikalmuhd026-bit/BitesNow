<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
$userName   = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? '');
$userRole   = $_SESSION['role'] ?? 'customer';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash message status (?status=success or ?status=error)
$status = $_GET['status'] ?? '';

// Catch item passed from recom.php (?item=laksa)
$selectedItem = strtolower(trim($_GET['item'] ?? ''));

// Menu option lists
$foodOptions = [
    'laksa'          => 'Laksa Mee',
    'pandan-chicken' => 'Pandan Chicken',
    'fried-rice'     => 'Fried Rice',
    'fried-tofu'     => 'Fried Tofu'
];

$drinkOptions = [
    'coffee'             => 'Coffee',
    'tea'                => 'Tea',
    'milk'               => 'Milk',
    'strawberry-falooda' => 'Strawberry Falooda',
    'none'               => 'None / No Drink'
];

// If ?item= matches a drink (like strawberry-falooda), set it as selected drink
$preselectedFood  = isset($foodOptions[$selectedItem]) ? $selectedItem : '';
$preselectedDrink = isset($drinkOptions[$selectedItem]) ? $selectedItem : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings &amp; Reviews | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=11">
    <link rel="stylesheet" href="product_review.css?v=2">
    <style>
        .review-section {
            max-width: 780px;
            margin: 40px auto 60px;
            padding: 0 20px;
            text-align: left;
        }
        .review-form {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        }
        .review-section h1 {
            font-size: 2.2rem;
            color: #ffffff;
            margin: 0 0 8px;
            text-align: center;
        }
        .review-section .menu-intro {
            color: #9ca3af;
            text-align: center;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }
        .field {
            margin-bottom: 18px;
        }
        .field label, fieldset legend {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            color: #e5e7eb;
            font-weight: 600;
        }
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .field-row {
                grid-template-columns: 1fr;
            }
        }
        .field input[type="text"],
        .field select,
        .field textarea {
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
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #7CFC00;
            box-shadow: 0 0 0 3px rgba(124, 252, 0, 0.2);
        }
        .field select option {
            background: #111827;
            color: #ffffff;
        }
        /* Star Rating Controls */
        .star-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 6px;
            font-size: 1.8rem;
        }
        .star-input input {
            display: none;
        }
        .star-input label {
            color: #4b5563;
            cursor: pointer;
            transition: color 0.15s ease;
            margin: 0;
        }
        .star-input label:hover,
        .star-input label:hover ~ label,
        .star-input input:checked ~ label {
            color: #facc15;
        }
        /* Radio Choice Buttons */
        .choice-group {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .choice {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1f2937;
            padding: 8px 14px;
            border: 1px solid #374151;
            border-radius: 6px;
            cursor: pointer;
            color: #d1d5db;
            font-size: 0.88rem;
            transition: border-color 0.2s ease;
        }
        .choice:hover {
            border-color: #7CFC00;
        }
        .choice input[type="radio"] {
            accent-color: #7CFC00;
        }
        .req {
            color: #ef4444;
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
        }
        .form-note {
            font-size: 0.78rem;
            color: #9ca3af;
            margin-top: 10px;
            text-align: center;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
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

                    <li><a href="product_review.php" style="color: #7CFC00;">Ratings</a></li>
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
        <section class="section review-section" aria-labelledby="formTitle">
            <h1 id="formTitle">Rate Your Order</h1>
            <p class="menu-intro">Tell us what you ate. Your feedback helps other customers choose.</p>

            <!-- Submission Feedback Alerts -->
            <?php if ($status === 'success'): ?>
                <div class="notice success" role="alert" style="margin-bottom: 24px;">
                    Thank you! Your review has been submitted successfully and updated on our menu.
                </div>
            <?php elseif ($status === 'error'): ?>
                <div class="notice error" role="alert" style="margin-bottom: 24px;">
                    An error occurred while submitting your review. Please make sure all required fields are filled.
                </div>
            <?php endif; ?>

            <form class="review-form" method="post" action="submit_review.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="field">
                    <label for="reviewer">Your name <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" 
                           id="reviewer" 
                           name="reviewer" 
                           autocomplete="name" 
                           placeholder="e.g. Aisha" 
                           value="<?= htmlspecialchars($userName) ?>" 
                           required>
                </div>

                <div class="field-row">
                    <!-- Food Dropdown (Pre-selects dish from recom.php link) -->
                    <div class="field">
                        <label for="product">Food you ordered <span class="req" aria-hidden="true">*</span></label>
                        <select id="product" name="product" required>
                            <option value="" disabled <?= empty($preselectedFood) ? 'selected' : '' ?>>Choose a dish</option>
                            <?php foreach ($foodOptions as $key => $name): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= ($preselectedFood === $key) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Drink Dropdown -->
                    <div class="field">
                        <label for="drink">Drink</label>
                        <select id="drink" name="drink">
                            <option value="" <?= empty($preselectedDrink) ? 'selected' : '' ?>>None / No Drink</option>
                            <?php foreach ($drinkOptions as $dKey => $dName): ?>
                                <option value="<?= htmlspecialchars($dKey) ?>" <?= ($preselectedDrink === $dKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <fieldset class="field" style="border: none; padding: 0; margin: 0 0 18px 0;">
                    <legend>Your rating <span class="req" aria-hidden="true">*</span></legend>
                    <div class="star-input">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5" title="5 stars"><span class="sr-only">5 stars</span>★</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4" title="4 stars"><span class="sr-only">4 stars</span>★</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3" title="3 stars"><span class="sr-only">3 stars</span>★</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2" title="2 stars"><span class="sr-only">2 stars</span>★</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1" title="1 star"><span class="sr-only">1 star</span>★</label>
                    </div>
                </fieldset>

                <div class="field-row">
                    <div class="field">
                        <label for="pros">What was good?</label>
                        <textarea id="pros" name="pros" rows="3" placeholder="Tasty, arrived hot..."></textarea>
                    </div>
                    <div class="field">
                        <label for="cons">What could be better?</label>
                        <textarea id="cons" name="cons" rows="3" placeholder="Portion size, delivery time..."></textarea>
                    </div>
                </div>

                <div class="field">
                    <label for="review">Your review <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="review" name="review" rows="5" required placeholder="Share your experience..."></textarea>
                </div>

                <fieldset class="field" style="border: none; padding: 0; margin: 0 0 18px 0;">
                    <legend>Would you recommend it to others?</legend>
                    <div class="choice-group">
                        <label class="choice"><input type="radio" name="recommend" value="Yes" checked> <span>Yes</span></label>
                        <label class="choice"><input type="radio" name="recommend" value="No"> <span>No</span></label>
                        <label class="choice"><input type="radio" name="recommend" value="Not sure"> <span>Not sure</span></label>
                    </div>
                </fieldset>

                <button type="submit" class="cta-btn submit-btn">Submit review</button>
                <p class="form-note"><span class="req">*</span> Required fields</p>
            </form>
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
                toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            });
        }

        // Cart localStorage badge
        const CART_KEY = 'bitesnowCart';
        function getCart() {
            try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
            catch (e) { return []; }
        }
        const badge = document.getElementById('cartCount');
        const total = getCart().reduce((sum, item) => sum + item.qty, 0);
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