<?php
session_start();
include '../config.php';

if(isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

    if(mysqli_stmt_execute($stmt)) {
        header("Location: login.php");
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      background-image: url('../IMAGES/signuppage.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .page-logo {
      padding: 8px 40px;
      font-family: 'Frunchy', serif;
      font-size: 4.5rem;
      color: #333;
    }

    .page-logo span {
      font-family: 'Frunchy', serif;
      font-size: 0.3em;
      color: #333;
      font-weight: 400;
    }

    .auth-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .auth-box {
      background: rgba(255, 248, 240, 0.92);
      backdrop-filter: blur(8px);
      padding: 48px 40px;
      border-radius: 20px;
      width: 100%;
      max-width: 480px;
      text-align: center;
    }

    .auth-box h2 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 32px;
      color: #333;
    }

    .auth-box input {
      width: 100%;
      padding: 14px 20px;
      margin-bottom: 16px;
      border: 2px solid #C84B31;
      border-radius: 30px;
      font-size: 0.95rem;
      font-family: 'Inter', sans-serif;
      background: white;
      outline: none;
      color: #333;
    }

    .auth-box input::placeholder {
      color: #aaa;
    }

    .auth-box button {
      background: #C84B31;
      color: white;
      border: none;
      padding: 14px 48px;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      font-family: 'Inter', sans-serif;
    }

    .auth-box button:hover {
      background: #a83828;
    }

    .auth-box p {
      margin-top: 24px;
      color: #555;
      font-size: 0.9rem;
    }

    .auth-box p a {
      color: #C84B31;
      text-decoration: none;
      font-weight: 600;
    }

    .error-msg {
      color: red;
      margin-bottom: 16px;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <div class="page-logo">The Foodies<span>.com</span></div>

  <div class="auth-wrapper">
    <div class="auth-box">
      <h2>Sign-up</h2>
      <?php if(isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
      <form method="POST" action="">
        <input type="text" name="username" placeholder="Enter username" required>
        <input type="email" name="email" placeholder="Enter email or phone number" required>
        <input type="password" name="password" placeholder="Set your password" required>
        <button type="submit" name="signup">Register</button>
      </form>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
  </div>

  <?php include 'cursor.php'; ?>
</body>
</html>