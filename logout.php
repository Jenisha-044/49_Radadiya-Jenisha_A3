
<?php
session_start();

unset($_SESSION["user_id"]);
unset($_SESSION["user_name"]);
unset($_SESSION["cart"]);

session_regenerate_id(true);

header("Location: login.php");
exit;
?>
