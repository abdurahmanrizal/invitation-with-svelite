<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$config = require_once __DIR__ . '/config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $rows = $data['rows'] ?? [];
    $category = $data['category'] ?? '';

    if (!in_array($category, ['jamaah', 'mitra'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kategori tidak valid']);
        exit();
    }

    if (!is_array($rows) || count($rows) === 0 || count($rows) > 500) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Data harus berisi 1 sampai 500 baris'
        ]);
        exit();
    }

    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    $conn->begin_transaction();

    $result = $conn->query("
        SELECT reservationCode
        FROM reservations
        WHERE reservationCode LIKE 'SULTAN-UMR-%'
        ORDER BY CAST(SUBSTRING_INDEX(reservationCode, '-', -1) AS UNSIGNED) DESC
        LIMIT 1
        FOR UPDATE
    ");

    $nextNumber = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $nextNumber = ((int) substr(strrchr($row['reservationCode'], '-'), 1)) + 1;
    }

    $stmt = $conn->prepare("
        INSERT INTO reservations
            (reservationCode, guestName, seatLabel, allowedGuests, phone, status, category)
        VALUES (?, ?, NULL, 1, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare import statement');
    }

    $imported = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        $excelRow = $index + 2;
        $guestName = trim((string) ($row['guestName'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $status = strtolower(trim((string) ($row['status'] ?? 'pending')));

        if ($guestName === '') {
            $errors[] = ['row' => $excelRow, 'message' => 'Nama wajib diisi'];
            continue;
        }

        if (mb_strlen($guestName) > 255) {
            $errors[] = ['row' => $excelRow, 'message' => 'Nama maksimal 255 karakter'];
            continue;
        }

        if (!in_array($status, ['pending', 'confirmed', 'checked_in'], true)) {
            $errors[] = ['row' => $excelRow, 'message' => 'Status tidak valid'];
            continue;
        }

        $reservationCode = 'SULTAN-UMR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $stmt->bind_param('sssss', $reservationCode, $guestName, $phone, $status, $category);

        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan baris ' . $excelRow);
        }

        $nextNumber++;
        $imported++;
    }

    $stmt->close();
    $conn->commit();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => $imported . ' reservasi berhasil diimpor',
        'imported' => $imported,
        'failed' => count($errors),
        'errors' => $errors
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
