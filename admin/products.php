<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Resize and save an uploaded image
function uploadProductImage($file)
{
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    // Maximum upload size: 5 MB (5 * 1024 * 1024)
    if ($file["size"] > 5 * 1024 * 1024) {
        throw new Exception("Image must be smaller than 5 MB.");
    }

    $info = getimagesize($file["tmp_name"]);

    if ($info === false) {
        throw new Exception("Please upload a valid image.");
    }

    $mime = $info["mime"];

    if (!in_array($mime, ["image/jpeg", "image/png", "image/webp"])) {
        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
    }

    if (!function_exists("imagecreatetruecolor")) {
        throw new Exception(
            "PHP GD extension is not enabled. Enable GD in XAMPP php.ini."
        );
    }

    switch ($mime) {
        case "image/jpeg":
            $source = imagecreatefromjpeg($file["tmp_name"]);
            $extension = "jpg";
            break;

        case "image/png":
            $source = imagecreatefrompng($file["tmp_name"]);
            $extension = "png";
            break;

        case "image/webp":
            if (!function_exists("imagecreatefromwebp")) {
                throw new Exception("Your PHP GD does not support WEBP.");
            }
            $source = imagecreatefromwebp($file["tmp_name"]);
            $extension = "webp";
            break;

        default:
            throw new Exception("Unsupported image format.");
    }

    if (!$source) {
        throw new Exception("Could not read the uploaded image.");
    }

    $width = imagesx($source);
    $height = imagesy($source);

    // Resize to fit inside 800 x 800, preserving aspect ratio
    $scale = min(800 / $width, 800 / $height, 1);
    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $resized = imagecreatetruecolor($newWidth, $newHeight);

    if ($extension === "png" || $extension === "webp") {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle(
            $resized, 0, 0, $newWidth, $newHeight, $transparent
        );
    }

    imagecopyresampled(
        $resized, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight, $width, $height
    );

    $folder = __DIR__ . "/../uploads/";

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        imagedestroy($source);
        imagedestroy($resized);
        throw new Exception("Could not create uploads folder.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $extension;
    $destination = $folder . $filename;

    switch ($extension) {
        case "jpg":
            $saved = imagejpeg($resized, $destination, 85);
            break;
        case "png":
            $saved = imagepng($resized, $destination, 6);
            break;
        case "webp":
            $saved = imagewebp($resized, $destination, 85);
            break;
    }

    imagedestroy($source);
    imagedestroy($resized);

    if (!$saved) {
        throw new Exception("Could not save resized image.");
    }

    return "uploads/" . $filename;
}

// Add product
if (isset($_POST["add"])) {
    try {
        $categoryId = (int) $_POST["category_id"];
        $name = trim($_POST["name"]);
        $price = (float) $_POST["price"];

        if ($name === "" || $price < 0 || $categoryId <= 0) {
            throw new Exception("Enter valid product details.");
        }

        $image = uploadProductImage($_FILES["image"] ?? null);

        $stmt = $conn->prepare(
            "INSERT INTO products (category_id, name, price, image)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isds", $categoryId, $name, $price, $image);
        $stmt->execute();

        header("Location: products.php");
        exit;
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

// Update product
if (isset($_POST["update"])) {
    try {
        $id = (int) $_POST["id"];
        $categoryId = (int) $_POST["category_id"];
        $name = trim($_POST["name"]);
        $price = (float) $_POST["price"];

        if ($name === "" || $price < 0 || $categoryId <= 0) {
            throw new Exception("Enter valid product details.");
        }

        // Keep existing image if no new file is selected
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $oldProduct = $stmt->get_result()->fetch_assoc();

        if (!$oldProduct) {
            throw new Exception("Product not found.");
        }

        $image = $oldProduct["image"];
        $newImage = uploadProductImage($_FILES["image"] ?? null);

        if ($newImage !== null) {
            $image = $newImage;
        }

        $stmt = $conn->prepare(
            "UPDATE products
             SET category_id = ?, name = ?, price = ?, image = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "isdsi", $categoryId, $name, $price, $image, $id
        );
        $stmt->execute();

        // Remove old uploaded file after a successful update
        if ($newImage !== null && !empty($oldProduct["image"])) {
            $oldPath = __DIR__ . "/../" . $oldProduct["image"];
            $uploadFolder = realpath(__DIR__ . "/../uploads");

            if (
                $uploadFolder &&
                is_file($oldPath) &&
                realpath($oldPath) &&
                strpos(realpath($oldPath), $uploadFolder . DIRECTORY_SEPARATOR) === 0
            ) {
                unlink($oldPath);
            }
        }

        header("Location: products.php");
        exit;
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

// Delete product
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $oldProduct = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($oldProduct && !empty($oldProduct["image"])) {
        $oldPath = __DIR__ . "/../" . $oldProduct["image"];
        $uploadFolder = realpath(__DIR__ . "/../uploads");

        if (
            $uploadFolder &&
            is_file($oldPath) &&
            realpath($oldPath) &&
            strpos(realpath($oldPath), $uploadFolder . DIRECTORY_SEPARATOR) === 0
        ) {
            unlink($oldPath);
        }
    }

    header("Location: products.php");
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$products = $conn->query(
    "SELECT products.*, categories.name AS category_name
     FROM products
     JOIN categories ON products.category_id = categories.id
     ORDER BY products.id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<h1>Manage Products</h1>
<?php if ($message !== "") { ?>
    <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<h2>Add Product</h2>

<form method="post" enctype="multipart/form-data">
    <label>Product Name:</label>
    <input type="text" name="name" required>
    <br><br>

    <label>Category:</label>
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php while ($cat = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $cat["id"]; ?>">
                <?php echo htmlspecialchars($cat["name"]); ?>
            </option>
        <?php } ?>
    </select>
    <br><br>

    <label>Price:</label>
    <input type="number" name="price" min="0" step="0.01" required>
    <br><br>

    <label>Product Picture (JPG, PNG or WEBP; max 5 MB):</label>
    <input type="file" name="image"
           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    <br><br>

    <button type="submit" name="add">Add Product</button>
</form>

<hr>

<h2>All Products</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Picture</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>

    <?php while ($row = $products->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row["id"]; ?></td>

            <td>
                <?php if (!empty($row["image"])) { ?>
                    <img
                        src="../<?php echo htmlspecialchars($row["image"]); ?>"
                        alt="Product picture"
                        width="80"
                        style="max-height:80px; object-fit:contain;"
                    >
                <?php } else { ?>
                    No image
                <?php } ?>
            </td>

            <td><?php echo htmlspecialchars($row["name"]); ?></td>
            <td><?php echo htmlspecialchars($row["category_name"]); ?></td>
            <td>₹<?php echo number_format((float)$row["price"], 2); ?></td>

            <td>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">

                    <label>Name:</label>
                    <input type="text" name="name"
                           value="<?php echo htmlspecialchars($row["name"]); ?>" required>

                    <br><br>

                    <label>Category:</label>
                    <select name="category_id" required>
                        <?php
                        $allCats = $conn->query("SELECT * FROM categories ORDER BY name");
                        while ($cat = $allCats->fetch_assoc()) {
                        ?>
                            <option value="<?php echo $cat["id"]; ?>"
                                <?php echo ((int)$cat["id"] === (int)$row["category_id"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($cat["name"]); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <br><br>

                    <label>Price:</label>
                    <input type="number" name="price" min="0" step="0.01"
                           value="<?php echo htmlspecialchars((string)$row["price"]); ?>" required>

                    <br><br>

                    <label>Replace picture (optional):</label>
                    <input type="file" name="image"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <br><br>

                    <button type="submit" name="update">Update</button>
                </form>
            </td>

            <td>
                <a href="products.php?delete=<?php echo $row["id"]; ?>"
                   onclick="return confirm('Delete this product?')">
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