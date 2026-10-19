<?php
session_start();
require_once "db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    // AJAX requests must receive JSON, not a login-page redirect
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["ajax_request"])
    ) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Session expired. Please log in again."
        ]);
        exit;
    }

    header("Location: login.php");
    exit;
}

// Initialize cart
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Add product to cart
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    (
        isset($_POST["add_to_cart"]) ||
        (
            isset($_POST["ajax_request"]) &&
            $_POST["ajax_request"] === "1"
        )
    )
) {
    $product_id = (int)($_POST["product_id"] ?? 0);

    $isAjax = (
        isset($_POST["ajax_request"]) &&
        $_POST["ajax_request"] === "1"
    );

    $productExists = false;

    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");

    if ($stmt) {
        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $productExists = ($result && $result->num_rows > 0);
        }

        $stmt->close();
    }

    if ($productExists) {
        if (isset($_SESSION["cart"][$product_id])) {
            $_SESSION["cart"][$product_id]++;
        } else {
            $_SESSION["cart"][$product_id] = 1;
        }
    }

    if ($isAjax) {
        // Return clean JSON response
        header("Content-Type: application/json; charset=UTF-8");

        if (!$productExists) {
            http_response_code(400);
        }

        echo json_encode([
            "success" => $productExists,
            "cartCount" => array_sum($_SESSION["cart"]),
            "message" => $productExists
                ? "Product added successfully."
                : "Product not found or database query failed."
        ]);

        exit;
    }

    // Normal form submission
    $category_id = (int)($_POST["category_id"] ?? 0);

    header(
        "Location: shop.php" .
        ($category_id > 0 ? "?category=" . $category_id : "")
    );
    exit;
}

// Fetch categories
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

if (!$categories) {
    die("Could not load categories. Please check your database.");
}

// Current category
$category_id = isset($_GET["category"])
    ? max(0, (int)$_GET["category"])
    : 0;

// Cart count
$cartCount = array_sum($_SESSION["cart"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Shopping</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<h1>Welcome to Our Shopping Website</h1>

<p>
    Hello, <?php echo htmlspecialchars($_SESSION["user_name"] ?? "User"); ?>!
</p>

<p>
    <a href="cart.php">
        View Cart (<span id="cart-count"><?php echo $cartCount; ?></span>)
    </a>
    |
    <a href="logout.php">Logout</a>
</p>

<hr>

<h2>Shop by Category</h2>

<p>
    <a href="shop.php" class="category-link" data-category="0">All Products</a>
    |

    <?php while ($category = $categories->fetch_assoc()) { ?>
        <a
            href="shop.php?category=<?php echo (int)$category["id"]; ?>"
            class="category-link"
            data-category="<?php echo (int)$category["id"]; ?>"
        >
            <?php echo htmlspecialchars($category["name"]); ?>
        </a>
        |
    <?php } ?>
</p>

<hr>

<h2 id="products-heading">All Available Products</h2>

<div id="products-container">
    <p>Loading products...</p>
</div>

<script>
const productsContainer = document.getElementById("products-container");
const productsHeading = document.getElementById("products-heading");
const cartCountElement = document.getElementById("cart-count");

let currentCategory = <?php echo $category_id; ?>;

// Load products using AJAX
function loadProducts(categoryId = 0, page = 1) {
    currentCategory = categoryId;

    productsContainer.innerHTML = "<p>Loading products...</p>";

    const categoryLink = document.querySelector(
        '.category-link[data-category="' + categoryId + '"]'
    );

    productsHeading.textContent = categoryId === 0
        ? "All Available Products"
        : (
            categoryLink
                ? categoryLink.textContent.trim() + " Products"
                : "Products"
        );

    // અહીં ફાઈલનું સાચું નામ fetch_products.php કરી દીધું છે
    fetch(
        "fetch_products.php?category=" +
        encodeURIComponent(categoryId) +
        "&page=" +
        encodeURIComponent(page),
        { credentials: "same-origin" }
    )
    .then(response => {
        if (!response.ok) {
            throw new Error("Could not load products (HTTP " + response.status + ")");
        }
        return response.text();
    })
    .then(html => {
        productsContainer.innerHTML = html;

        // Add-to-cart AJAX forms
        productsContainer
            .querySelectorAll(".ajax-add-to-cart")
            .forEach(form => {
                form.addEventListener("submit", function(event) {
                    event.preventDefault();

                    const button = form.querySelector("button");

                    if (button.disabled) {
                        return;
                    }

                    button.disabled = true;

                    const formData = new FormData(form);
                    formData.set("ajax_request", "1");

                    fetch("shop.php", {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    })
                    .then(async response => {
                        const text = await response.text();
                        let data;

                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            console.error("Raw server response:", text);
                            throw new Error("PHP did not return JSON. Check Console and Network Response.");
                        }

                        if (!response.ok) {
                            throw new Error(data.message || "Request failed.");
                        }

                        return data;
                    })
                    .then(data => {
                        if (data.success) {
                            cartCountElement.textContent = data.cartCount;

                            const previousText = button.textContent;
                            button.textContent = "Added!";

                            setTimeout(() => {
                                button.textContent = previousText;
                            }, 1000);
                        } else {
                            alert(data.message || "Product could not be added.");
                        }
                    })
                    .catch(error => {
                        console.error("Add to cart error:", error);
                        alert("Add to Cart failed: " + error.message);
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
                });
            });

        // Pagination
        productsContainer
            .querySelectorAll(".page-button")
            .forEach(button => {
                button.addEventListener("click", function() {
                    loadProducts(
                        currentCategory,
                        Number(this.dataset.page)
                    );
                });
            });
    })
    .catch(error => {
        console.error("Product loading error:", error);
        productsContainer.innerHTML = "<p>Products could not be loaded. Check fetch_products.php.</p>";
    });
}

// Category selection without page reload
document.querySelectorAll(".category-link").forEach(link => {
    link.addEventListener("click", function(event) {
        event.preventDefault();

        const categoryId = Number(this.dataset.category) || 0;

        const newUrl = categoryId === 0
            ? "shop.php"
            : "shop.php?category=" + categoryId;

        history.pushState(
            { category: categoryId },
            "",
            newUrl
        );

        loadProducts(categoryId, 1);
    });
});

// Browser Back and Forward
window.addEventListener("popstate", function() {
    const params = new URLSearchParams(window.location.search);
    const categoryId = Number(params.get("category")) || 0;

    loadProducts(categoryId, 1);
});

// Initial load
loadProducts(currentCategory, 1);
</script>

</body>
</html>