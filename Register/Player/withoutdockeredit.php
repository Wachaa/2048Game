<?php
// edit.php

// 1) Include shared config (session start, $conn, etc.)
require_once __DIR__ . '/../config.php';

// 2) Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];

// 3) Handle avatar upload if present
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $origName = basename($_FILES['profile_picture']['name']);
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','gif','svg'];

    if (in_array($ext, $allowed)) {
        $newName    = time() . '_' . preg_replace('/[^a-z0-9\._-]/i','', $origName);
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            // Store the correct web-accessible path:
            $dbPath = '/2048/uploads/' . $newName;
            $stmt   = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $dbPath, $userId);
            $stmt->execute();
        }
    }

    // Refresh to avoid re‑POSTing
    header("Location: edit.php");
    exit;
}


// 4) Handle username update (separate form)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['username'])
    && !isset($_POST['current_password'])
) {
    $newUsername = trim($_POST['username']);
    if ($newUsername !== '') {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $newUsername, $userId);
        $stmt->execute();
       $_SESSION['username'] = $newUsername;
$_SESSION['success_message'] = "Username updated successfully.";

    }
    header("Location: edit.php");
    exit;
}

// 5) Handle password change (separate form)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])
) {
    $currentPassword = trim($_POST['current_password']);
    $newPassword     = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Fetch existing hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();
    $hash = $row['password'];

    if (!password_verify($currentPassword, $hash)) {
        $passwordError = "Current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "New passwords do not match.";
   } else {
    $pwdPattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!preg_match($pwdPattern, $newPassword)) {
        $passwordError = "Password must be at least 8 characters, include an uppercase letter, a number, and a special character.";
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt    = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $userId);
        $stmt->execute();
        $_SESSION['success_message'] = "Password updated successfully.";
        header("Location: edit.php");
        exit;
    }
}

}

// 6) Fetch profile_picture for display
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row        = $stmt->get_result()->fetch_assoc();
$raw        = $row['profile_picture'] ?? '';
$profileUrl = $raw
    ? (strpos($raw, '/2048/') === 0 ? $raw : '/2048' . $raw)
    : '/2048/assets/user.svg';

    // Default values (fallback)
$enteredUsername = htmlspecialchars($username);
$enteredCurrentPassword = '';
$enteredNewPassword = '';
$enteredConfirmPassword = '';

// If submitted, preserve username field
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'])) {
        $enteredUsername = htmlspecialchars($_POST['username']);
    }

    if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $enteredCurrentPassword = htmlspecialchars($_POST['current_password']);
        $enteredNewPassword = htmlspecialchars($_POST['new_password']);
        $enteredConfirmPassword = htmlspecialchars($_POST['confirm_password']);
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profile</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Tailwind Font Config -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            poppins: ["Poppins", "sans-serif"],
          },
        },
      },
    }
  </script>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>

<body >

<div class="flex flex-col items-center justify-center mt-4  w-full">
  <div class="profile-container flex flex-col w-[400px] h-full bg-slate-200 text-black p-6 rounded-lg shadow-md">
    
    <!-- Top Navigation -->
    <div class="flex items-center justify-between ">
      <a href="./profile.php" class="bg-transparent text-black border-2 border-black rounded-full w-8 h-8 flex items-center justify-center">
        <img src="../../assets/leftarrow.svg" alt="Back" />
      </a>
      <h1 class="text-xl font-semibold">Edit Profile</h1>
      <div class="w-8 h-8"></div> <!-- Placeholder to balance flex -->
    </div>

    <!-- Avatar -->
     <form id="avatarForm" method="POST" enctype="multipart/form-data" class="flex justify-center mb-6">
  <div class="relative w-20 h-20 border-2 border-black rounded-md overflow-hidden ">
   

    <label for="profile_picture" class="cursor-pointer">
      <img src="<?= htmlspecialchars($profileUrl) ?>" alt="Avatar"
 class="w-full h-full object-cover">
      <img src="../../assets/editBtn.svg" alt="Edit Icon" class="absolute top-1 bg-white rounded-md right-1 w-5 h-5 p-0.5 shadow opacity-50">
  </label>

     <input 
            type="file" 
            name="profile_picture" 
            id="profile_picture" 
            accept="image/*" 
            class="hidden" 
            onchange="document.getElementById('avatarForm').submit()"
          />
  </div>
  </form>



    <!-- Form -->
      <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
    <form action="" method="POST" class="flex gap-2">
      
       
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" class="border border-gray-300 rounded px-3 py-2 mt-1 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        <button class="bg-black text-white rounded px-3 py-2 mt-1 w-full">save username</button>
      
      
</form>
 <hr class="border-gray-400 my-2" />
 <?php if (!empty($_SESSION['success_message'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
      <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>
 
<form action="" method="POST" class="flex flex-col gap-4">
     

      <h3 class="text-sm font-semibold">Change Password</h3>
     <div>
  <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
  <div class="relative">
    <input 
      type="password" 
      id="current_password" 
      name="current_password" 
      class="border border-gray-300 rounded px-3 py-2 mt-1 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
      value="<?= $enteredCurrentPassword ?>"
      required
    >
    <button type="button" class="togglePassword absolute right-2 top-1/2 transform -translate-y-1/2 text-sm text-blue-600 hover:text-blue-800" data-target="current_password">
      Show
    </button>
  </div>
</div>

<div>
  <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
  <div class="relative">
    <input type="password" id="new_password" name="new_password"  value="<?= $enteredNewPassword ?>" class="border border-gray-300 rounded px-3 py-2 mt-1 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    <button type="button" class="togglePassword absolute right-2 top-1/2 transform -translate-y-1/2 text-sm text-blue-600 hover:text-blue-800" data-target="new_password">
      Show
    </button>
  </div>
</div>
<!-- Password Rules List -->
<ul class="text-sm mt-2">
  <li id="password-minlength" class="text-red-500">Min 8 characters</li>
  <li id="password-uppercase" class="text-red-500">At least 1 uppercase letter</li>
  <li id="password-special" class="text-red-500">At least 1 special character</li>
  <li id="password-number" class="text-red-500">At least 1 number</li>
</ul>
<p class="text-red-500 text-sm mt-1" id="confirm-password-error"></p>


<div>
  <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
  <div class="relative">
    <input type="password" id="confirm_password" name="confirm_password" value="<?= $enteredConfirmPassword ?>" class="border border-gray-300 rounded px-3 py-2 mt-1 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    <button type="button" class="togglePassword absolute right-2 top-1/2 transform -translate-y-1/2 text-sm text-blue-600 hover:text-blue-800" data-target="confirm_password">
      Show
    </button>
  </div>
</div>


<?php if (!empty($passwordError)): ?>
  <div class="text-red-600 text-sm"><?= htmlspecialchars($passwordError) ?></div>
<?php endif; ?>
<?php if (!empty($passwordSuccess)): ?>
  <div class="text-green-600 text-sm"><?= htmlspecialchars($passwordSuccess) ?></div>
<?php endif; ?>
      <button type="submit" class="mt-4 bg-black text-white py-2 rounded hover:bg-gray-800 transition duration-200">
        Save Changes
      </button>
    </form>
  </div>
  <!-- Minimal Footer -->
      <div class="mt-4 text-sm text-gray-600 py-3">© 2025 | 2048 Bloom</div>
</div>


<script>
 document.querySelectorAll('.togglePassword').forEach(button => {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    const targetId = this.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (input.type === 'password') {
      input.type = 'text';
      this.textContent = 'Hide';
    } else {
      input.type = 'password';
      this.textContent = 'Show';
    }
  });
});



  const newPasswordInput = document.getElementById('new_password');
  const confirmNewPasswordInput = document.getElementById('confirm_password');

  const minLength = document.getElementById('password-minlength');
  const uppercase = document.getElementById('password-uppercase');
  const specialChar = document.getElementById('password-special');
  const number = document.getElementById('password-number');
  const confirmPasswordError = document.getElementById('confirm-password-error');

  function validatePassword() {
    const pwd = newPasswordInput.value;

    // Min length
    pwd.length >= 8
      ? (minLength.classList.replace('text-red-500', 'text-green-500'))
      : (minLength.classList.replace('text-green-500', 'text-red-500'));

    // Uppercase letter
    /[A-Z]/.test(pwd)
      ? (uppercase.classList.replace('text-red-500', 'text-green-500'))
      : (uppercase.classList.replace('text-green-500', 'text-red-500'));

    // Special character
    /[!@#$%^&*(),.?":{}|<>]/.test(pwd)
      ? (specialChar.classList.replace('text-red-500', 'text-green-500'))
      : (specialChar.classList.replace('text-green-500', 'text-red-500'));

    // Number
    /\d/.test(pwd)
      ? (number.classList.replace('text-red-500', 'text-green-500'))
      : (number.classList.replace('text-green-500', 'text-red-500'));

    // Confirm match
    if (pwd !== confirmNewPasswordInput.value) {
      confirmPasswordError.textContent = 'Passwords do not match.';
    } else {
      confirmPasswordError.textContent = '';
    }
  }

  newPasswordInput.addEventListener('input', validatePassword);
  confirmNewPasswordInput.addEventListener('input', validatePassword);



</script>
  
</body>
</html>
