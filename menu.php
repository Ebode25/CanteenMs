<?php
require_once 'config.php';
requireAuth();

$conn = getDBConnection();

// Get today's menu items
$today = date('l');
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE day_of_week = ? AND available = 1");
$stmt->bind_param("s", $today);
$stmt->execute();
$menuItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart']) && isset($_POST['item_id'])) {
        $itemId = (int)$_POST['item_id'];
        
        // Verify item exists and is available
        $stmt = $conn->prepare("SELECT id, price FROM menu_items WHERE id = ? AND available = 1");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            $_SESSION['cart'][] = $item;
        }
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    } elseif (isset($_POST['place_order']) && isset($_POST['payment_method'])) {
        if (!empty($_SESSION['cart'])) {
            $conn->begin_transaction();
            
            try {
                // Calculate total
                $total = array_sum(array_column($_SESSION['cart'], 'price'));
                $paymentMethod = $_POST['payment_method'];
                $userId = $_SESSION['user_id'];
                
                // Create order
                $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method) VALUES (?, ?, ?)");
                $stmt->bind_param("ids", $userId, $total, $paymentMethod);
                $stmt->execute();
                $orderId = $conn->insert_id;
                
                // Add order items
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, price) VALUES (?, ?, ?)");
                foreach ($_SESSION['cart'] as $item) {
                    $stmt->bind_param("iid", $orderId, $item['id'], $item['price']);
                    $stmt->execute();
                }
                
                $conn->commit();
                $_SESSION['cart'] = [];
                $success = "Order placed successfully! Your order ID is #$orderId";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error placing order: " . $e->getMessage();
            }
        } else {
            $error = "Your cart is empty";
        }
    }
}

// Calculate cart total
$cartTotal = array_sum(array_column($_SESSION['cart'], 'price'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>ICTU Canteen Menu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f0f2f5;
      padding: 20px;
      color: #333;
    }

    .container {
      max-width: 1100px;
      margin: auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    h2, h3 {
      color: #2c3e50;
      margin-bottom: 20px;
    }

    #userName {
      color: #2980b9;
    }

    .menu-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .meal-card {
      background: #fafafa;
      border: 1px solid #ddd;
      border-radius: 10px;
      width: 300px;
      padding: 15px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .meal-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .meal-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .meal-card h4 {
      margin-bottom: 6px;
      color: #2c3e50;
    }

    .meal-card p {
      margin-bottom: 6px;
      font-size: 14px;
    }

    .add-btn {
      background: #27ae60;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: background 0.3s ease;
    }

    .add-btn:hover {
      background: #219150;
    }

    .add-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .cart-container {
      margin: 30px 0;
      padding: 20px;
      border: 1px solid #eee;
      border-radius: 8px;
    }

    .cart-item {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }

    .cart-total {
      font-weight: bold;
      margin-top: 10px;
      text-align: right;
    }

    .payment-methods {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .pay-btn {
      padding: 10px 20px;
      font-size: 14px;
      border: none;
      border-radius: 6px;
      color: white;
      cursor: pointer;
      transition: opacity 0.3s ease;
    }

    .pay-btn.mtn {
      background: #f7b731;
    }

    .pay-btn.orange {
      background: #fa8231;
    }

    .pay-btn.cash {
      background: #3498db;
    }

    .pay-btn:hover {
      opacity: 0.9;
    }

    .clear-btn {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
    }

    .alert {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 6px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }

    .logout-btn {
      float: right;
      padding: 5px 10px;
      background-color: #dc3545;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      .menu-container {
        flex-direction: column;
        align-items: center;
      }
      .meal-card {
        width: 90%;
      }
      .payment-methods {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <h2>WELCOME, <span id="userName"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </h2>
  
  <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <h3>Today's Menu (<?php echo $today; ?>)</h3>
  <div class="menu-container">
    <?php foreach ($menuItems as $item): ?>
      <div class="meal-card">
        <img src="Images/<?php echo htmlspecialchars($item['image_path'] ?? 'default_food.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" />
        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
        <p><strong>Price:</strong> FCFA <?php echo htmlspecialchars($item['price']); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
        <form method="POST" style="margin-top: 10px;">
          <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>" />
          <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="cart-container">
    <h3>Your Cart</h3>
    
    <?php if (empty($_SESSION['cart'])): ?>
      <p>Your cart is empty</p>
    <?php else: ?>
      <?php foreach ($_SESSION['cart'] as $index => $item): ?>
        <div class="cart-item">
          <span><?php echo htmlspecialchars($item['name'] ?? 'Item #' . ($index + 1)); ?></span>
          <span>FCFA <?php echo htmlspecialchars($item['price']); ?></span>
        </div>
      <?php endforeach; ?>
      
      <div class="cart-total">
        Total: FCFA <?php echo number_format($cartTotal, 2); ?>
      </div>
      
      <form method="POST">
        <button type="submit" name="clear_cart" class="clear-btn">Clear Cart</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($_SESSION['cart'])): ?>
    <div class="payment-section">
      <h3>Payment Method</h3>
      <form method="POST">
        <div class="payment-methods">
          <button type="submit" name="place_order" value="MTN MoMo" class="pay-btn mtn">Pay with MTN MoMo</button>
          <button type="submit" name="place_order" value="Orange Money" class="pay-btn orange">Pay with Orange Money</button>
          <button type="submit" name="place_order" value="Cash" class="pay-btn cash">Pay with Cash</button>
        </div>
        <input type="hidden" name="payment_method" id="paymentMethod" />
      </form>
    </div>
  <?php endif; ?>
</div>

<script>
  // Set payment method when buttons are clicked
  document.querySelectorAll('[name="place_order"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      document.getElementById('paymentMethod').value = this.value;
    });
  });
</script>
</body>
</html>