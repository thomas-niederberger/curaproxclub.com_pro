<?php
require_once __DIR__ . '/../partials/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$draggedId = $data['dragged_id'] ?? '';
$targetId = $data['target_id'] ?? '';

if (empty($draggedId) || empty($targetId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get both pages
    $stmt = $pdo->prepare('SELECT id, sort_order FROM page WHERE id IN (?, ?)');
    $stmt->execute([$draggedId, $targetId]);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pages) !== 2) {
        echo json_encode(['success' => false, 'error' => 'Pages not found']);
        exit;
    }
    
    $draggedPage = null;
    $targetPage = null;
    
    foreach ($pages as $page) {
        if ($page['id'] == $draggedId) {
            $draggedPage = $page;
        } else {
            $targetPage = $page;
        }
    }
    
    // Get all pages ordered by sort_order
    $stmt = $pdo->query('SELECT id, sort_order FROM page ORDER BY sort_order ASC');
    $allPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove dragged page from its current position
    $filteredPages = array_filter($allPages, function($p) use ($draggedId) {
        return $p['id'] != $draggedId;
    });
    $filteredPages = array_values($filteredPages);
    
    // Find target position
    $targetPosition = 0;
    foreach ($filteredPages as $index => $page) {
        if ($page['id'] == $targetId) {
            $targetPosition = $index;
            break;
        }
    }
    
    // Insert dragged page before target
    array_splice($filteredPages, $targetPosition, 0, [$draggedPage]);
    
    // Update all sort orders
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare('UPDATE page SET sort_order = ? WHERE id = ?');
    foreach ($filteredPages as $index => $page) {
        $newOrder = ($index + 1) * 10; // Use increments of 10 for easier manual adjustments
        $stmt->execute([$newOrder, $page['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
