<?php
require_once __DIR__ . '/../partials/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);

$bookingId = $data['booking_id'] ?? null;
$profileId = $data['profile_id'] ?? null;
$calBookingId = $data['cal_booking_id'] ?? null;
$bookingDate = $data['booking_date'] ?? null;

if (!$bookingId || !$profileId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Update booking with Cal.com details and set to booked
    $stmt = $pdo->prepare('
        UPDATE ohc_booking 
        SET cal_booking_id = ?, booking_date = ?, status = ?, updated_at = NOW()
        WHERE id = ? AND profile_id = ?
    ');
    $stmt->execute([$calBookingId, $bookingDate, 'booked', $bookingId, $profileId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    // TODO: Send confirmation email here
    // sendBookingConfirmationEmail($profileId, $bookingId);
    
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'status' => 'confirmed',
        'message' => 'Booking confirmed successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
