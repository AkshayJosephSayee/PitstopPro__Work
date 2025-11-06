<?php
// debug.php - Place this at the top of admin1.php temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
echo "<pre>";
echo "Session contents:\n";
print_r($_SESSION);
echo "\nPHP Info:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "</pre>";
?>