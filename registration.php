<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_review.php');
    exit;
}

// 2. Strict CSRF Token Verification
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: product_review.php?status=error');
    exit;
}

// 3. Extract and sanitize inputs
$reviewer  = trim($_POST['reviewer'] ?? ($_POST['customer_name'] ?? ''));
$itemId    = trim($_POST['product'] ?? ($_POST['item_id'] ?? ''));
$drinkId   = trim($_POST['drink'] ?? '');
$rating    = (int)($_POST['rating'] ?? 0);
$reviewTxt = trim($_POST['review'] ?? ($_POST['comment'] ?? ''));
$pros      = trim($_POST['pros'] ?? '');
$cons      = trim($_POST['cons'] ?? '');
$recommend = trim($_POST['recommend'] ?? '');
$userId    = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (empty($reviewer)) {
    $reviewer = !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Customer';
}

// 4. Validate required fields and rating range (1–5)
if (empty($itemId) || empty($reviewTxt) || $rating < 1 || $rating > 5) {
    header('Location: product_review.php?status=error');
    exit;
}

// Map drink slugs to readable labels
$drinkLabels = [
    'coffee'             => 'Coffee',
    'tea'                => 'Tea',
    'milk'               => 'Milk',
    'strawberry-falooda' => 'Strawberry Falooda'
];

// Build consolidated comment
$fullComment = $reviewTxt;
if (!empty($drinkId) && $drinkId !== 'none') {
    $drinkName = $drinkLabels[$drinkId] ?? ucwords(str_replace('-', ' ', $drinkId));
    $fullComment .= "\n[Drink: " . $drinkName . "]";
}
if (!empty($pros))      $fullComment .= "\n[Pros: " . $pros . "]";
if (!empty($cons))      $fullComment .= "\n[Cons: " . $cons . "]";
if (!empty($recommend)) $fullComment .= "\n[Recommend: " . $recommend . "]";

// 5. Insert into Database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Ensure table exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS `product_reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `customer_name` VARCHAR(100) NOT NULL,
            `item_id` VARCHAR(50) NOT NULL,
            `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
            `comment` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Check if 'user_id' column exists in case table predates it
    $colCheck = $conn->query("SHOW COLUMNS FROM `product_reviews` LIKE 'user_id'");
    $hasUserIdCol = ($colCheck && $colCheck->num_rows > 0);

    if ($hasUserIdCol) {
        $stmt = $conn->prepare("INSERT INTO product_reviews (user_id, customer_name, item_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $userId, $reviewer, $itemId, $rating, $fullComment);
    } else {
        $stmt = $conn->prepare("INSERT INTO product_reviews (customer_name, item_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $reviewer, $itemId, $rating, $fullComment);
    }

    $stmt->execute();
    $stmt->close();
    $conn->close();

    unset($_SESSION['csrf_token']);

    // Redirect to Recommended page with success flag
    header('Location: recom.php?reviewed=1');
    exit;

} catch (Exception $e) {
    error_log("Submit Review DB Error: " . $e->getMessage());
    header('Location: product_review.php?status=error');
    exit;
}