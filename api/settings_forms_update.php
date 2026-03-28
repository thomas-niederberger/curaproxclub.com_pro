<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['form_id']) || !isset($input['question_id']) || !isset($input['label']) || !isset($input['type'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$formId = $input['form_id'];
$questionId = $input['question_id'];
$label = $input['label'];
$type = $input['type'];
$required = isset($input['required']) ? (bool)$input['required'] : false;

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
    
    $found = false;
    foreach ($questions as &$question) {
        if ($question['id'] === $questionId) {
            $question['label'] = $label;
            $question['type'] = $type;
            $question['required'] = $required;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Question not found']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE form SET questions = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([json_encode($questions), $formId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
