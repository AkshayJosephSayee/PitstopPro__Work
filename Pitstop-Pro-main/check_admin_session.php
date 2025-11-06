<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to login page
    header('Location: login.html');
    exit;
}
?>