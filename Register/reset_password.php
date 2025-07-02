<?php
session_start();
include('config.php');

$error = "";
$token = $_GET['token'] ?? $_POST['token'] ?? null;

if ($token) {
    $stmt = $conn->prepare("SELECT id, token_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (strtotime($user['token_expiry']) < time()) {
            $error = "This reset link has expired.";
        } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
            $password = trim($_POST['password']);
            $confirm = trim($_POST['confirm_password']);

            // Password pattern
            $pwdPattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
            if (!preg_match($pwdPattern, $password)) {
                $error = "Password must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 special character.";
            } elseif ($password !== $confirm) {
                $error = "Passwords do not match.";
            } else {
                // Hash and update
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user['id']);
                if ($stmt->execute()) {
                    header("Location: login.php?reset=success");
                    exit;
                } else {
                    $error = "Something went wrong. Try again.";
                }
            }
        }
    } else {
        $error = "Invalid or expired reset token.";
    }
} else {
    $error = "No reset token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded shadow-lg w-[360px]">
    <h2 class="text-2xl font-bold mb-4">Reset Password</h2>

    <?php if ($error): ?>
      <p class="text-red-500 text-sm mb-3"><?= $error ?></p>
    <?php else: ?>
    <form method="post" class="flex flex-col gap-3" novalidate>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <!-- New Password -->
      <div>
        <label class="block mb-1">New Password</label>
        <input type="password" name="password" id="password" required
               pattern="(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
               class="w-full border p-2 rounded"
               placeholder="New password">
      </div>
         <!-- Password requirements -->
         <ul class="text-sm mb-3">
        <li id="length" class="text-red-500">At least 8 characters</li>
        <li id="uppercase" class="text-red-500">At least 1 uppercase letter</li>
        <li id="special" class="text-red-500">At least 1 special character</li>
        <li id="number" class="text-red-500">At least 1 number</li>
      </ul>

      <!-- Confirm Password -->
      <div>
        <label class="block mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required
               class="w-full border p-2 rounded"
               placeholder="Re-type new password">
        <p class="text-red-500 text-sm" id="confirm-password-error"></p>
      </div>

   

      <button type="submit" class="bg-black text-white p-2 rounded w-full">Reset Password</button>
    </form>
    <?php endif; ?>
  </div>

<script>
const pwdInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');
const confirmError = document.getElementById('confirm-password-error');

const length = document.getElementById('length');
const uppercase = document.getElementById('uppercase');
const special = document.getElementById('special');
const number = document.getElementById('number');

function validatePassword() {
  const value = pwdInput.value;

  // Length
  length.classList.toggle('text-green-500', value.length >= 8);
  length.classList.toggle('text-red-500', value.length < 8);

  // Uppercase
  uppercase.classList.toggle('text-green-500', /[A-Z]/.test(value));
  uppercase.classList.toggle('text-red-500', !/[A-Z]/.test(value));

  // Special
  special.classList.toggle('text-green-500', /[\W_]/.test(value));
  special.classList.toggle('text-red-500', !/[\W_]/.test(value));

  // Number
  number.classList.toggle('text-green-500', /\d/.test(value));
  number.classList.toggle('text-red-500', !/\d/.test(value));

  // Match
  if (pwdInput.value !== confirmInput.value) {
    confirmError.textContent = "Passwords do not match.";
  } else {
    confirmError.textContent = "";
  }
}

pwdInput.addEventListener('input', validatePassword);
confirmInput.addEventListener('input', validatePassword);
</script>
</body>
</html>
