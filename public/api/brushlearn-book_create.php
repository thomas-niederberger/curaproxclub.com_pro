<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);

$profileId = $data['profile_id'] ?? null;
$locationId = $data['location_id'] ?? null;
$isVirtual = $data['is_virtual'] ?? 0;
$bookingId = $data['booking_id'] ?? null; // For updates

try {
    if ($bookingId) {
        // Update existing booking
        $stmt = $pdo->prepare('
            UPDATE ohc_booking 
            SET location_id = ?, is_virtual = ?, status = ?
            WHERE id = ? AND profile_id = ?
        ');
        $stmt->execute([$locationId, $isVirtual, 'draft', $bookingId, $profileId]);
        
        echo json_encode([
            'success' => true,
            'booking_id' => $bookingId,
            'message' => 'Booking updated'
        ]);
    } else {
        // Create new booking
        $stmt = $pdo->prepare('
            INSERT INTO ohc_booking (profile_id, location_id, is_virtual, status, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$profileId, $locationId, $isVirtual, 'draft']);
        
        $newBookingId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'booking_id' => $newBookingId,
            'message' => 'Booking created'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
