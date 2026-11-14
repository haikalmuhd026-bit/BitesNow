<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_review.php');
    exit;
}

// 1. Verify CSRF
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: product_review.php?status=error');
    exit;
}

// 2. Extract and Sanitize Form Inputs
$reviewer  = trim($_POST['reviewer'] ?? 'Anonymous');
$itemId    = trim($_POST['product'] ?? '');
$rating    = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$reviewTxt = trim($_POST['review'] ?? '');
$pros      = trim($_POST['pros'] ?? '');
$cons      = trim($_POST['cons'] ?? '');
$recommend = trim($_POST['recommend'] ?? 'Yes');
$userId    = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (empty($itemId) || empty($reviewTxt)) {
    header('Location: product_review.php?status=error');
    exit;
}

// Combine review text with pros and cons if provided
$fullComment = $reviewTxt;
if (!empty($pros)) {
    $fullComment .= "\n[Pros: " . $pros . "]";
}
if (!empty($cons)) {
    $fullComment .= "\n[Cons: " . $cons . "]";
}

// 3. Insert into Database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bitesnow';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare("INSERT INTO product_reviews (user_id, customer_name, item_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $userId, $reviewer, $itemId, $rating, $fullComment);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redirect to recom.php with success flag
    header('Location: recom.php?reviewed=1');
    exit;

} catch (Exception $e) {
    error_log("Review insertion failed: " . $e->getMessage());
    header('Location: product_review.php?status=error');
    exit;
}