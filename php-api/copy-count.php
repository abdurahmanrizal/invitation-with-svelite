<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    // Increment copy count
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Increment copy count and return new value
        $stmt = $conn->prepare("
            UPDATE reservations
            SET copyCount = copyCount + 1
            WHERE id = ?
        ");
        $stmt->bind_param("i", $reservationId);

        if ($stmt->execute()) {
            // Get the updated copy count
            $selectStmt = $conn->prepare("
                SELECT copyCount FROM reservations WHERE id = ?
            ");
            $selectStmt->bind_param("i", $reservationId);
            $selectStmt->execute();
            $selectStmt->bind_result($copyCount);
            $selectStmt->fetch();
            $selectStmt->close();

            echo json_encode([
                'success' => true,
                'copyCount' => $copyCount ?? 0,
                'message' => 'Copy count incremented'
            ]);
        } else {
            throw new Exception("Failed to increment copy count");
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
