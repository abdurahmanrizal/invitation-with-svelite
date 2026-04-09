<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$config = require_once __DIR__ . '/config.php';

// Get the reservation ID from URL
// $requestUri = $_SERVER['REQUEST_URI'];
// preg_match('/reservations\/(\d+)/', $requestUri, $matches);


try {
    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // Update reservation
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reservationId = $data['id'] ?? null;

        if (!$reservationId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Reservation ID is required'
            ]);
            exit;
        }
        // echo json_encode($data);
        // exit;
        $stmt = $conn->prepare("
            UPDATE reservations
            SET guestName = ?, seatLabel = ?, allowedGuests = ?, phone = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssss",
            $data['guestName'],
            $data['seatLabel'],
            $data['allowedGuests'],
            $data['phone'],
            $data['status'],
            $reservationId
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation updated successfully'
            ]);
        } else {
            throw new Exception("Failed to update reservation");
        }
        $stmt->close();
    }

    // Delete reservation
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservationId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservation deleted successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Reservation not found'
                ]);
            }
        } else {
            throw new Exception("Failed to delete reservation");
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
