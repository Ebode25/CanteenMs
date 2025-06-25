<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();
    
    // Calculate total
    $total = 0;
    $items = [];
    
    foreach ($_SESSION['cart'] as $itemId) {
        $stmt = $conn->prepare("SELECT price FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        $total += $item['price'];
        $items[] = ['id' => $itemId, 'price' => $item['price']];
    }
    
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method) VALUES (?, ?, ?)");
    $paymentMethod = $_POST['payment_method']; // From your payment form
    $stmt->bind_param("ids", $_SESSION['user_id'], $total, $paymentMethod);
    $stmt->execute();
    $orderId = $conn->insert_id;
    
    // Add order items
    foreach ($items as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, price) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $orderId, $item['id'], $item['price']);
        $stmt->execute();
    }
    
    $conn->commit();
    $_SESSION['cart'] = []; // Clear cart
    
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>