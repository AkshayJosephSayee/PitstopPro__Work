<?php
require_once 'config.php';
// config.php already starts the session if needed

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$Username = trim($_POST['Username'] ?? '');
$password = $_POST['password'] ?? '';

if ($Username === '' || $password === '') {
    header('Location: login.html?admin=empty');
    exit;
}

// Try to authenticate against tbl_admin if it exists
$authenticated = false;
$admin_id = null;

$check = $conn->query("SHOW TABLES LIKE 'tbl_admins'");
if ($check && $check->num_rows > 0) {
    $stmt = $conn->prepare('SELECT admin_id, Username, password FROM tbl_admins WHERE Username = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $Username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($admin_id, $Username, $ahash);
            $stmt->fetch();
            // Support both hashed and plain passwords
            if (password_verify($password, $ahash) || $password === $ahash) {
                $authenticated = true;
            }
        }
        $stmt->close();
    }
}

// Fallback hard-coded admin credential if not authenticated yet
if (!$authenticated) {
    // CHANGE THESE IN PRODUCTION
    $default_admin_user = 'admin';
    $default_admin_pass = 'admin123';
    if ($Username === $default_admin_user && $password === $default_admin_pass) {
        $authenticated = true;
        $admin_id = 1; // Default admin ID
    }
}

if ($authenticated) {
    // set admin session and redirect to admin dashboard
    $_SESSION['is_admin'] = true;
    $_SESSION['Username'] = $Username;
    $_SESSION['admin_id'] = $admin_id;
    // redirect to admin dashboard
    header('Location: admin.php');
    exit;
} else {
    // failed
    header('Location: login.html?admin=failed');
    exit;
}
?>