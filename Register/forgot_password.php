<?php
session_start();
include('config.php');

// Include PHPMailer files
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Generate reset token & expiry
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Update database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expiry, $user['id']);
        $stmt->execute();

        //
        // ──────── U P D A T E D   L I N E ──────────
        //
        // We hardcode port 8080 so that the link goes into your Dockerized PHP container.
        // NEW (point to /Register/... on port 8080):
$resetLink = "http://localhost:8080/Register/reset_password.php?token=" . $token;

        //
        // ───────────────────────────────────────────
        //

        // Debug: Show link on screen (uncomment if you need to test without email)
        // echo "<p><strong>Reset link (for testing):</strong> <a href='$resetLink'>$resetLink</a></p>";

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bishantkayastha01@gmail.com'; // your Gmail
            $mail->Password   = 'xdyb zbhx fnlh dhiu';          // your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('bishantkayastha01@gmail.com', 'Game 2048');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Hi,\n\nTo reset your password, please click the following link:\n$resetLink\n\nIf you did not request this, ignore this email.";

            $mail->send();
            $success = "Password reset link sent to your email.";
        } catch (Exception $e) {
            $error = "Mail Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded-md shadow-lg w-[360px]">
    <h2 class="text-2xl font-bold mb-4">Forgot Password</h2>
    <?php if ($success): ?>
      <p class="text-green-600 text-sm mb-2"><?= $success ?></p>
    <?php elseif ($error): ?>
      <p class="text-red-500 text-sm mb-2"><?= $error ?></p>
    <?php endif; ?>
    <form method="post" class="flex flex-col gap-3">
      <div>
        <label for="email">Email Address</label>
        <input
          name="email"
          type="email"
          id="email"
          placeholder="Enter your email"
          required
          class="w-full border border-gray-300 p-2 rounded-md"
        />
      </div>
      <button type="submit" class="bg-black text-white p-2 rounded-md w-full">
        Send Reset Link
      </button>
      <a href="../index.html" class="w-full bg-gray-500 text-white p-2 rounded-md text-center">
        Cancel
      </a>
    </form>
  </div>
</body>
</html>
