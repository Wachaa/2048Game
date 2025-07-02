<?php
session_start();
// config.php include here...
include 'config.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize errors array
$errors = [];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid CSRF token.';
    }
    
    // Fetch & sanitize inputs
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate username
    if (!$username) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 20 
              || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = '3–20 chars; letters, numbers, underscore only.';
    }
    
   // Validate email
if (!$email) {
  $errors['email'] = 'Email is required.';
} elseif (!preg_match('/^[^@]+@[a-zA-Z0-9\-]+\.(com)$/', $email)) {
  $errors['email'] = 'Email must be a valid .com address with a domain. eg:your@gmail.com';
}

    
    // Validate password
    $pwdPattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!$password) {
        $errors['password'] = 'Password is required.';
    } elseif (!preg_match($pwdPattern, $password)) {
        $errors['password'] = 'Required*';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
 // Check email and username uniqueness
if (!$errors) {
    // Check email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors['email'] = 'Email already registered.';
    }
    $stmt->close();

    // Check username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors['username'] = 'Username already exists. Choose another.';
    }
    $stmt->close();
}
// Validate "agree_terms" checkbox
if (empty($_POST['agree_terms'])) {
    $errors['agree_terms'] = 'You must agree to the terms and conditions.';
}

    
    // Insert user if no errors
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
          "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $email, $hash);
        if ($stmt->execute()) {
            header('Location: login.php');
            exit;
        } else {
            $errors['general'] = 'Registration failed. Try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <form action="" method="post" novalidate class="bg-white p-6 rounded-md shadow-lg w-80">
    <h2 class="text-2xl font-bold mb-4">Sign Up</h2>
    
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
    
    <?php if (!empty($errors['general'])): ?>
      <p class="text-red-500 mb-2"><?= $errors['general']; ?></p>
    <?php endif; ?>
    
    <!-- Username -->
    <label class="block mb-1">Username</label>
    <input name="username" value="<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>"
           class="w-full border p-2 rounded mb-1"  placeholder="your username">
    <p class="text-red-500 text-sm mb-2"><?= $errors['username'] ?? '' ?></p>
    
    <!-- Email -->
    <label class="block mb-1">Email</label>
<input name="email" type="email" required
       pattern="^[^@]+@[a-zA-Z0-9\-]+\.com$"
       value="<?= htmlspecialchars($email ?? '') ?>"
       class="w-full border p-2 rounded mb-1"
       placeholder="you@example.com">
<p class="text-red-500 text-sm mb-2"><?= $errors['email'] ?? '' ?></p>

    
    <!-- Password -->
    <label class="block mb-1">Password</label>
    <input name="password" type="password" required minlength="8"
           pattern="(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
           value="<?= htmlspecialchars($password ?? '') ?>"
           class="w-full border p-2 rounded mb-1"
           placeholder="your password">
    <p class="text-red-500 text-sm mb-2"><?= $errors['password'] ?? '' ?></p>

    <!-- Password Validation -->
    <ul class="text-sm">
      <li id="password-minlength" class="text-red-500">Min 8 characters</li>
      <li id="password-uppercase" class="text-red-500">At least 1 uppercase letter</li>
      <li id="password-special" class="text-red-500">At least 1 special character</li>
      <li id="password-number" class="text-red-500">At least 1 number</li>
    </ul>
    
    <!-- Confirm Password -->
    <label class="block mb-1">Confirm Password</label>
    <input name="confirm_password" type="password" required
           value="<?= htmlspecialchars($confirm_password ?? '') ?>"
           class="w-full border p-2 rounded mb-1"
           placeholder="Re-type your password">
    <p class="text-red-500 text-sm mb-2" id="confirm-password-error"></p>

    <div class="mb-4">
  <label class="inline-flex items-center">
    <input type="checkbox" name="agree_terms" value="1" <?= !empty($_POST['agree_terms']) ? 'checked' : '' ?> class="form-checkbox">
    <span class="ml-2 text-sm">I agree to the <a href="./terms.html" class="text-blue-600 underline">terms and conditions</a></span>
  </label>
  <p class="text-red-500 text-sm mt-1" id="agree-terms-error"><?= $errors['agree_terms'] ?? '' ?></p>
</div>


   <div class=" flex gap-2"> <button type="submit" class="w-full bg-black text-white p-2 rounded">Sign Up</button>
       <a href="../index.html" class="w-full bg-gray-500 text-white p-2 rounded-md text-center">Cancel</a></div>
    <p class="text-sm mt-2">Already have an account ? <a href="../Register/Login.php" class="text-blue-600">login </a></p>
  </form>

  <script>
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
  const emailInput = document.querySelector('input[name="email"]');

  const minLength = document.querySelector('#password-minlength');
  const uppercase = document.querySelector('#password-uppercase');
  const specialChar = document.querySelector('#password-special');
  const number = document.querySelector('#password-number');
  const confirmPasswordError = document.querySelector('#confirm-password-error');

  function validatePassword() {
    const password = passwordInput.value;

    // Check the length
    if (password.length >= 8) {
      minLength.classList.remove('text-red-500');
      minLength.classList.add('text-green-500');
    } else {
      minLength.classList.remove('text-green-500');
      minLength.classList.add('text-red-500');
    }

    // Uppercase check
    if (/[A-Z]/.test(password)) {
      uppercase.classList.remove('text-red-500');
      uppercase.classList.add('text-green-500');
    } else {
      uppercase.classList.remove('text-green-500');
      uppercase.classList.add('text-red-500');
    }

    // Special character check
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      specialChar.classList.remove('text-red-500');
      specialChar.classList.add('text-green-500');
    } else {
      specialChar.classList.remove('text-green-500');
      specialChar.classList.add('text-red-500');
    }

    // Number check
    if (/\d/.test(password)) {
      number.classList.remove('text-red-500');
      number.classList.add('text-green-500');
    } else {
      number.classList.remove('text-green-500');
      number.classList.add('text-red-500');
    }

    // Confirm password match check
    const confirmPassword = confirmPasswordInput.value;
    if (password !== confirmPassword) {
      confirmPasswordError.textContent = 'Passwords do not match.';
      confirmPasswordError.classList.add('text-red-500');
    } else {
      confirmPasswordError.textContent = '';
    }
  }

  // Attach validation on input events
  passwordInput.addEventListener('input', validatePassword);
  confirmPasswordInput.addEventListener('input', validatePassword);
  emailInput.addEventListener('input', validatePassword);

  // Optional: Run once on page load to validate pre-filled values
  validatePassword();

  const form = document.querySelector('form');
const agreeCheckbox = form.querySelector('input[name="agree_terms"]');
const agreeError = document.getElementById('agree-terms-error');

form.addEventListener('submit', function(e) {
  if (!agreeCheckbox.checked) {
    e.preventDefault();
    agreeError.textContent = 'You must agree to the terms and conditions.';
  } else {
    agreeError.textContent = ''; // Clear error if checked
  }
});

</script>
</body>
</html>
