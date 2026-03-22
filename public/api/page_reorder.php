<?php
require_once __DIR__ . '/../partials/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? '';
$direction = $data['direction'] ?? '';

if (empty($id) || !in_array($direction, ['up', 'down'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get current page
    $stmt = $pdo->prepare('SELECT id, sort_order FROM page WHERE id = ?');
    $stmt->execute([$id]);
    $currentPage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentPage) {
        echo json_encode(['success' => false, 'error' => 'Page not found']);
        exit;
    }
    
    $currentOrder = $currentPage['sort_order'];
    
    // Find the page to swap with
    if ($direction === 'up') {
        $stmt = $pdo->prepare('
            SELECT id, sort_order 
            FROM page 
            WHERE sort_order < ? 
            ORDER BY sort_order DESC 
            LIMIT 1
        ');
    } else {
        $stmt = $pdo->prepare('
            SELECT id, sort_order 
            FROM page 
            WHERE sort_order > ? 
            ORDER BY sort_order ASC 
            LIMIT 1
        ');
    }
    
    $stmt->execute([$currentOrder]);
    $swapPage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$swapPage) {
        echo json_encode(['success' => false, 'error' => 'Cannot move in that direction']);
        exit;
    }
    
    // Swap sort orders
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare('UPDATE page SET sort_order = ? WHERE id = ?');
    $stmt->execute([$swapPage['sort_order'], $id]);
    
    $stmt = $pdo->prepare('UPDATE page SET sort_order = ? WHERE id = ?');
    $stmt->execute([$currentOrder, $swapPage['id']]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
