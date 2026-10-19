<?php
session_start();
require_once "db.php";

$message = "";

// 1. CAPTCHA જનરેટ કરવો (જો ન હોય તો)
if (!isset($_SESSION["captcha_num1"]) || !isset($_SESSION["captcha_num2"])) {
    $_SESSION["captcha_num1"] = rand(1, 9);
    $_SESSION["captcha_num2"] = rand(1, 9);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $userCaptcha = isset($_POST["captcha"]) ? (int)$_POST["captcha"] : 0;
    
    // સાચો CAPTCHA જવાબ
    $correctCaptcha = $_SESSION["captcha_num1"] + $_SESSION["captcha_num2"];

    // 2. CAPTCHA ચકાસણી
    if ($userCaptcha !== $correctCaptcha) {
        $message = "Invalid CAPTCHA! Please try again.";
        // નવો CAPTCHA જનરેટ કરવો
        $_SESSION["captcha_num1"] = rand(1, 9);
        $_SESSION["captcha_num2"] = rand(1, 9);
    } elseif ($name !== "" && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);

        if ($stmt->execute()) {
            // રજીસ્ટ્રેશન સફળ થાય એટલે CAPTCHA સેશન ક્લિયર કરવું
            unset($_SESSION["captcha_num1"], $_SESSION["captcha_num2"]);
            
            header("Location: login.php?registered=1");
            exit;
        } else {
            $message = "This email may already be registered.";
        }
    } else {
        $message = "Enter valid details. Password must be at least 6 characters.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Create Account</h2>

<?php if ($message !== "") { ?>
    <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<form method="post">
    <label>Full Name:</label>
    <input type="text" name="name" required>
    <br><br>

    <label>Email:</label>
    <input type="email" name="email" required>
    <br><br>

    <label>Password:</label>
    <input type="password" name="password" minlength="6" required>
    <br><br>

    <!-- CAPTCHA Field -->
    <label>CAPTCHA Question:</label>
    <strong><?php echo $_SESSION["captcha_num1"] . " + " . $_SESSION["captcha_num2"] . " = ?"; ?></strong>
    <br>
    <input type="number" name="captcha" placeholder="Enter answer" required>
    <br><br>

    <button type="submit">Register</button>
</form>

<br>
<p>Already have an account? <a href="login.php">Login</a></p>

</body>
</html>