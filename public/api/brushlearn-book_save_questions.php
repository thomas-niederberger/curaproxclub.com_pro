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

$bookingId = $data['booking_id'] ?? null;
$profileId = $data['profile_id'] ?? null;
$formId = $data['form_id'] ?? null;
$answers = $data['answers'] ?? [];

if (!$bookingId || !$profileId || !$formId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Check if form_response already exists for this booking
    $stmt = $pdo->prepare('SELECT form_response_id FROM ohc_booking WHERE id = ? AND profile_id = ?');
    $stmt->execute([$bookingId, $profileId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    $formResponseId = $booking['form_response_id'];
    
    if ($formResponseId) {
        // Update existing form_response
        $stmt = $pdo->prepare('
            UPDATE form_response 
            SET answers = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([json_encode($answers), $formResponseId]);
    } else {
        // Create new form_response
        $stmt = $pdo->prepare('
            INSERT INTO form_response (form_id, profile_id, answers, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$formId, $profileId, json_encode($answers)]);
        
        $formResponseId = $pdo->lastInsertId();
        
        // Link form_response to booking
        $stmt = $pdo->prepare('
            UPDATE ohc_booking 
            SET form_response_id = ?, status = ?
            WHERE id = ?
        ');
        $stmt->execute([$formResponseId, 'draft', $bookingId]);
    }
    
    echo json_encode([
        'success' => true,
        'form_response_id' => $formResponseId,
        'message' => 'Questions saved'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
