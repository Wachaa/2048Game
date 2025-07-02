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

$allowedSorts = ['high-to-low' => 'DESC', 'low-to-high' => 'ASC'];
$sortParam = $_GET['sort'] ?? 'high-to-low';  // Default to high-to-low
$sortOrder = $allowedSorts[$sortParam] ?? 'DESC';
$highlightUser = isset($_GET['highlight_user']) && $_GET['highlight_user'] == '1';

// Query to get the current user's highest score rank
$rankSql = "
  SELECT user_id, RANK() OVER (ORDER BY max_score DESC) as user_rank
  FROM (
      SELECT user_id, MAX(score) as max_score
      FROM score_history
      GROUP BY user_id
  ) ranked_scores
  WHERE user_id = ?
";

$rankStmt = $conn->prepare($rankSql);
$rankStmt->bind_param('i', $userId);
$rankStmt->execute();
$rankResult = $rankStmt->get_result();
$userRankData = $rankResult->fetch_assoc();
$userRank = $userRankData['user_rank'] ?? null;




$sql = "
SELECT u.username, u.profile_picture, s.score as high_score, s.time_taken, s.played_at
FROM score_history s
JOIN users u ON u.id = s.user_id
JOIN (
    SELECT user_id, MAX(score) as max_score
    FROM score_history
    GROUP BY user_id
) grouped_scores ON s.user_id = grouped_scores.user_id AND s.score = grouped_scores.max_score
ORDER BY s.score $sortOrder
LIMIT 50
";

// Get top 3 players (by highest score)
$top3Sql = "
SELECT u.username, u.profile_picture, s.score as high_score, s.time_taken, s.played_at
FROM score_history s
JOIN users u ON u.id = s.user_id
JOIN (
    SELECT user_id, MAX(score) as max_score
    FROM score_history
    GROUP BY user_id
) grouped_scores ON s.user_id = grouped_scores.user_id AND s.score = grouped_scores.max_score
ORDER BY s.score DESC
LIMIT 3
";

$top3Stmt = $conn->prepare($top3Sql);
$top3Stmt->execute();
$top3Result = $top3Stmt->get_result();

$topPlayers = [];
while ($row = $top3Result->fetch_assoc()) {
    $topPlayers[] = $row;
}


$stmt = $conn->prepare($sql);

$stmt->execute();

$result = $stmt->get_result();  // ❗❗❗ Needed before while loop



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
    <a class="btn border-1 border-black bg-black text-white px-4 py-1 rounded-md" href="./profile.php">My Profile</a>
     <a class="btn border-1 border-black bg-black text-white px-4 py-1 rounded-md" href="./index.php">Game board</a>
    <a class="btn border-2 border-black bg-white text-black px-4 py-1 rounded-md" href="./leaderboard.php">Leaderboard</a>
  
          <a
    href="../logout.php"
    class=" btn border-1 border-black bg-black text-white px-4 py-1 rounded-md"
  >
    Log Out
  </a>

</div>
<div class="panel flex flex-col gap-4 mt-7 w-full justify-center items-center">
  
  <!-- Profile Container -->
 <?php
$medals = ['2nd' => 'bg-slate-400 h-[190px]', '1st' => 'bg-amber-500 h-[220px]', '3rd' => 'bg-orange-900 h-[160px]'];
$positions = ['1st', '2nd', '3rd'];
?>

<div class="profile-container flex w-[500px] h-[250px] bg-slate-200 shadow-md text-black p-4 rounded-md gap-4 justify-center items-end">
  <?php foreach ($topPlayers as $index => $player): 
      $pos = $positions[$index];
      $style = $medals[$pos];
      $datetime = new DateTime($player['played_at']);
      $formattedTime = $datetime->format('h:i A');
  ?>
    <div class="<?php echo $pos . ' w-[100px] ' . $style; ?> rounded-md flex flex-col items-center justify-center px-3">
        <img src="<?php echo htmlspecialchars($player['profile_picture']); ?>" alt="user" class="w-12 h-12 bg-white rounded-md object-cover">
        <p class="text-white font-semibold text-center  text-sm">
            <?php echo htmlspecialchars($player['username']); ?><br>
            High score <br>
            <?php echo htmlspecialchars($player['high_score']); ?><br>
            at <?php echo htmlspecialchars($player['time_taken']. "s"); ?>
        </p>
    </div>
  <?php endforeach; ?>
</div>

  <!-- Score Box (You can style it however you like) -->
  <div class="score w-[500px] h-full bg-slate-200 text-black p-4 rounded-lg shadow-md flex flex-col gap-2">
  
  <!-- Sorting Dropdown -->
  <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="flex justify-end mb-2 gap-3">
    
 <button type="submit" name="highlight_user" value="1" class="btn border-1 border-black bg-black text-white px-4 py-1 rounded-md">
    My Rank
  </button>

  <select name="sort" class="border border-gray-400 rounded px-2 py-1 text-sm" onchange="this.form.submit()">
    <option value="high-to-low" <?php if (($sortParam ?? '') === 'high-to-low') echo 'selected'; ?>>High to Low</option>
    <option value="low-to-high" <?php if (($sortParam ?? '') === 'low-to-high') echo 'selected'; ?>>Low to High</option>
  </select>
</form>
  <?php if ($highlightUser && $userRank === null): ?>
        <div class="text-amber-500 px-2 rounded-md bg-white font-semibold  mx-24">Play a game to view your rank here.</div>
      <?php endif; ?>

  <!-- Score Table Header -->
  <table class="w-full text-sm font-semibold text-left text-black">
  <thead>
    <tr class="border-t border-transparent">
      <th class="w-1/5 py-2">SN</th>
      <th class="w-1/5 py-2">Profile</th>
      <th class="w-1/5 py-2">Username</th>
      <th class="w-1/5 py-2">High Score</th>
      <th class="w-2/5 py-2">Time Taken</th>
    </tr>
  </thead>
  <tbody>
      <?php
$sn = 1;
while ($row = $result->fetch_assoc()):
    $datetime = new DateTime($row['played_at']);
    $date = $datetime->format('Y-m-d');
    $time = $datetime->format('h:i A');

    // Check if this row is the logged-in user and highlight if needed
    $highlightClass = '';
    if ($highlightUser && $row['username'] === $username) {
        $highlightClass = 'bg-yellow-200 font-bold';  // example highlight styles
    }
?>
<tr class="<?php echo $highlightClass; ?>">
  <td class="py-1"><?php echo $sn++; ?></td>
  <td class="py-1">
<img src="<?php echo htmlspecialchars($row['profile_picture'] ?: '../../assets/user.svg'); ?>" class="w-8 h-8 rounded-md border-2 border-black object-cover" />

  </td>
  <td class="py-1"><?php echo htmlspecialchars($row['username']); ?></td>
  <td class="py-1"><?php echo htmlspecialchars($row['high_score']); ?></td>
  <td class="py-1"><?php echo htmlspecialchars($row['time_taken']) . "s"; ?> </td>
</tr>
<?php endwhile; ?>


      </tbody>
</table>
</div>
</div>
 <!-- Minimal Footer -->
      <div class=" mt-2 text-sm text-gray-600 ">© 2025 | 2048 Bloom</div>
</div>
</div>
</body>
</html>