<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();
requireRole('admin');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['form_id']) || !isset($input['question_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$formId = $input['form_id'];
$questionId = $input['question_id'];

try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM form WHERE id = ?");
    $stmt->execute([$formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        echo json_encode(['success' => false, 'error' => 'Form not found']);
        exit;
    }
    
    $questions = json_decode($form['questions'], true);
    
    $questions = array_filter($questions, function($question) use ($questionId) {
        return $question['id'] !== $questionId;
    });
    
    $questions = array_values($questions);
    
    $stmt = $pdo->prepare("UPDATE form SET questions = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([json_encode($questions), $formId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('settings_forms_delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}
