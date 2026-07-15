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

        // Handle checkedInAt - properly set to NULL if needed
        $checkedInAt = null;
        if (isset($data['checkedInAt']) && $data['checkedInAt'] !== null && $data['checkedInAt'] !== '') {
            // $checkedInAt = $data['checkedInAt'];
            $checkedInAt = date('Y-m-d, H:i:s', strtotime($data['checkedInAt']));
        }

        // Preserve the existing category for older clients that do not send it.
        $category = $data['category'] ?? null;
        if ($category !== null && !in_array($category, ['mitra', 'jamaah'], true)) {
            $category = null;
        }

        $stmt = $conn->prepare("
            UPDATE reservations
            SET guestName = ?, seatLabel = ?, allowedGuests = ?, phone = ?, status = ?, category = COALESCE(?, category), checkedInAt = ?
            WHERE id = ?
        ");

        // Type: s=string, i=integer
        // guestName(s), seatLabel(s), allowedGuests(i), phone(s), status(s), checkedInAt(s/null), id(i)
        $stmt->bind_param(
            "ssissssi",
            $data['guestName'],
            $data['seatLabel'],
            $data['allowedGuests'],
            $data['phone'],
            $data['status'],
            $category,
            $checkedInAt,
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
