<?php
session_start();

// Include the database connection
include('connection.php');

// Check if the user is already logged in
if (isset($_SESSION['isLogin']) && $_SESSION['isLogin']) {
  header("Location: dashboard.php");
  exit;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Validation (Check if any field is empty)
  if (empty($username) || empty($email) || empty($password)) {
    // Redirect with an error message
    header("Location: register.php?message=All fields are required.");
    exit;
  }

  // Check if email already exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    // Email already exists
    header("Location: register.php?message=Email is already in use.");
    exit;
  }

  $stmt->close();

  // Hash the password before saving it
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Insert user into the database using a prepared statement
  $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $email, $hashed_password);

  if ($stmt->execute()) {
    // Start a session and set session variables for login
    

    // Redirect to the dashboard
    header("Location: dashboard.php");
  } else {
    // If there was an error, redirect with the error message
    header("Location: register.php?message=Error: " . $stmt->error);
  }

  // Close the statement and connection
  $stmt->close();
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Library Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-image: url('uploads/library.jpg');">
  <div class="container mt-5" style="max-width:400px ; background-color: rgba(236, 236, 236, 0.80); height: 85vh; margin-bottom: 30px;padding: 30px;">
    <h2 class="text-center mb-4">Register</h2>
    <!-- Form submission is handled by PHP -->
    <form action="register.php" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required />
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required />
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required />
      </div>
      <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <!-- Message container for displaying success or error messages -->
    <div id="message" class="mt-3">
      <?php
      if (isset($_GET['message'])) {
        echo '<div class="alert alert-info" role="alert">' . htmlspecialchars($_GET['message']) . '</div>';
      }
      ?>
    </div>
    <div>Already have an account? <a href="login.php">Login</a></div>
  </div>
</body>

</html>