<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? '';
$name = $data['name'] ?? '';
$header = $data['header'] ?? '';
$descriptionShort = $data['description_short'] ?? '';
$description = $data['description'] ?? '';
$icon = $data['icon'] ?? '';
$isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (empty($id) || empty($name) || empty($header)) {
    echo json_encode(['success' => false, 'error' => 'ID, name, and header are required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare('
        UPDATE page 
        SET name = ?, header = ?, description_short = ?, description = ?, icon = ?, is_active = ?
        WHERE id = ?
    ');
    
    $stmt->execute([
        $name,
        $header,
        $descriptionShort,
        $description,
        $icon,
        $isActive,
        $id
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
