<?php
include '../conn/conn.php';

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/");
    exit();
}
// Your code to manage users goes here

?>