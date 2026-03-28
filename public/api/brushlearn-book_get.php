<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$profileId = $_GET['profile_id'] ?? null;

if (!$profileId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing profile_id']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT b.*, fr.answers, fr.form_id
        FROM ohc_booking b
        LEFT JOIN form_response fr ON b.form_response_id = fr.id
        WHERE b.profile_id = ? 
        AND b.status IN ("draft")
        ORDER BY b.created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$profileId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        // Decode answers if they exist
        if ($booking['answers']) {
            $booking['answers'] = json_decode($booking['answers'], true);
        }
        
        echo json_encode([
            'success' => true,
            'booking' => $booking
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'booking' => null
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
