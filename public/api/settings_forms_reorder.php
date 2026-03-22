<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['form_id']) || !isset($input['questions'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$formId = $input['form_id'];
$questionOrder = $input['questions'];

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
    
    $orderMap = [];
    foreach ($questionOrder as $item) {
        $orderMap[$item['id']] = $item['order'];
    }
    
    usort($questions, function($a, $b) use ($orderMap) {
        $orderA = isset($orderMap[$a['id']]) ? $orderMap[$a['id']] : 999;
        $orderB = isset($orderMap[$b['id']]) ? $orderMap[$b['id']] : 999;
        return $orderA - $orderB;
    });
    
    $stmt = $pdo->prepare("UPDATE form SET questions = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([json_encode($questions), $formId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
