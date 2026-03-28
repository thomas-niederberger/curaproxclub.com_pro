<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();
requireRole('admin');

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
    $stmt = $pdo->prepare('SELECT id, sort_sidebar FROM page WHERE id = ?');
    $stmt->execute([$id]);
    $currentPage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentPage) {
        echo json_encode(['success' => false, 'error' => 'Page not found']);
        exit;
    }
    
    $currentOrder = $currentPage['sort_sidebar'];
    
    // Find the page to swap with
    if ($direction === 'up') {
        $stmt = $pdo->prepare('
            SELECT id, sort_sidebar 
            FROM page 
            WHERE sort_sidebar < ? 
            ORDER BY sort_sidebar DESC 
            LIMIT 1
        ');
    } else {
        $stmt = $pdo->prepare('
            SELECT id, sort_sidebar 
            FROM page 
            WHERE sort_sidebar > ? 
            ORDER BY sort_sidebar ASC 
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
    
    $stmt = $pdo->prepare('UPDATE page SET sort_sidebar = ? WHERE id = ?');
    $stmt->execute([$swapPage['sort_sidebar'], $id]);
    
    $stmt = $pdo->prepare('UPDATE page SET sort_sidebar = ? WHERE id = ?');
    $stmt->execute([$currentOrder, $swapPage['id']]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('page_reorder error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}
