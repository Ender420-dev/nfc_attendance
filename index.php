<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Salon System</title>
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <img src="images/salon.jpg" alt="Salon Logo" class="logo">
      <h1 class="title">Welcome Back</h1>
      <p class="subtitle">Login to your salon system</p>

      <!-- Login form -->
      <form class="login-form" action="login.php" method="POST">
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your Username" required>
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn-login">Login</button>

        <div class="extra-links">
          <a href="forgot-password.html">Forgot Password?</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
