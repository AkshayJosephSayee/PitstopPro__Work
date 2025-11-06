<?php
require_once 'config.php';

// Verify database connection
if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "Connection is null"));
}

// Set proper charset
$conn->set_charset("utf8mb4");

function getRecentBookings($limit = 5) {
    global $conn;
    try {
        $sql = "SELECT b.booking_id, b.Username, b.service_type, b.booking_date, b.b_status,
                       s.price, s.Estimated_duration
                FROM tbl_bookings b 
                LEFT JOIN tbl_services s ON b.service_type = s.service_name
                ORDER BY b.booking_date DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $limit);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        return $bookings;
    } catch (Exception $e) {
        error_log("Error in getRecentBookings: " . $e->getMessage());
        return [];
    }
}

function getDashboardStats() {
    global $conn;
    try {
        $stats = [
            'totalBookings' => 0,
            'pendingServices' => 0,
            'completedToday' => 0,
            'totalRevenue' => 0
        ];
        
        // Start transaction for consistent reads
        $conn->begin_transaction();
        
        // Total bookings
        $result = $conn->query("SELECT COUNT(*) as total FROM tbl_bookings");
        if ($result) {
            $stats['totalBookings'] = $result->fetch_assoc()['total'];
        }
        
        // Pending services
        $result = $conn->query("SELECT COUNT(*) as pending FROM tbl_bookings WHERE b_status = 'pending'");
        if ($result) {
            $stats['pendingServices'] = $result->fetch_assoc()['pending'];
        }
        
        // Completed today
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM tbl_bookings WHERE DATE(booking_date) = ? AND b_status = 'completed'");
        if ($stmt) {
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $stats['completedToday'] = $result->fetch_assoc()['completed'];
            }
        }
        
        // Total revenue
        $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM tbl_bill WHERE Payment_status = 'paid'");
        if ($result) {
            $stats['totalRevenue'] = $result->fetch_assoc()['revenue'];
        }
        
        $conn->commit();
        return $stats;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in getDashboardStats: " . $e->getMessage());
        return $stats;
    }
}

function getAllUsers() {
    global $conn;
    try {
        $sql = "SELECT c.User_id_ as id, c.Username as name, c.email, c.Phone as phone,
                COUNT(b.booking_id) as total_bookings
                FROM tbl_customer c
                LEFT JOIN tbl_bookings b ON c.Username = b.Username
                GROUP BY c.User_id_, c.Username, c.email, c.Phone";
                
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['role'] = 'customer'; // Add role for UI compatibility
            $users[] = $row;
        }
        return $users;
    } catch (Exception $e) {
        error_log("Error in getAllUsers: " . $e->getMessage());
        return [];
    }
}

function getAllServices() {
    global $conn;
    try {
        $sql = "SELECT s.Service_id as id, 
                       s.service_name as name, 
                       s.description, 
                       s.price, 
                       s.Estimated_duration as duration,
                       COUNT(b.booking_id) as total_bookings
                FROM tbl_services s
                LEFT JOIN tbl_bookings b ON s.service_name = b.service_type
                GROUP BY s.Service_id, s.service_name, s.description, s.price, s.Estimated_duration
                ORDER BY total_bookings DESC";
                
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        return $services;
    } catch (Exception $e) {
        error_log("Error in getAllServices: " . $e->getMessage());
        return [];
    }
}

function addUser($data) {
    global $conn;
    $sql = "INSERT INTO tbl_customer (Username, email, Phone, Password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $hashedPassword = password_hash($data['password'] ?? 'default123', PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $data['name'], $data['email'], $data['phone'], $hashedPassword);
    return $stmt->execute();
}

function updateUser($data) {
    global $conn;
    $sql = "UPDATE tbl_customer SET Username = ?, email = ?, Phone = ? WHERE User_id_ = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $data['name'], $data['email'], $data['phone'], $data['id']);
    return $stmt->execute();
}

function deleteUser($User_id_) {
    global $conn;
    $sql = "DELETE FROM tbl_customer WHERE User_id_ = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $User_id_);
    return $stmt->execute();
}

function addService($data) {
    global $conn;
    $sql = "INSERT INTO tbl_services (service_name, description, price, Estimated_duration) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $data['name'], $data['description'], $data['price'], $data['duration']);
    return $stmt->execute();
}

function updateService($data) {
    global $conn;
    $sql = "UPDATE tbl_services SET service_name = ?, description = ?, price = ?, Estimated_duration = ? WHERE Service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $data['name'], $data['description'], $data['price'], $data['duration'], $data['id']);
    return $stmt->execute();
}

function deleteService($id) {
    global $conn;
    $sql = "DELETE FROM tbl_services WHERE Service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getCompletedBookings() {
    global $conn;
    $sql = "SELECT b.booking_id, b.Username , b.service_type, b.booking_date 
            FROM tbl_bookings b 
            WHERE b.b_status = 'completed'";
    $result = $conn->query($sql);
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    return $bookings;
}

function updateBookingStatus($bookingId, $status) {
    global $conn;
    try {
        $sql = "UPDATE tbl_bookings SET b_status = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("si", $status, $bookingId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in updateBookingStatus: " . $e->getMessage());
        return false;
    }
}

function searchBookings($searchTerm = '', $fromDate = '', $toDate = '', $status = '') {
    global $conn;
    try {
        $conditions = [];
        $params = [];
        $types = "";
        
        $sql = "SELECT b.*, s.price, s.Estimated_duration 
                FROM tbl_bookings b 
                LEFT JOIN tbl_services s ON b.service_type = s.service_name 
                WHERE 1=1";

        if ($searchTerm) {
            $conditions[] = "(b.Username LIKE ? OR b.service_type LIKE ?)";
            $searchParam = "%$searchTerm%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        if ($fromDate) {
            $conditions[] = "DATE(b.booking_date) >= ?";
            $params[] = $fromDate;
            $types .= "s";
        }

        if ($toDate) {
            $conditions[] = "DATE(b.booking_date) <= ?";
            $params[] = $toDate;
            $types .= "s";
        }

        if ($status) {
            $conditions[] = "b.b_status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY b.booking_date DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    } catch (Exception $e) {
        error_log("Error in searchBookings: " . $e->getMessage());
        return [];
    }
}

function generateBill($booking_id, $items) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Calculate total amount
        $sql = "SELECT service_name FROM tbl_bookings WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();
        
        // Get service price
        $stmt = $conn->prepare("SELECT price FROM tbl_services WHERE service_name = ?");
        $stmt->bind_param("s", $service['service_name']);
        $stmt->execute();
        $basePrice = $stmt->get_result()->fetch_assoc()['price'];
        
        // Calculate total with additional items
        $totalAmount = $basePrice;
        foreach ($items as $item) {
            $totalAmount += $item['price'];
        }
        
        // Create bill
        $sql = "INSERT INTO tbl_bill (booking_id, total_amount, Payment_status) VALUES (?, ?, 'not paid')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $booking_id, $totalAmount);
        $stmt->execute();
        
        $conn->commit();
        return ['success' => true, 'total' => $totalAmount];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>