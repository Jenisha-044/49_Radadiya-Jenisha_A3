
<?php
$conn = new mysqli("localhost", "root", "root", "shopping_cart");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
