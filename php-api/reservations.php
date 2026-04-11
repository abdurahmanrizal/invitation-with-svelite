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

    // Get all reservations
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("
            SELECT id, reservationCode, guestName, seatLabel, allowedGuests, phone, status, checkedInAt, copyCount
            FROM reservations
            ORDER BY createdAt DESC
        ");

        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }

        echo json_encode([
            'success' => true,
            'reservations' => $reservations
        ]);
    }

    // Create new reservation
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($data['guestName'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Guest name is required'
            ]);
            exit;
        }

        // Auto-generate reservation code
        $result = $conn->query("
            SELECT reservationCode
            FROM reservations
            WHERE reservationCode LIKE 'SULTAN-UMR-%'
            ORDER BY CAST(SUBSTRING_INDEX(reservationCode, '-', -1) AS UNSIGNED) DESC
            LIMIT 1
        ");

        $nextNumber = 1;
        if ($result && $row = $result->fetch_assoc()) {
            $lastCode = $row['reservationCode'];
            $lastNumber = (int)substr(strrchr($lastCode, '-'), 1);
            $nextNumber = $lastNumber + 1;
        }

        $data['reservationCode'] = 'SULTAN-UMR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Check if reservation code already exists
        $checkStmt = $conn->prepare("
            SELECT id FROM reservations
            WHERE LOWER(reservationCode) = LOWER(?)
        ");
        $checkStmt->bind_param("s", $data['reservationCode']);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Reservation code already exists'
            ]);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();

        // Set default values for nullable fields
        if (!isset($data['seatLabel'])) $data['seatLabel'] = null;
        if (!isset($data['allowedGuests'])) $data['allowedGuests'] = 1;
        if (!isset($data['phone'])) $data['phone'] = null;
        if (!isset($data['status'])) $data['status'] = 'pending';

        // Insert new reservation
        $stmt = $conn->prepare("
            INSERT INTO reservations (reservationCode, guestName, seatLabel, allowedGuests, phone, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssiss",
            $data['reservationCode'],
            $data['guestName'],
            $data['seatLabel'],
            $data['allowedGuests'],
            $data['phone'],
            $data['status']
        );

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Reservation created successfully',
                'id' => $newId
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create reservation'
            ]);
            throw new Exception("Failed to create reservation");
        }
        $stmt->close();
    }
    // delete new reservation
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reservationId = $data['id'] ?? null;
        $stmt = $conn->prepare("
            DELETE FROM reservations
            WHERE id = ?
        ");
        $stmt->bind_param("i", $reservationId);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete reservation'
            ]);
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
