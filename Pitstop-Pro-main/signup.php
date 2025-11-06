<?php
require_once 'config.php';

// Simple registration handler for tbl_customer
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$Username = trim($_POST['Username'] ?? '');
$email = trim($_POST['email'] ?? '');
$Phone = trim($_POST['Phone'] ?? '');
$Password = $_POST['Password'] ?? '';
$repeat = $_POST['repeat_password'] ?? '';

$errors = [];
if ($Username === '' || $email === '' || $Password === '') {
    $errors[] = 'Please fill in name, email and password.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}
if ($Password !== $repeat) {
    $errors[] = 'Passwords do not match.';
}

if (empty($errors)) {
    // Check if email already exists
    $stmt = $conn->prepare('SELECT User_id_ FROM tbl_customer WHERE email = ? LIMIT 1');
    if (!$stmt) {
        $errors[] = 'Database error: ' . $conn->error;
    } else {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }
        $stmt->close();
    }
}

if (empty($errors)) {
    $hashed = password_hash($Password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO tbl_customer (Username, email, Phone, Password) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        $errors[] = 'Database error: ' . $conn->error;
    } else {
        $stmt->bind_param('ssss', $Username, $email, $Phone, $hashed);
        if ($stmt->execute()) {
            // Registration successful. Don't redirect immediately, let the modal handle it
            $stmt->close();
            $conn->close();
            // The success modal will be shown and handle the redirect
        } else {
            $errors[] = 'Could not create account: ' . $stmt->error;
        }
      
    }
}

// If we reach here there are errors; show basic feedback and link back to form.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signup - Pitstop Pro</title>
    <!-- Bootstrap CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- Custom styles -->
    <style>
        /* Popup Modal Styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .popup-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: slideDown 0.4s ease-out;
        }
        
        .popup-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .popup-icon.success {
            color: #28a745;
        }
        
        .popup-icon.error {
            color: #dc3545;
        }
        
        .popup-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .popup-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .popup-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .popup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from { 
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to { 
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Popup Modal -->
    <div id="popupOverlay" class="popup-overlay">
        <div class="popup-container">
            <div id="popupIcon" class="popup-icon"></div>
            <h2 id="popupTitle" class="popup-title"></h2>
            <p id="popupMessage" class="popup-message"></p>
            <button class="popup-button" onclick="closePopup()">OK</button>
        </div>
    </div>

    <!-- Error Display -->
    <?php if (!empty($errors)): ?>
        <div class="container mt-4">
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <p><a href="login.html" class="btn btn-primary">Back to login</a></p>
        </div>
    <?php endif; ?>

    <!-- JavaScript Libraries -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Popup Functions
        function showPopup(type, title, message) {
            console.log('showPopup called with:', type, title, message);
            
            const overlay = document.getElementById('popupOverlay');
            const icon = document.getElementById('popupIcon');
            const titleEl = document.getElementById('popupTitle');
            const messageEl = document.getElementById('popupMessage');
            
            if (!overlay || !icon || !titleEl || !messageEl) {
                console.error('Popup elements not found!');
                alert(title + '\n\n' + message);
                return;
            }
            
            // Set icon based on type
            if (type === 'success') {
                icon.innerHTML = '<i class="fa fa-check-circle"></i>';
                icon.className = 'popup-icon success';
            } else {
                icon.innerHTML = '<i class="fa fa-times-circle"></i>';
                icon.className = 'popup-icon error';
            }
            
            titleEl.textContent = title;
            messageEl.textContent = message;
            overlay.style.display = 'block';
            
            // Prevent body scroll when popup is open
            document.body.style.overflow = 'hidden';
        }
        
        function closePopup() {
            const overlay = document.getElementById('popupOverlay');
            if (overlay) {
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Initialize
        (function() {
            // Close popup when clicking outside
            const overlay = document.getElementById('popupOverlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closePopup();
                    }
                });
            }
            
            <?php if (empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            // Show success popup and redirect
            showPopup('success', 'Registration Successful!', 'Your account has been created successfully. Redirecting to login page...');
            setTimeout(function() {
                window.location.href = 'login.html';
            }, 3000);
            <?php endif; ?>
        })();
    </script>
</body>
</html>
<?php
// contains your DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Username = $_POST['Username'] ?? '';
    $email = $_POST['email'] ?? '';
    $Phone = $_POST['Phone'] ?? '';
    $Password = $_POST['Password'] ?? '';
    $repeat_password = $_POST['repeat_password'] ?? '';

    if ($Password !== $repeat_password) {
        echo "Passwords do not match!";
        exit;
    }

    // Hash password for security
    $hashed_password = password_hash($Password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO tbl_customer (Username, email, Phone,Password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $Username, $email, $Phone, $hashed_password);

    if ($stmt->execute()) {
        echo "Signup successful!";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
}
?>