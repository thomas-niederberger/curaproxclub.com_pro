<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);

$profileId = $currentProfileId;
$locationId = $data['location_id'] ?? null;
$isVirtual  = $data['is_virtual'] ?? 0;
$bookingId  = $data['booking_id'] ?? null;

try {
    if ($bookingId) {
        // Update existing booking — AND profile_id is pinned to session so users
        $stmt = $pdo->prepare('
            UPDATE ohc_booking 
            SET location_id = ?, is_virtual = ?, status = ?
            WHERE id = ? AND profile_id = ?
        ');
        $stmt->execute([$locationId, $isVirtual, 'draft', $bookingId, $profileId]);

        echo json_encode([
            'success'    => true,
            'booking_id' => $bookingId,
            'message'    => 'Booking updated'
        ]);
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO ohc_booking (profile_id, location_id, is_virtual, status, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$profileId, $locationId, $isVirtual, 'draft']);

        echo json_encode([
            'success'    => true,
            'booking_id' => $pdo->lastInsertId(),
            'message'    => 'Booking created'
        ]);
    }
} catch (PDOException $e) {
    error_log('brushlearn-book_create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}