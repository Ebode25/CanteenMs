<?php
require_once 'config.php';

// Initialize error/success messages
$error = '';
$success = '';

// Check for success message from registration
if (isset($_GET['success'])){
    if ($_GET['success'] === 'registered') {
        $success = 'Registration successful! Please log in.';
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR full_name = ?) AND role = ?");
    $stmt->bind_param("sss", $username, $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'menu.php'));
            exit();
        } else {
            $error = 'Invalid password. Please try again.';
        }
    } else {
        $error = 'No account found with those credentials.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ICTU Canteen Management - Login</title>
  <style>
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
    <h2 id="formTitle">Login</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="authForm" method="POST" action="login.php">
      <div class="inputBox">
        <label for="username">Username or Email</label>
        <input type="text" id="username" name="username" required />
      </div>
      <div class="inputBox">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>
      <div class="inputBox">
        <label for="role">Role</label>
        <select id="role" name="role" required>
          <option value="student">Student/Parent</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="links">
        <a href="register.php">Don't have an account? Register</a>
      </div>
      <button type="submit">Login</button>
    </form>
  </div>

  <script>
    // Client-side form validation
    document.getElementById('authForm').addEventListener('submit', function(e) {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;
      
      if (!username || !password || !role) {
        e.preventDefault();
        alert('Please fill in all fields');
      }
    });
  </script>
</body>
</html>