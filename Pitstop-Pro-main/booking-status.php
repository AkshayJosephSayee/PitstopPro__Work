<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Get user's bookings
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.service_type,
        b.appointment_date,
        b.appointment_time,
        b.special_request,
        b.status,
        b.created_at
    FROM bookings b
    WHERE b.customer_id = ?
    ORDER BY b.appointment_date DESC, b.appointment_time DESC
");

$bookings = [];
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Booking Status - Pitstop-Pro</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600|Nunito:600,700,800,900" rel="stylesheet"> 

        <!-- Bootstrap CSS File -->
        <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

        <!-- Libraries CSS Files -->
        <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <link href="vendor/animate/animate.min.css" rel="stylesheet">

        <!-- Main Stylesheet File -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body>
        <!-- Header Start -->
        <header id="header">
            <div class="container">
                <nav id="nav-menu-container">
                    <ul class="nav-menu">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="service.html">Services</a></li>
                        <li><a href="booking.html">Booking</a></li>
                        <li class="menu-active"><a href="booking-status.php">Booking Status</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <!-- Header End -->

        <main id="main">
            <section id="booking-status">
                <div class="container">
                    <div class="section-header">
                        <h2>Your Bookings</h2>
                        <p>Check the status of your service appointments</p>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <?php if (empty($bookings)): ?>
                                <div class="alert alert-info">
                                    You haven't made any bookings yet. 
                                    <a href="booking.html" class="alert-link">Book a service now</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Service</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                                <th>Special Request</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['appointment_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['appointment_time']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo match($booking['status']) {
                                                                'pending' => 'warning',
                                                                'confirmed' => 'success',
                                                                'completed' => 'info',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($booking['special_request'] ?: '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
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

        <!-- JavaScript Libraries -->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="vendor/easing/easing.min.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>