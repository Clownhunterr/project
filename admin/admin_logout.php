<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
session_write_close();
header("Location: admin_login.php");
exit;
