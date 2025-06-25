<?php
require_once 'config.php';
requireAuth();

if ($_SESSION['role'] !== 'admin') {
    header('Location: menu.php');
    exit();
}

$conn = getDBConnection();
$message = '';

// Get item to edit
$itemId = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header('Location: admin.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['itemName']);
    $price = $_POST['itemPrice'];
    $available = isset($_POST['itemAvailable']) ? 1 : 0;
    $day = $_POST['dayOfWeek'];
    $category = $_POST['category'];
    
    $imagePath = $item['image_path']; // Keep current image by default
    
    // Handle image upload if new file was provided
    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'Images/';
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileType = strtolower(pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
            $newFilename = uniqid('menu_', true) . '.' . $fileType;
            $uploadFile = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $uploadFile)) {
                // Delete old image if it's not the default
                if ($imagePath !== 'default_food.jpg') {
                    @unlink($uploadDir . $imagePath);
                }
                $imagePath = $newFilename;
            }
        }
    }
    
    // Update item in database
    $stmt = $conn->prepare("UPDATE menu_items SET name = ?, price = ?, available = ?, day_of_week = ?, category = ?, image_path = ? WHERE id = ?");
    $stmt->bind_param("sdisssi", $name, $price, $available, $day, $category, $imagePath, $itemId);
    
    if ($stmt->execute()) {
        $message = "Menu item updated successfully!";
        // Refresh item data
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "Error updating menu item: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Menu Item</title>
  <style>
    .edit-container {
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
    }
    .form-group input, 
    .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .current-image {
      max-width: 200px;
      max-height: 200px;
      margin: 10px 0;
    }
    .btn {
      padding: 10px 15px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="edit-container">
    <h2>Edit Menu Item</h2>
    
    <?php if ($message): ?>
      <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="itemName">Item Name</label>
        <input type="text" id="itemName" name="itemName" value="<?php echo htmlspecialchars($item['name']); ?>" required />
      </div>
      <div class="form-group">
        <label for="itemPrice">Price (FCFA)</label>
        <input type="number" id="itemPrice" name="itemPrice" step="0.01" min="0" value="<?php echo htmlspecialchars($item['price']); ?>" required />
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" name="itemAvailable" <?php echo $item['available'] ? 'checked' : ''; ?> />
          Available
        </label>
      </div>
      <div class="form-group">
        <label for="dayOfWeek">Day of Week</label>
        <select id="dayOfWeek" name="dayOfWeek" required>
          <option value="Monday" <?php echo $item['day_of_week'] === 'Monday' ? 'selected' : ''; ?>>Monday</option>
          <option value="Tuesday" <?php echo $item['day_of_week'] === 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
          <option value="Wednesday" <?php echo $item['day_of_week'] === 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
          <option value="Thursday" <?php echo $item['day_of_week'] === 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
          <option value="Friday" <?php echo $item['day_of_week'] === 'Friday' ? 'selected' : ''; ?>>Friday</option>
          <option value="Saturday" <?php echo $item['day_of_week'] === 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
          <option value="Sunday" <?php echo $item['day_of_week'] === 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
        </select>
      </div>
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" required>
          <option value="Soup" <?php echo $item['category'] === 'Soup' ? 'selected' : ''; ?>>Soup</option>
          <option value="Rice Dish" <?php echo $item['category'] === 'Rice Dish' ? 'selected' : ''; ?>>Rice Dish</option>
          <option value="Drink" <?php echo $item['category'] === 'Drink' ? 'selected' : ''; ?>>Drink</option>
          <option value="Traditional" <?php echo $item['category'] === 'Traditional' ? 'selected' : ''; ?>>Traditional</option>
          <option value="Combo" <?php echo $item['category'] === 'Combo' ? 'selected' : ''; ?>>Combo</option>
          <option value="Other" <?php echo $item['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
        </select>
      </div>
      <div class="form-group">
        <label>Current Image</label>
        <img src="Images/<?php echo htmlspecialchars($item['image_path'] ?? 'default_food.jpg'); ?>" class="current-image" />
        <label for="itemImage">Change Image</label>
        <input type="file" id="itemImage" name="itemImage" accept="image/*" />
      </div>
      <button type="submit" class="btn">Update Item</button>
      <a href="admin.php" style="margin-left: 10px;">Cancel</a>
    </form>
  </div>
</body>
</html>