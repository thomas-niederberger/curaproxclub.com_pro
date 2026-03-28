<?php
require_once __DIR__ . '/../partials/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'] ?? '';
$header = $data['header'] ?? '';
$descriptionShort = $data['description_short'] ?? '';
$description = $data['description'] ?? '';
$icon = $data['icon'] ?? '';
$isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (empty($name) || empty($header)) {
    echo json_encode(['success' => false, 'error' => 'Name and header are required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get the next sort_sidebar value
    $stmt = $pdo->query('SELECT COALESCE(MAX(sort_sidebar), 0) + 1 AS next_order FROM page');
    $nextOrder = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    
    $stmt = $pdo->prepare('
        INSERT INTO page (name, header, description_short, description, icon, is_active, sort_sidebar, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    
    $stmt->execute([
        $name,
        $header,
        $descriptionShort,
        $description,
        $icon,
        $isActive,
        $nextOrder
    ]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
