<?php
// NOTE FOR SUMAN/SHATTERED: this file didn't exist anywhere in the project -
// profile.php's "Sign Out" link was pointing to a 404. Standard logout logic added.

session_start();
session_destroy();
header("Location: ../index.php");
exit;
