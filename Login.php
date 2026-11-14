<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Sanitize redirect destination (prevent open redirects)
$rawRedirect = basename(trim($_GET['redirect'] ?? ''));
$allowedRedirects = ['index.php', 'food.php', 'recom.php', 'checkout.php', 'my_orders.php', 'cart.php', 'product_review.php', 'admin_orders.php'];
$redirect = in_array($rawRedirect, $allowedRedirects, true) ? $rawRedirect : '';

if (empty($redirect) && ($_GET['status'] ?? '') === 'login_required') {
    $redirect = 'checkout.php';
}

// 2. Redirect if already logged in (routes admins directly to kitchen queue)
if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
    $defaultDest = (($_SESSION['role'] ?? '') === 'admin') ? 'admin_orders.php' : 'index.php';
    header('Location: ' . (!empty($redirect) ? $redirect : $defaultDest));
    exit;
}

// 3. Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Server-side flash messages
$messages = [
    'invalid'        => ['type' => 'error',   'text' => 'Incorrect username or password.'],
    'exists'         => ['type' => 'error',   'text' => 'That username is already taken. Please choose another.'],
    'mismatch'       => ['type' => 'error',   'text' => 'The passwords did not match.'],
    'csrf'           => ['type' => 'error',   'text' => 'Security session expired. Please try submitting again.'],
    'error'          => ['type' => 'error',   'text' => 'Something went wrong. Please check your details and try again.'],
    'registered'     => ['type' => 'success', 'text' => 'Account created successfully. You can log in now.'],
    'loggedout'      => ['type' => 'success', 'text' => 'You have been logged out.'],
    'login_required' => ['type' => 'error',   'text' => 'Please log in or create an account to proceed with your order.'],
    'admin_required' => ['type' => 'error',   'text' => 'Administrator access required. Please log in with an admin account.']
];
$status = $_GET['status'] ?? '';
$activeNotice = $messages[$status] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in or Register | BitesNow Food Delivery</title>
    <link rel="icon" href="images/BitesNow.png">
    <link rel="stylesheet" href="styles.css?v=12">
    <link rel="stylesheet" href="login.css?v=3">
    <style>
        [hidden] {
            display: none !important;
        }
        .auth-brand {
            background: rgba(17, 24, 39, 0.95);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 14px 20px;
            backdrop-filter: blur(8px);
        }
        .auth-brand-inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .auth-brand-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .auth-brand-link img {
            height: 40px;
            width: auto;
            display: block;
        }
        .back-home-link {
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .back-home-link:hover {
            color: #7CFC00;
        }
        .auth-main {
            max-width: 960px;
            margin: 40px auto 60px;
            padding: 0 20px;
            width: 100%;
        }
        .auth-title {
            text-align: center;
            font-size: 2.2rem;
            color: #ffffff;
            margin: 0 0 6px;
        }
        .auth-intro {
            text-align: center;
            color: #9ca3af;
            margin: 0 0 28px;
            font-size: 0.95rem;
        }
        .auth-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 780px) {
            .auth-wrap {
                grid-template-columns: 1fr;
            }
        }
        .auth-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
            text-align: left;
        }
        .auth-card h2 {
            margin-top: 0;
            color: #7CFC00;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-size: 1.35rem;
        }
        .auth-card .field {
            margin-bottom: 18px;
        }
        .auth-card label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            color: #e5e7eb;
            font-weight: 600;
        }
        .auth-card input[type="text"],
        .auth-card input[type="email"],
        .auth-card input[type="password"] {
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
        .auth-card input:focus {
            outline: none;
            border-color: #7CFC00;
            box-shadow: 0 0 0 3px rgba(124, 252, 0, 0.2);
        }
        .pw-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .pw-wrap input {
            padding-right: 64px !important;
        }
        .pw-toggle {
            position: absolute;
            right: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #d1d5db;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .pw-toggle:hover {
            border-color: #7CFC00;
            color: #7CFC00;
        }
        .hint {
            display: block;
            font-size: 0.78rem;
            color: #9ca3af;
            margin-top: 5px;
        }
        .auth-btn {
            width: 100%;
            margin-top: 6px;
            padding: 13px;
        }
        .terms {
            font-size: 0.78rem;
            color: #9ca3af;
            text-align: center;
            margin: 12px 0 0;
        }
    </style>
</head>
<body class="auth-page">

    <header class="auth-brand">
        <div class="auth-brand-inner">
            <a href="index.php" class="auth-brand-link">
                <img src="images/BitesNow.png" alt="BitesNow Logo" height="40" style="height: 40px; width: auto;">
                <span>BitesNow</span>
            </a>
            <a href="index.php" class="back-home-link">&larr; Back to Home</a>
        </div>
    </header>

    <main class="auth-main">
        <h1 class="auth-title">Welcome</h1>
        <p class="auth-intro">Log in to order, or create a new account.</p>

        <!-- Flash messages -->
        <?php if ($activeNotice): ?>
            <div id="notice" class="notice <?= htmlspecialchars($activeNotice['type']) ?>" role="status" style="max-width: 960px; margin: 0 auto 24px;">
                <?= htmlspecialchars($activeNotice['text']) ?>
            </div>
        <?php else: ?>
            <div id="notice" class="notice" role="status" hidden></div>
        <?php endif; ?>

        <div class="auth-wrap">

            <!-- Login Card -->
            <section class="auth-card" aria-labelledby="loginTitle">
                <h2 id="loginTitle">Log in</h2>
                <form action="validation.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <div class="field">
                        <label for="login-user">Username</label>
                        <input type="text" id="login-user" name="user" autocomplete="username" required>
                    </div>

                    <div class="field">
                        <label for="login-password">Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                            <button type="button" class="pw-toggle" data-target="login-password" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="cta-btn auth-btn">Log in</button>
                </form>
            </section>

            <!-- Register Card -->
            <section class="auth-card" aria-labelledby="registerTitle">
                <h2 id="registerTitle">Create an account</h2>
                <form action="registration.php" method="post" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <div class="field">
                        <label for="reg-user">Username</label>
                        <input type="text" id="reg-user" name="user" autocomplete="username"
                               minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]+"
                               title="3 to 30 characters: letters, numbers, dot, dash or underscore" required>
                    </div>

                    <div class="field">
                        <label for="reg-email">Email</label>
                        <input type="email" id="reg-email" name="email" autocomplete="email" placeholder="name@example.com" required>
                    </div>

                    <div class="field">
                        <label for="reg-password">Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="reg-password" name="password" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="pw-toggle" data-target="reg-password" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                        <small class="hint">At least 8 characters.</small>
                    </div>

                    <div class="field">
                        <label for="reg-confirm">Confirm password</label>
                        <div class="pw-wrap">
                            <input type="password" id="reg-confirm" name="password_confirm" autocomplete="new-password" required>
                            <button type="button" class="pw-toggle" data-target="reg-confirm" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="cta-btn auth-btn">Register</button>
                    <p class="terms">By creating an account you agree to our Terms &amp; Privacy.</p>
                </form>
            </section>

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
        // Show / hide password toggle
        document.querySelectorAll('.pw-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Hide' : 'Show';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.setAttribute('aria-pressed', String(show));
            });
        });

        // Passwords match validation
        const pw = document.getElementById('reg-password');
        const confirmPw = document.getElementById('reg-confirm');
        function checkMatch() {
            confirmPw.setCustomValidity(
                confirmPw.value && confirmPw.value !== pw.value ? 'Passwords do not match' : ''
            );
        }
        pw.addEventListener('input', checkMatch);
        confirmPw.addEventListener('input', checkMatch);

        // Failsafe: clear cart if redirected here after logout
        if (new URLSearchParams(window.location.search).get('status') === 'loggedout') {
            localStorage.removeItem('bitesnowCart');
        }
    </script>
</body>
</html>