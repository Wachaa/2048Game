<?php
// 1) Include the shared config (session, $conn, auth helpers)
require_once __DIR__ . '/../config.php';


// 2) Define the auth helpers in full:
function isLoggedIn() {
  // Returns true if we have a user_id in session
  return isset($_SESSION['user_id']);
}

function requireAuth() {
  // If not logged in, send back to the login page
  if (!isLoggedIn()) {
      header('Location: ../Login.php');
      exit;
  }
}

// 3) Enforce authentication on this page
requireAuth();

// 3) Grab session data
$userId   = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];

$sql = "SELECT username, top_time, profile_picture, high_score FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$rawPfp = $user['profile_picture'] ?? '';
if ($rawPfp && file_exists($rawPfp)) {
    // If $rawPfp is e.g. "/var/www/html/uploads/xyz.png",
    // strip off DOCUMENT_ROOT so the browser sees "/uploads/xyz.png"
    $profileUrl = substr($rawPfp, strlen($_SERVER['DOCUMENT_ROOT']));
} else {
    // Fallback to default avatar
    $profileUrl = '/uploads/user.png';
}
$user['profile_picture'] = $profileUrl;

$user['high_score'] = (string)($user['high_score'] ?? '0');
$user['top_time'] = (string)($user['top_time'] ?? '0');
$user['profile_picture'] = (string)($user['profile_picture'] ?? '../../assets/user.svg');


$countQuery = "SELECT COUNT(*) as total_games FROM score_history WHERE user_id = ?";
$countStmt = $conn->prepare($countQuery);

if(!$countStmt){
die("Prepare failed:".$conn->error);
}
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow= $countResult->fetch_assoc();


$allowedSorts = ['high-to-low' => 'DESC', 'low-to-high' => 'ASC'];
$sortParam = $_GET['sort'] ?? 'high-to-low';  // Default to high-to-low
$sortOrder = $allowedSorts[$sortParam] ?? 'DESC';

$sql = "SELECT id, user_id, score, time_taken, played_at 
        FROM score_history 
        WHERE user_id = ? 
        ORDER BY score $sortOrder";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2048 Game</title>

    <!-- Import Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style.css" />
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              poppins: ["Poppins", "sans-serif"],
            },
          },
        },
      };
    </script>
  </head>
<body>
    
<div class=" flex flex-col w-full h-full  justify-center items-center mt-4">
<div class="buttons flex gap-4">
    <a class="btn border-2 border-black bg-white text-black px-4 py-1 rounded-md" href="./profile.php">My Profile</a>
     <a class="btn border-1 border-black bg-black text-white px-4 py-1 rounded-md" href="./index.php">Game board</a>
    <a class="btn border-1 border-black bg-black text-white px-4 py-1 rounded-md" href="./leaderboard.php">Leaderboard</a>
  
          <a
    href="../logout.php"
    class=" btn border-1 border-black bg-black text-white px-4 py-1 rounded-md"
  >
    Log Out
  </a>

</div>
<div class="panel flex flex-col gap-4 mt-7 w-full justify-center items-center">
  
  <!-- Profile Container -->
  <div class="profile-container flex w-[500px] h-24 bg-slate-200 text-black p-4 rounded-lg shadow-md">
    
    <!-- Left Block: Avatar -->
    <div class="leftblock flex items-center mr-4">
  <div class="image bg-white border-2 border-black w-16 h-16 rounded-md overflow-hidden">
 <img src="<?= htmlspecialchars($user['profile_picture']) ?>"
     alt="Avatar" class="w-full h-full object-cover">

  </div>
</div>

    <!-- Right Block: User Info -->
    <div class="rightblock flex flex-col justify-between w-full">
      
      <!-- Top Row: Username & Edit Button -->
      <div class="top flex justify-between items-center mb-1">
        <div class="username font-bold text-xl "><?php echo htmlspecialchars($user['username']);?></div>
        <a class=" border-2 border-black text-white bg-black px-3 py-1 rounded hover:bg-slate-800 hover:text-white  transition " href="./edit.php">Edit Profile</a>
      </div>

      <!-- Bottom Row: Stats -->
      <div class="bottom flex justify-between items-center text-sm">
        <div class="gameplayed">Games Played: <strong><?php echo htmlspecialchars($countRow['total_games']);?></strong></div> <div class="line w-px h-5 bg-black mx-1 "></div>

        <div class="highscore flex items-center gap-2">
          <span>High Score:</span>
          <strong><?php echo htmlspecialchars($user['high_score']);?></strong>
          <span>At: <?php echo htmlspecialchars($user['top_time']);?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Score Box -->
<div class="score w-[500px] h-[400px] bg-slate-200 text-black p-4 rounded-lg shadow-md flex flex-col gap-2 overflow-y-auto">
  
  <!-- Sorting Dropdown -->
 <!-- Sorting Dropdown -->
<form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="flex justify-end mb-2">
  <select name="sort" class="border border-gray-400 rounded px-2 py-1 text-sm" onchange="this.form.submit()">
    <option value="high-to-low" <?php if (($sortParam ?? '') === 'high-to-low') echo 'selected'; ?>>High to Low</option>
    <option value="low-to-high" <?php if (($sortParam ?? '') === 'low-to-high') echo 'selected'; ?>>Low to High</option>
  </select>
</form>

  <!-- Scrollable Table Container -->
  <div class="overflow-y-auto max-h-[200px]">
    <table class="w-full text-sm font-semibold text-left text-black">
      <thead class="sticky top-0 bg-slate-200">
        <tr class="border-t border-transparent">
          <th class="w-1/5 py-2">SN</th>
          <th class="w-1/5 py-2">Score</th>
          <th class="w-1/5 py-2">Time Taken</th>
          <th class="w-2/5 py-2">Played At</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sn = 1;
        while ($score = $result->fetch_assoc()):
          $datetime = new DateTime($score['played_at']);
          $date = $datetime->format('Y-m-d');
          $time = $datetime->format('h:i A');
        ?>
        <tr>
          <td class="py-1"><?php echo $sn++; ?></td>
          <td class="py-1"><?php echo htmlspecialchars($score['score']); ?></td>
          <td class="py-1"><?php echo htmlspecialchars($score['time_taken']); ?></td>
          <td class="py-1"><?php echo "$date at $time"; ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

 <!-- Minimal Footer -->
      <div class=" text-sm text-gray-600 ">© 2025 | 2048 Bloom</div>
</div>
</div>
</body>
</html>