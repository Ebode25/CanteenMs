<?php
require_once 'config.php';
requireAuth();

if ($_SESSION['role'] !== 'admin') {
    header('Location: menu.php');
    exit();
}

$conn = getDBConnection();

// Get item ID to delete
$itemId = $_GET['id'] ?? 0;

// First get the image path
$stmt = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if ($item) {
    // Delete the item
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    
    if ($stmt->execute()) {
        // Delete the associated image if it's not the default
        if ($item['image_path'] !== 'default_food.jpg') {
            @unlink('Images/' . $item['image_path']);
        }
        $_SESSION['message'] = "Item deleted successfully";
    } else {
        $_SESSION['message'] = "Error deleting item";
    }
}

header('Location: admin.php');
exit();
?>