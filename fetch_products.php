<?php
session_start();
require_once "db.php";

// Check user login
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit("Please login first.");
}

// Initialize cart
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Get category and page
$category_id = isset($_GET["category"])
    ? max(0, (int)$_GET["category"])
    : 0;

$page = isset($_GET["page"])
    ? max(1, (int)$_GET["page"])
    : 1;

// Pagination settings
$limit = 4;
$offset = ($page - 1) * $limit; // અહીં - (minus) સેટ કરી દીધું છે

// Count products
if ($category_id > 0) {
    $countStmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM products
         WHERE category_id = ?"
    );

    if (!$countStmt) {
        http_response_code(500);
        exit("Database query failed.");
    }

    $countStmt->bind_param("i", $category_id);
    $countStmt->execute();

    $countResult = $countStmt->get_result();
    $total = (int)$countResult->fetch_assoc()["total"];

    $countStmt->close();
} else {
    $countResult = $conn->query(
        "SELECT COUNT(*) AS total FROM products"
    );

    if (!$countResult) {
        http_response_code(500);
        exit("Could not count products.");
    }

    $total = (int)$countResult->fetch_assoc()["total"];
}

// Calculate total pages
$totalPages = max(1, (int)ceil($total / $limit));

// Fetch products
if ($category_id > 0) {
    $stmt = $conn->prepare(
        "SELECT products.*, categories.name AS category_name
         FROM products
         INNER JOIN categories
             ON products.category_id = categories.id
         WHERE products.category_id = ?
         ORDER BY products.id DESC
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Could not prepare product query.");
    }

    $stmt->bind_param("iii", $category_id, $limit, $offset);
} else {
    $stmt = $conn->prepare(
        "SELECT products.*, categories.name AS category_name
         FROM products
         INNER JOIN categories
             ON products.category_id = categories.id
         ORDER BY products.id DESC
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Could not prepare product query.");
    }

    $stmt->bind_param("ii", $limit, $offset);
}

// Execute query
if (!$stmt->execute()) {
    http_response_code(500);
    $stmt->close();
    exit("Could not load products.");
}

$products = $stmt->get_result();

// Display products
if ($products && $products->num_rows > 0) {

    while ($product = $products->fetch_assoc()) {

        echo '<div class="product-card">';

        echo '<h3>' . htmlspecialchars($product["name"]) . '</h3>';

        echo '<p>Category: ' . htmlspecialchars($product["category_name"]) . '</p>';

        echo '<p class="product-price">Price: ₹' . number_format((float)$product["price"], 2) . '</p>';

        // Product image
        if (!empty($product["image"])) {
            echo '<img src="' . htmlspecialchars($product["image"]) . '" alt="Product image">';
        }

        // Add to Cart form
        echo '<form method="post" class="ajax-add-to-cart">';
        echo '<input type="hidden" name="product_id" value="' . (int)$product["id"] . '">';
        echo '<input type="hidden" name="category_id" value="' . $category_id . '">';
        echo '<input type="hidden" name="add_to_cart" value="1">';
        echo '<button type="submit">Add to Cart</button>';
        echo '</form>';

        echo '</div>';
    }

} else {
    echo '<p>No products found in this category.</p>';
}

// Pagination
if ($total > 0 && $totalPages > 1) {

    echo '<div class="pagination">';

    for ($i = 1; $i <= $totalPages; $i++) {

        echo '<button type="button" class="page-button" data-page="' . $i . '"';

        if ($i === $page) {
            echo ' disabled';
        }

        echo '>' . $i . '</button> ';
    }

    echo '</div>';
}

// Close statement
$stmt->close();
?>