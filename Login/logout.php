<?php
session_start();
session_unset();
session_destroy();
header("Location: login.html"); // Redirect to your homepage or login page
exit();
?>

