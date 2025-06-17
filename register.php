<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $password = $_POST['passwordReg'];
    $role = $_POST['roleReg'];
    
    // Validate inputs
    if (empty($fullName) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        $conn = getDBConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullName, $email, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                $_POST = array(); // Clear form
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ICTU Canteen Management - Register</title>
  <style>
    /* Same styles as login.php */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f2f4f8;
      color: #333;
      line-height: 1.6;
      min-height: 100vh;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      width: 100%;
      max-width: 400px;
      padding: 30px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    .logo {
      display: block;
      margin: 0 auto 20px auto;
      max-width: 100px;
      height: auto;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .inputBox {
      margin-bottom: 15px;
    }

    .inputBox label {
      display: block;
      margin-bottom: 5px;
    }

    .inputBox input,
    .inputBox select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    .links {
      text-align: center;
      margin-top: 10px;
    }

    .links a {
      color: #007bff;
      text-decoration: none;
      cursor: pointer;
      font-size: 14px;
    }

    .links a:hover {
      text-decoration: underline;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }

    button:hover {
      background-color: #0056b3;
    }

    .alert {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      text-align: center;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="Images/ictuniversity.jpg" alt="ICTU Logo" class="logo">
    <h2>Register</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <div class="inputBox">
        <label for="fullName">Full Name</label>
        <input type="text" id="fullName" name="fullName" value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" required />
      </div>
      <div class="inputBox">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
      </div>
      <div class="inputBox">
        <label for="passwordReg">Password (min 8 characters)</label>
        <input type="password" id="passwordReg" name="passwordReg" required />
      </div>
      <div class="inputBox">
        <label for="roleReg">Role</label>
        <select id="roleReg" name="roleReg" required>
          <option value="student" <?php echo (isset($_POST['roleReg']) && $_POST['roleReg'] === 'student') ? 'selected' : ''; ?>>Student/Parent</option>
          <option value="staff" <?php echo (isset($_POST['roleReg']) && $_POST['roleReg'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
          <option value="admin" <?php echo (isset($_POST['roleReg']) && $_POST['roleReg'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
      </div>
      <div class="links">
        <a href="index.php">Already have an account? Login</a>
      </div>
      <button type="submit">Register</button>
    </form>
  </div>
</body>
</html>