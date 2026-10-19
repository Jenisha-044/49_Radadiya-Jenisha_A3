
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<h1>Welcome to Admin Dashboard</h1>

<h3>Manage Your Shopping Website</h3>

<ul>
    <li><a href="categories.php">Manage Categories</a></li>
    <li><a href="products.php">Manage Products</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>
