<?php
require_once 'config.php';
requireAuth();

if ($_SESSION['role'] !== 'admin') {
    header('Location: menu.php');
    exit();
}

$orderId = $_GET['id'] ?? 0;
$conn = getDBConnection();

// Get order details
$order = $conn->query("
    SELECT o.*, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = $orderId
")->fetch_assoc();

// Get order items
$items = $conn->query("
    SELECT oi.*, mi.name, mi.image_path 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    WHERE oi.order_id = $orderId
")->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_column($items, 'price'));
?>

<!DOCTYPE html>
<html>
<head>
  <title>Order Details</title>
  <style>
    .order-container {
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .order-header {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    .order-items {
      margin-top: 20px;
    }
    .order-item {
      display: flex;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #f5f5f5;
    }
    .order-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      margin-right: 15px;
      border-radius: 4px;
    }
    .item-info {
      flex-grow: 1;
    }
    .item-price {
      font-weight: bold;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 8px 15px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .order-total {
      font-size: 1.2em;
      font-weight: bold;
      text-align: right;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="order-container">
    <div class="order-header">
      <h2>Order #<?php echo $order['id']; ?></h2>
      <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
      <p><strong>Date:</strong> <?php echo date('F j, Y g:i a', strtotime($order['created_at'])); ?></p>
      <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
      <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
    </div>
    
    <div class="order-items">
      <h3>Ordered Items</h3>
      <?php foreach ($items as $item): ?>
        <div class="order-item">
          <img src="Images/<?php echo htmlspecialchars($item['image_path'] ?? 'default_food.jpg'); ?>" 
               alt="<?php echo htmlspecialchars($item['name']); ?>">
          <div class="item-info">
            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
          </div>
          <div class="item-price">
            <?php echo htmlspecialchars($item['price']); ?> FCFA
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="order-total">
      Total: <?php echo number_format($total, 2); ?> FCFA
    </div>
    
    <a href="admin.php" class="back-btn">Back to Admin Panel</a>
  </div>
</body>
</html>