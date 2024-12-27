<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['isLogin']) && $_SESSION['isLogin']) {
    header("Location: dashboard.php");
    exit;
}

// Include database connection
include('connection.php');

$message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if email and password are empty
    if (empty($email) || empty($password)) {
        $message = "Email and password are required!";
    } else {
        // Prepare SQL query to check if user exists
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if a user with the given email exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Start a session and set session variables for login
                $_SESSION['isLogin'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Incorrect password. Please try again.";
            }
        } else {
            $message = "No user found with that email address.";
        }
        
        // Close statement
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Library Management</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body  style="background-image: url('uploads/library.jpg');">
    <div class="container mt-5" style="max-width:400px; background-color: rgba(236, 236, 236, 0.80); height: 85vh; margin-bottom: 30px;padding: 30px;">
      <h2 class="text-center mb-4">Login</h2>
      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" required />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required />
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
      </form>

      <!-- Display message -->
      <div id="message" class="mt-3">
        <?php if (!empty($message)) : ?>
          <div class="alert alert-danger" role="alert">
            <?php echo $message; ?>
          </div>
        <?php endif; ?>
      </div>
      <div>Don't have an account? <a href="register.php">Register</a></div>
    </div>
  </body>
</html>
