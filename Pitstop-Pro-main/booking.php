<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once 'config.php';

// Initialize variables
$Username = $email = $Phone = $service_type = $appointment_date = $appointment_time = $special_request = '';
$success_message = $error_message = '';
$show_popup = false;

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    $show_popup = true;
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    $show_popup = true;
    unset($_SESSION['error_message']);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $Username = trim($_POST['Username']);
    $email = trim($_POST['email']);
    $Phone = trim($_POST['Phone']);
    $service_type = trim($_POST['service_type']);
    $appointment_date = trim($_POST['appointment_date']);
    $appointment_time = trim($_POST['appointment_time']);
    $special_request = isset($_POST['special_request']) ? trim($_POST['special_request']) : '';
    
    // Basic validation
    if (empty($Username) || empty($email) || empty($Phone) || empty($service_type) || empty($appointment_date) || empty($appointment_time)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Check if columns exist, if not add them
    $check_columns = $conn->query("SHOW COLUMNS FROM tbl_bookings LIKE 'Username'");
    if ($check_columns->num_rows == 0) {
        // Add columns if they don't exist
        $conn->query("ALTER TABLE tbl_bookings ADD COLUMN Username VARCHAR(100) DEFAULT NULL AFTER booking_id");
        $conn->query("ALTER TABLE tbl_bookings ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER Username");
        $conn->query("ALTER TABLE tbl_bookings ADD COLUMN Phone VARCHAR(20) DEFAULT NULL AFTER email");
        $conn->query("ALTER TABLE tbl_bookings ADD COLUMN service_type VARCHAR(100) DEFAULT NULL AFTER Phone");
    }
    
    // Prepare and bind - insert into tbl_bookings with new columns
    $stmt = $conn->prepare("INSERT INTO tbl_bookings (Username, email, Phone, service_type, booking_date, booking_time, special_request, b_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    if ($stmt === false) {
        $_SESSION['error_message'] = "Database error: Unable to prepare statement. Please try again later.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $stmt->bind_param("sssssss", $Username, $email, $Phone, $service_type, $appointment_date, $appointment_time, $special_request);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Your booking has been successfully submitted! We will contact you soon after the service.";
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error_message'] = "Booking could not be submitted to database. Error: " . $stmt->error;
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Pitstop-Pro - Booking</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Favicons -->
        <link href="img/favicon.ico" rel="icon">
        <link href="img/apple-touch-icon.png" rel="apple-touch-icon">

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600|Nunito:600,700,800,900" rel="stylesheet"> 

        <!-- Bootstrap CSS File -->
        <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

        <!-- Libraries CSS Files -->
        <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <link href="vendor/animate/animate.min.css" rel="stylesheet">
        <link href="vendor/ionicons/css/ionicons.min.css" rel="stylesheet">
        <link href="vendor/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
        <link href="vendor/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

        <!-- Main Stylesheet File -->
        <link href="css/hover-style.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
        
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
        <!-- Top Header Start -->
        <section class="banner-header">
            <video autoplay muted loop>
                <source src="img\BMW M3 Competition - 4K Cinematic Short Video.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <header class="relative py-24 lg:py-32 hero-bg-pattern overflow-hidden">
                <div class="relative z-10 max-w-4xl mx-auto text-center px-4">
                    <div class="glitch-container">
                        <h1 id="glitch-heading" class="glitch-text text-4xl md:text-5xl lg:text-6xl font-black mb-6" data-text="Pitstop Pro">
                            Pitstop <span class="gradient-text">Pro</span>
                        </h1>
                    </div>
                    <h2>Your Car Doctor</h2>
                </div>
            </header>
        </section>
        <!-- Top Header End -->

        <!-- Header Start -->
        <header id="header">
            <div class="container">
                <nav id="nav-menu-container">
                    <ul class="nav-menu">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="service.html">Services</a></li>
                        <li class="menu-active"><a href="booking.php">Booking</a></li>
                        <li><a href="login.html">Login</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <!-- Header End -->

        <main id="main">

            <!-- Popup Modal -->
            <div id="popupOverlay" class="popup-overlay">
                <div class="popup-container">
                    <div id="popupIcon" class="popup-icon"></div>
                    <h2 id="popupTitle" class="popup-title"></h2>
                    <p id="popupMessage" class="popup-message"></p>
                    <button class="popup-button" onclick="closePopup()">OK</button>
                </div>
            </div>

            <!-- Booking Section Start -->
            <section id="booking">
                <div class="container">
                    <div class="section-header">
                        <h3>Book for Getting Services</h3>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="booking-form">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="form-row">
                                        <div class="control-group col-sm-6">
                                            <label> Username *</label>
                                            <input type="text" class="form-control" name="Username" value="<?php echo htmlspecialchars($Username); ?>" required="required" />
                                        </div>
                                        <div class="control-group col-sm-6">
                                            <label>Email *</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required="required" />
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="control-group col-sm-6">
                                            <label>Phone Num *</label>
                                            <input type="text" class="form-control" name="Phone" value="<?php echo htmlspecialchars($Phone); ?>" required="required" />
                                        </div>
                                        <div class="control-group col-sm-6">
                                            <label>Select a Service *</label>
                                            <select class="custom-select" name="service_type" required>
                                                <option value="">Choose a service...</option>
                                                <option value="Engine Tuning" <?php echo ($service_type == 'Engine Tuning') ? 'selected' : ''; ?>>Engine Tuning</option>
                                                <option value="Paint work" <?php echo ($service_type == 'Paint work') ? 'selected' : ''; ?>>Paint work</option>
                                                <option value="Break check" <?php echo ($service_type == 'Break check') ? 'selected' : ''; ?>>Break check</option>
                                                <option value="Service" <?php echo ($service_type == 'Service') ? 'selected' : ''; ?>>Service</option>
                                                <option value="Wheel Alignment" <?php echo ($service_type == 'Wheel Alignment') ? 'selected' : ''; ?>>Wheel Alignment</option>
                                                <option value="Body Work" <?php echo ($service_type == 'Body Work') ? 'selected' : ''; ?>>Body Work</option>
                                                <option value="Accessories" <?php echo ($service_type == 'Accessories') ? 'selected' : ''; ?>>Accessories</option>
                                                <option value="Washing" <?php echo ($service_type == 'Washing') ? 'selected' : ''; ?>>Washing</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="control-group col-sm-6">
                                            <label>Appointment Date *</label>
                                            <input type="date" class="form-control" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date); ?>" required="required" />
                                        </div>
                                        <div class="control-group col-sm-6">
                                            <label>Appointment Time *</label>
                                            <input type="time" class="form-control" name="appointment_time" value="<?php echo htmlspecialchars($appointment_time); ?>" required="required" />
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label>Special Request</label>
                                        <textarea class="form-control" name="special_request" rows="3"><?php echo htmlspecialchars($special_request); ?></textarea>
                                    </div>
                                    <div class="button">
                                        <button type="submit">Book Now</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Booking Section End -->
            
            <!-- Support Section Start -->
            <section id="support" class="wow fadeInUp">
                <div class="container">
                    <h1>
                        Need help? Call me 24/7 at +91 9567884807
                    </h1>
                </div>
            </section>
            <!-- Support Section end -->

        </main>

        <!-- Footer Start -->
        <footer id="footer">
            <div class="container">
                <div class="copyright">
                    <p>&copy; Copyright <a href="#">Pitstop-Pro</a>. All Rights Reserved</p>
                </div>
            </div>
        </footer>
        <!-- Footer end -->

        <a href="#" class="back-to-top"><i class="fa fa-chevron-up"></i></a>

        <!-- JavaScript Libraries -->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/jquery/jquery-migrate.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="vendor/easing/easing.min.js"></script>
        <script src="vendor/stickyjs/sticky.js"></script>
        <script src="vendor/superfish/hoverIntent.js"></script>
        <script src="vendor/superfish/superfish.min.js"></script>
        <script src="vendor/owlcarousel/owl.carousel.min.js"></script>
        <script src="vendor/touchSwipe/jquery.touchSwipe.min.js"></script>
        <script src="vendor/tempusdominus/js/moment.min.js"></script>
        <script src="vendor/tempusdominus/js/moment-timezone.min.js"></script>
        <script src="vendor/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

        <!-- Main Javascript File -->
        <script src="js/main.js"></script>
        
        <script>
            console.log('Script loaded');
            console.log('PHP Success message:', '<?php echo addslashes($success_message); ?>');
            console.log('PHP Error message:', '<?php echo addslashes($error_message); ?>');
            console.log('Show popup flag:', <?php echo $show_popup ? 'true' : 'false'; ?>);
            
            // Popup Functions
            function showPopup(type, title, message) {
                console.log('showPopup called with:', type, title, message);
                
                const overlay = document.getElementById('popupOverlay');
                const icon = document.getElementById('popupIcon');
                const titleEl = document.getElementById('popupTitle');
                const messageEl = document.getElementById('popupMessage');
                
                if (!overlay || !icon || !titleEl || !messageEl) {
                    console.error('Popup elements not found!');
                    console.log('overlay:', overlay);
                    console.log('icon:', icon);
                    console.log('titleEl:', titleEl);
                    console.log('messageEl:', messageEl);
                    // Fallback to alert
                    alert(title + '\n\n' + message);
                    return;
                }
                
                console.log('All popup elements found');
                
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
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.zIndex = '9999';
                
                console.log('Popup should be visible now');
                console.log('Overlay display:', overlay.style.display);
                
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
            
            // Initialize immediately
            (function() {
                console.log('Immediate function executing');
                
                // Wait a bit for DOM to be ready
                setTimeout(function() {
                    console.log('Timeout executing');
                    
                    // Close popup when clicking outside
                    const overlay = document.getElementById('popupOverlay');
                    if (overlay) {
                        overlay.addEventListener('click', function(e) {
                            if (e.target === this) {
                                closePopup();
                            }
                        });
                    }
                    
                    // Check for messages and show popup
                    var hasSuccess = <?php echo !empty($success_message) ? 'true' : 'false'; ?>;
                    var hasError = <?php echo !empty($error_message) ? 'true' : 'false'; ?>;
                    
                    console.log('Has success:', hasSuccess, 'Has error:', hasError);
                    
                    if (hasSuccess) {
                        var successMsg = <?php echo json_encode($success_message); ?>;
                        console.log('Showing success popup:', successMsg);
                        showPopup('success', 'Booking Submitted!', successMsg);
                    } else if (hasError) {
                        var errorMsg = <?php echo json_encode($error_message); ?>;
                        console.log('Showing error popup:', errorMsg);
                        showPopup('error', 'Booking Not Submitted', errorMsg);
                    } else {
                        console.log('No messages to show');
                    }
                }, 100);
            })();
        </script>

    </body>
</html>

<?php
// Close database connection
$conn->close();
?>