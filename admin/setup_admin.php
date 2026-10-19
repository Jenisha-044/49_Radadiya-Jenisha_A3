
<?php
session_start();
require_once "../db.php";

$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admins (username, password)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password = VALUES(password)"
);

$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    echo "Admin created successfully!";
    echo "<br>Username: admin";
    echo "<br>Password: admin123";
} else {
    echo "Error creating admin: " . $stmt->error;
}
?>
