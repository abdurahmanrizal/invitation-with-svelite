<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$config = require_once __DIR__ . '/config.php';

// Get the reservation code from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract reservation code from path like /api/reservation/UMR-ARIF-001
if (preg_match('/\/api\/reservation\/([^\/]+)/', $path, $matches)) {
    $reservationCode = $matches[1];
} else {
    // Try from query parameter
    $reservationCode = $_GET['code'] ?? '';
}

if (empty($reservationCode)) {
    echo json_encode([
        'valid' => false,
        'message' => 'Reservation code is required'
    ]);
    exit;
}

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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get reservation details
        $stmt = $conn->prepare("
            SELECT reservationCode, guestName, seatLabel, allowedGuests, phone, status, checkedInAt
            FROM reservations
            WHERE LOWER(reservationCode) = LOWER(?)
        ");
        $stmt->bind_param("s", $reservationCode);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($reservationCode, $guestName, $seatLabel, $allowedGuests, $phone, $status, $checkedInAt);
            $stmt->fetch();
            echo json_encode([
                'valid' => true,
                'reservationCode' => $reservationCode,
                'guestName' => $guestName,
                'seatLabel' => $seatLabel,
                'allowedGuests' => (int)$allowedGuests,
                'status' => $status
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'valid' => false,
                'message' => 'Reservation not found'
            ]);
        }
        $stmt->close();

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check in reservation
        $stmt = $conn->prepare("
            SELECT status FROM reservations
            WHERE LOWER(reservationCode) = LOWER(?)
        ");
        $stmt->bind_param("s", $reservationCode);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($status);
            $stmt->fetch();

            if ($status === 'checked_in') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Guest has already been checked in',
                    'reservationCode' => $reservationCode
                ]);
            } else {
                // Update status to checked_in
                $updateStmt = $conn->prepare("
                    UPDATE reservations
                    SET status = 'checked_in', checkedInAt = NOW()
                    WHERE LOWER(reservationCode) = LOWER(?)
                ");
                $updateStmt->bind_param("s", $reservationCode);
                $updateStmt->execute();

                // Get updated reservation
                $selectStmt = $conn->prepare("
                    SELECT reservationCode, guestName, status, checkedInAt
                    FROM reservations
                    WHERE LOWER(reservationCode) = LOWER(?)
                ");
                $selectStmt->bind_param("s", $reservationCode);
                $selectStmt->execute();
                $selectStmt->store_result();
                $selectStmt->bind_result($reservationCode, $guestName, $status, $checkedInAt);
                $selectStmt->fetch();

                echo json_encode([
                    'success' => true,
                    'message' => 'Check-in successful',
                    'reservationCode' => $reservationCode,
                    'guestName' => $guestName,
                    'status' => $status,
                    'checkedInAt' => $checkedInAt
                ]);
                $selectStmt->close();
                $updateStmt->close();
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Reservation not found'
            ]);
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
