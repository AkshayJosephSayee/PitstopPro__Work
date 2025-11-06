<?php
header('Content-Type: application/json');
require_once 'admin_functions.php';

// Initialize response array
$response = ['success' => false, 'data' => null, 'error' => null];

try {
    // Get the action from POST request
    $action = $_POST['action'] ?? '';

    // Decode optional JSON payload sent in `data` (used by admin.php's postAction)
    $payload = [];
    if (isset($_POST['data'])) {
        $decoded = json_decode($_POST['data'], true);
        if (is_array($decoded)) $payload = $decoded;
    }

    // Handle different actions
    switch ($action) {
        case 'getStats':
            $response['data'] = getDashboardStats();
            $response['success'] = true;
            break;
            
        case 'getBookings':
            // Accept limit either as a top-level POST field or inside JSON `data` payload
            $limit = $payload['limit'] ?? ($_POST['limit'] ?? 5);
            $response['data'] = getRecentBookings((int)$limit);
            $response['success'] = true;
            break;
            
        case 'getUsers':
            $response['data'] = getAllUsers();
            $response['success'] = true;
            break;
            
        case 'getServices':
            $response['data'] = getAllServices();
            $response['success'] = true;
            break;
            
        case 'addUser':
            // Prefer JSON payload, fall back to individual POST fields
            $userData = $payload;
            if (!$userData) {
                $userData = [
                    'name' => $_POST['name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'password' => $_POST['password'] ?? null
                ];
            }

            if (!$userData) {
                throw new Exception("Invalid user data provided");
            }
            
            // Validate required fields
            if (empty($userData['name']) || empty($userData['email']) || empty($userData['phone'])) {
                throw new Exception("Missing required user fields");
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            $result = addUser($userData);
            if (!$result) {
                throw new Exception("Failed to add user");
            }
            
            $response['success'] = true;
            $response['message'] = "User added successfully";
            break;
            
        case 'updateUser':
            $userData = $payload;
            if (!$userData) {
                $userData = [
                    'id' => isset($_POST['id']) ? $_POST['id'] : null,
                    'name' => $_POST['name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? ''
                ];
            }

            if (!$userData || !isset($userData['id'])) {
                throw new Exception("Invalid user data or missing ID");
            }
            
            // Validate email if provided
            if (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            $result = updateUser($userData);
            if (!$result) {
                throw new Exception("Failed to update user");
            }
            
            $response['success'] = true;
            $response['message'] = "User updated successfully";
            break;
            
        case 'deleteUser':
            $userId = $payload['id'] ?? ($_POST['id'] ?? null);
            if (!$userId) {
                throw new Exception("User ID is required");
            }

            $result = deleteUser($userId);
            if (!$result) {
                throw new Exception("Failed to delete user");
            }
            
            $response['success'] = true;
            $response['message'] = "User deleted successfully";
            break;
            
        case 'addService':
            $serviceData = $payload;
            if (!$serviceData) {
                $serviceData = [
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'price' => isset($_POST['price']) ? $_POST['price'] : null,
                    'duration' => isset($_POST['duration']) ? $_POST['duration'] : null
                ];
            }

            if (!$serviceData) {
                throw new Exception("Invalid service data provided");
            }
            
            // Validate required fields
            if (empty($serviceData['name']) || !isset($serviceData['price']) || !isset($serviceData['duration'])) {
                throw new Exception("Missing required service fields");
            }
            
            // Validate price and duration
            if ($serviceData['price'] < 0) {
                throw new Exception("Price cannot be negative");
            }
            if ($serviceData['duration'] <= 0) {
                throw new Exception("Duration must be positive");
            }
            
            $result = addService($serviceData);
            if (!$result) {
                throw new Exception("Failed to add service");
            }
            
            $response['success'] = true;
            $response['message'] = "Service added successfully";
            break;
            
        case 'updateService':
            $serviceData = $payload;
            if (!$serviceData) {
                $serviceData = [
                    'id' => isset($_POST['id']) ? $_POST['id'] : null,
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'price' => isset($_POST['price']) ? $_POST['price'] : null,
                    'duration' => isset($_POST['duration']) ? $_POST['duration'] : null
                ];
            }

            if (!$serviceData || !isset($serviceData['id'])) {
                throw new Exception("Invalid service data or missing ID");
            }
            
            // Validate price and duration if provided
            if (isset($serviceData['price']) && $serviceData['price'] < 0) {
                throw new Exception("Price cannot be negative");
            }
            if (isset($serviceData['duration']) && $serviceData['duration'] <= 0) {
                throw new Exception("Duration must be positive");
            }
            
            $result = updateService($serviceData);
            if (!$result) {
                throw new Exception("Failed to update service");
            }
            
            $response['success'] = true;
            $response['message'] = "Service updated successfully";
            break;
            
        case 'deleteService':
            $serviceId = $payload['id'] ?? ($_POST['id'] ?? null);
            if (!$serviceId) {
                throw new Exception("Service ID is required");
            }

            $result = deleteService($serviceId);
            if (!$result) {
                throw new Exception("Failed to delete service");
            }
            
            $response['success'] = true;
            $response['message'] = "Service deleted successfully";
            break;
            
        case 'generateBill':
            // Accept bookingId/items either via JSON payload or POST fields
            $bookingId = $payload['bookingId'] ?? ($_POST['bookingId'] ?? null);
            $items = $payload['items'] ?? (isset($_POST['items']) ? json_decode($_POST['items'], true) : []);

            if (!$bookingId) {
                throw new Exception("Booking ID is required");
            }

            // Normalize items
            if (!is_array($items)) $items = [];

            // Validate items
            foreach ($items as $item) {
                if (!isset($item['name']) || !isset($item['price'])) {
                    throw new Exception("Invalid item data");
                }
                if ($item['price'] < 0) {
                    throw new Exception("Item price cannot be negative");
                }
            }

            $result = generateBill($bookingId, $items);
            if (!$result['success']) {
                throw new Exception($result['error'] ?? "Failed to generate bill");
            }
            
            $response['success'] = true;
            $response['data'] = $result;
            $response['message'] = "Bill generated successfully";
            break;
            
        case 'getCompletedBookings':
            $response['data'] = getCompletedBookings();
            $response['success'] = true;
            break;
            
        case 'updateBookingStatus':
            $bookingId = $payload['bookingId'] ?? ($_POST['bookingId'] ?? null);
            $status = $payload['status'] ?? ($_POST['status'] ?? null);

            if (!$bookingId || !$status) {
                throw new Exception("Booking ID and status are required");
            }

            // Validate status
            $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status provided");
            }

            $result = updateBookingStatus($bookingId, $status);
            if (!$result) {
                throw new Exception("Failed to update booking status");
            }
            
            $response['success'] = true;
            $response['message'] = "Booking status updated successfully";
            break;
            
        case 'searchBookings':
            $searchTerm = $payload['search'] ?? ($_POST['search'] ?? '');
            $fromDate = $payload['fromDate'] ?? ($_POST['fromDate'] ?? '');
            $toDate = $payload['toDate'] ?? ($_POST['toDate'] ?? '');
            $status = $payload['status'] ?? ($_POST['status'] ?? '');

            $response['data'] = searchBookings($searchTerm, $fromDate, $toDate, $status);
            $response['success'] = true;
            break;
            
        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    // Log the error for debugging
    error_log("Admin AJAX Error: " . $e->getMessage());
    http_response_code(400); // Set appropriate error status code
}

// Send JSON response
echo json_encode($response);
?>