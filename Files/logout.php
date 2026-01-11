<?php
session_start();
session_unset();
session_destroy();
header("Location: login_page.html"); // redirect to login or homepage
exit();
?>
