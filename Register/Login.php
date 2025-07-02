<?php
session_start();
include('config.php');

$error = "";
$email_input = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $email_input = htmlspecialchars($email); // For form persistence

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Retrieve user
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ./Player/index.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded-md shadow-lg w-[360px]">
    <h2 class="text-2xl font-bold mb-4">Login</h2>

    <?php if (!empty($error)): ?>
      <p class="text-red-500 text-sm mb-2"><?= $error; ?></p>
    <?php endif; ?>

    <form action="login.php" method="post" class="flex flex-col gap-3">
      <div>
        <label>Email Address</label>
        <input name="email" type="email" placeholder="Enter your email"
               value="<?= $email_input ?? '' ?>"
               required class="w-full border border-gray-300 p-2 rounded-md"/>
      </div>
      <div>
        <label>Password</label>
        <input name="password" type="password" placeholder="Enter your password"
               required class="w-full border border-gray-300 p-2 rounded-md"/>
      </div>

      <div class="text-right">
        <a href="forgot_password.php" class="text-sm text-blue-600">Forgot password?</a>
      </div>

      <div class="flex gap-1">
        <button type="submit" class="w-full bg-black text-white p-2 rounded-md">Login</button>
        <a href="../index.html" class="w-full bg-gray-500 text-white p-2 rounded-md text-center">Cancel</a>
      </div>

      <div class="text-center text-sm mt-2">
        Don't have an account? <a href="register.php" class="text-blue-600 font-medium">Sign Up</a>
      </div>
    </form>
  </div>
</body>
</html>
