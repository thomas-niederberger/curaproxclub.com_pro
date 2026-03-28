<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['form_id']) || !isset($input['label']) || !isset($input['type'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$formId = (int) $input['form_id'];
$label = trim((string) $input['label']);
$type = (string) $input['type'];
$required = isset($input['required']) ? (bool) $input['required'] : false;

if ($label === '') {
    echo json_encode(['success' => false, 'error' => 'Question text is required']);
    exit;
}

if (!in_array($type, ['text', 'textarea', 'select'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid question type']);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT * FROM form WHERE id = ?');
    $stmt->execute([$formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        echo json_encode(['success' => false, 'error' => 'Form not found']);
        exit;
    }

    $questions = json_decode($form['questions'], true);
    $questions = is_array($questions) ? $questions : [];

    $baseId = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
    if ($baseId === '') {
        $baseId = 'question';
    }

    $existingIds = [];
    foreach ($questions as $question) {
        if (isset($question['id'])) {
            $existingIds[(string) $question['id']] = true;
        }
    }

    $questionId = $baseId;
    $suffix = 2;
    while (isset($existingIds[$questionId])) {
        $questionId = $baseId . '_' . $suffix;
        $suffix++;
    }

    $newQuestion = [
        'id' => $questionId,
        'label' => $label,
        'type' => $type,
        'required' => $required,
    ];

    $questions[] = $newQuestion;

    $stmt = $pdo->prepare('UPDATE form SET questions = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([json_encode($questions), $formId]);

    echo json_encode(['success' => true, 'question' => $newQuestion]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
