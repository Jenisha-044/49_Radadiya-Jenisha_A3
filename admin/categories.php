
<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Add category
if (isset($_POST["add"])) {
    $name = trim($_POST["name"]);

    if ($name !== "") {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            $message = "Category added successfully!";
        } else {
            $message = "Error adding category.";
        }
    }
}

// Delete category
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: categories.php");
        exit;
    } else {
        $message = "Cannot delete category. It may contain products.";
    }
}

// Update category
if (isset($_POST["update"])) {
    $id = (int) $_POST["id"];
    $name = trim($_POST["name"]);

    if ($name !== "") {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();

        header("Location: categories.php");
        exit;
    }
}

// Fetch categories
$result = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
</head>
<body>

<h2>Manage Categories</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<form method="post">
    <label>Category Name:</label>
    <input type="text" name="name" required>
    <button type="submit" name="add">Add Category</button>
</form>

<hr>

<h3>All Categories</h3>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Category Name</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row["id"]; ?></td>
            <td><?php echo htmlspecialchars($row["name"]); ?></td>

            <td>
                <form method="post">
                    <input type="hidden" name="id"
                           value="<?php echo $row["id"]; ?>">
                    <input type="text" name="name"
                           value="<?php echo htmlspecialchars($row["name"]); ?>"
                           required>
                    <button type="submit" name="update">Update</button>
                </form>
            </td>

            <td>
                <a href="categories.php?delete=<?php echo $row["id"]; ?>"
                   onclick="return confirm('Delete this category?')">
                    Delete
                </a>
            </td>
        </tr>
    <?php } ?>
</table>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
