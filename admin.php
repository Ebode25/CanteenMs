<?php
require_once 'config.php';
requireAuth();

// Only allow admin access
if ($_SESSION['role'] !== 'admin') {
    header('Location: menu.php');
    exit();
}

$conn = getDBConnection();
$message = '';

// Handle all form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['newUser']);
        $role = $_POST['userRole'];
        $tempPassword = bin2hex(random_bytes(4));
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, password, role) VALUES (?, ?, ?)");
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $username, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $message = "User added successfully! Temporary password: $tempPassword";
        } else {
            $message = "Error adding user";
        }
    } 
    // Add menu item with image
    elseif (isset($_POST['add_menu_item'])) {
        $name = trim($_POST['itemName']);
        $price = $_POST['itemPrice'];
        $stock = $_POST['itemStock'];
        $day = $_POST['dayOfWeek'];
        $category = $_POST['category'];
        
        // Image upload handling
        $imagePath = 'default_food.jpg';
        if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'Images/';
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $fileType = strtolower(pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileType, $allowedTypes)) {
                $newFilename = uniqid('menu_', true) . '.' . $fileType;
                $uploadFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $uploadFile)) {
                    $imagePath = $newFilename;
                }
            }
        }
        
        $available = $stock > 0 ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO menu_items (name, price, available, day_of_week, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdisss", $name, $price, $available, $day, $category, $imagePath);
        
        if ($stmt->execute()) {
            $message = "Menu item added successfully!";
        } else {
            $message = "Error adding menu item: " . $conn->error;
        }
    }
    // Update order status
    elseif (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        
        if ($stmt->execute()) {
            $message = "Order status updated successfully!";
        } else {
            $message = "Error updating order status";
        }
    }
}

// Handle GET actions (delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $itemId = (int)$_GET['id'];
    
    // First get image path to delete the file
    $stmt = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if ($item) {
        // Delete the menu item
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        
        if ($stmt->execute()) {
            // Delete the image file if it's not the default
            if ($item['image_path'] !== 'default_food.jpg') {
                @unlink('Images/' . $item['image_path']);
            }
            $message = "Menu item deleted successfully!";
        } else {
            $message = "Error deleting menu item";
        }
    }
}

// Fetch data
$users = $conn->query("SELECT * FROM users ORDER BY role, full_name")->fetch_all(MYSQLI_ASSOC);
$menuItems = $conn->query("SELECT * FROM menu_items ORDER BY day_of_week, name")->fetch_all(MYSQLI_ASSOC);
$orders = $conn->query("
    SELECT o.*, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Previous styles remain */
    .admin-container { max-width: 1200px; margin: 20px auto; padding: 20px; }
    .admin-section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    .admin-table th { background-color: #f8f9fa; }
    .admin-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-group { margin-bottom: 15px; }
    .image-upload-container { margin-bottom: 15px; }
    .image-preview { max-width: 200px; max-height: 200px; margin-top: 10px; display: none; }
    .current-image { max-width: 100px; max-height: 100px; margin: 10px 0; }
    .action-links a { margin-right: 10px; }
    .status-select { padding: 5px; border-radius: 4px; }
    .alert { padding: 10px; margin-bottom: 20px; border-radius: 6px; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    .logout-btn { float: right; padding: 5px 10px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="admin-container">
    <h2>Admin Panel - Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      <a href="logout.php" class="logout-btn">Logout</a>
    </h2>
    
    <?php if ($message): ?>
      <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <!-- User Management -->
    <div class="admin-section">
      <h3>User Management</h3>
      <form method="POST" class="admin-form">
        <div class="form-group">
          <label for="newUser">Username</label>
          <input type="text" id="newUser" name="newUser" required />
        </div>
        <div class="form-group">
          <label for="userRole">Role</label>
          <select id="userRole" name="userRole" required>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <button type="submit" name="add_user">Add User</button>
        </div>
      </form>
      
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['id']); ?></td>
              <td><?php echo htmlspecialchars($user['full_name']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td><?php echo htmlspecialchars($user['role']); ?></td>
              <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Menu Management -->
    <div class="admin-section">
      <h3>Menu Management</h3>
      <form method="POST" enctype="multipart/form-data" class="admin-form">
        <div class="form-group">
          <label for="itemName">Item Name</label>
          <input type="text" id="itemName" name="itemName" required />
        </div>
        <div class="form-group">
          <label for="itemPrice">Price (FCFA)</label>
          <input type="number" id="itemPrice" name="itemPrice" step="0.01" min="0" required />
        </div>
        <div class="form-group">
          <label for="itemStock">Initial Stock</label>
          <input type="number" id="itemStock" name="itemStock" min="0" required />
        </div>
        <div class="form-group">
          <label for="dayOfWeek">Day of Week</label>
          <select id="dayOfWeek" name="dayOfWeek" required>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
          </select>
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="Soup">Soup</option>
            <option value="Rice Dish">Rice Dish</option>
            <option value="Drink">Drink</option>
            <option value="Traditional">Traditional</option>
            <option value="Combo">Combo</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group image-upload-container">
          <label for="itemImage">Item Image</label>
          <input type="file" id="itemImage" name="itemImage" accept="image/*" />
          <img id="imagePreview" class="image-preview" src="#" alt="Image Preview" />
        </div>
        <div class="form-group">
          <button type="submit" name="add_menu_item">Add Menu Item</button>
        </div>
      </form>
      
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Available</th>
            <th>Day</th>
            <th>Category</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menuItems as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['id']); ?></td>
              <td>
                <img src="Images/<?php echo htmlspecialchars($item['image_path'] ?? 'default_food.jpg'); ?>" 
                     class="current-image" 
                     alt="<?php echo htmlspecialchars($item['name']); ?>">
              </td>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td><?php echo htmlspecialchars($item['price']); ?> FCFA</td>
              <td><?php echo $item['available'] ? 'Yes' : 'No'; ?></td>
              <td><?php echo htmlspecialchars($item['day_of_week']); ?></td>
              <td><?php echo htmlspecialchars($item['category']); ?></td>
              <td class="action-links">
                <a href="edit_item.php?id=<?php echo $item['id']; ?>">Edit</a>
                <a href="admin.php?action=delete&id=<?php echo $item['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Order Management -->
    <div class="admin-section">
      <h3>Recent Orders</h3>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td><?php echo htmlspecialchars($order['id']); ?></td>
              <td><?php echo htmlspecialchars($order['full_name']); ?></td>
              <td><?php echo htmlspecialchars($order['total_amount']); ?> FCFA</td>
              <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
              <td>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                  <select name="new_status" class="status-select" 
                          onchange="this.form.submit()">
                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                  </select>
                  <input type="hidden" name="update_status" value="1">
                </form>
              </td>
              <td><?php echo date('M j, Y g:i a', strtotime($order['created_at'])); ?></td>
              <td>
                <a href="view_order.php?id=<?php echo $order['id']; ?>">View Items</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Image preview functionality
    document.getElementById('itemImage').addEventListener('change', function(e) {
      const preview = document.getElementById('imagePreview');
      const file = e.target.files[0];
      const reader = new FileReader();
      
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      }
      
      if (file) {
        reader.readAsDataURL(file);
      }
    });

    // Confirm before deleting items
    document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
      link.addEventListener('click', function(e) {
        if (!confirm(this.getAttribute('onclick').match(/'(.*?)'/)[1])) {
          e.preventDefault();
        }
      });
    });
  </script>
</body>
</html>