<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 1. Remove one product from cart (લાસ્ટથી ઉપર લાવ્યું)
if (isset($_GET["remove"])) {
    $id = (int) $_GET["remove"];
    unset($_SESSION["cart"][$id]);

    header("Location: cart.php");
    exit;
}

// 2. Clear cart (લાસ્ટથી ઉપર લાવ્યું)
if (isset($_POST["clear_cart"])) {
    $_SESSION["cart"] = [];

    header("Location: cart.php");
    exit;
}

// 3. સેશનમાંથી કાર્ટ ડેટા મેળવો
$cart = $_SESSION["cart"] ?? [];
$total = 0;
$items = [];

if (!empty($cart)) {
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");

    foreach ($cart as $id => $quantity) {
        $id = (int) $id;
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();

        if ($product) {
            $product["quantity"] = (int) $quantity;
            $product["subtotal"] = (float) $product["price"] * (int) $quantity;

            $total += $product["subtotal"];
            $items[] = $product;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Your Shopping Cart</h1>

<?php if (empty($items)) { ?>

    <p>Your cart is empty.</p>

<?php } else { ?>

    <table border="1" cellpadding="10">
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
        </tr>

        <?php foreach ($items as $item) { ?>
            <tr>
                <td><?php echo htmlspecialchars($item["name"]); ?></td>
                <td>₹<?php echo number_format($item["price"], 2); ?></td>
                <td><?php echo $item["quantity"]; ?></td>
                <td>₹<?php echo number_format($item["subtotal"], 2); ?></td>
                <td>
                    <a href="cart.php?remove=<?php echo $item["id"]; ?>" onclick="return confirm('Remove item?')">
                        Remove
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <h3>Total: ₹<?php echo number_format($total, 2); ?></h3>

    <form method="post">
        <button type="submit" name="clear_cart" onclick="return confirm('Clear entire cart?')">Clear Cart</button>
    </form>

<?php } ?>

<br>
<p><a href="shop.php">Continue Shopping</a></p>

</body>
</html>