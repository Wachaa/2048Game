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

// 4) Fetch high_score from users table
$highScore = 0;
if ($stmt = $conn->prepare('SELECT high_score FROM users WHERE id = ?')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($hs);
    if ($stmt->fetch()) {
        $highScore = (int)$hs;
    }
    $stmt->close();

}
?><!DOCTYPE html>
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
  <!-- Countdown Overlay -->
<div
  id="countdown-overlay"
  class="fixed inset-0 bg-black bg-opacity-80 text-white text-6xl flex items-center justify-center z-50 hidden"
>
  <div id="countdown-number">3</div>
</div>

  <body
    class="bg-[url('../../assets/bg3.jpg')] bg-cover bg-center h-full w-full font-poppins bg-neutral-200 flex flex-col"
  >
    <div class="containerScore flex justify-center mt-10 gap-10">
      <div class="leftSide max-w-[180px] flex flex-col gap-1">
        <h1 class="font-black text-[32px]">2048*</h1>
        <p class="text-[12px]">Merge to win and achieve the 2048 target</p>
        <a
          href="#"
          id="showPopup"
          onclick="toogleShow"
          class="how text-[12px] font-semibold"
          >How to play</a
        >
      </div>
      <!-- popup -->
      <div
        id="popup"
        class="popup fixed bg-white inset-0 bg-opacity-80 z-60 flex justify-center items-center hidden"
      >
        <div
          class="howto flex flex-col justify-center p-4 border-2 border-black w-[400px] rounded-md bg-white"
        >
          <h2 class="text-[18px] font-black">How to Play 2048:</h2>
          <p class="mt-4">
            Move tiles with arrow keys. Two tiles of the same number that touch
            each other will merge into one, doubling its value. Keep merging
            tiles to reach 2048. The game is over when there are no moves left.
          </p>
          <button
            class="mt-4 p-2 bg-black text-white rounded-md"
            id="okay"
            onclick="toogleClose"
          >
            Ok
          </button>
        </div>
      </div>
      
      <div class="rightSide flex flex-col place-items-end gap-7">
        <div class="scores flex gap-2">
          <div
            class="flex flex-col items-center border-[2px] rounded-md border-black px-5 py-1 bg-white"
          >
            <h2 class="font-medium text-[12px]">Score</h2>
            <p id="score" class="font-bold text-[12px]">0</p>
          </div>

          <div
            class="flex flex-col items-center border-[2px] rounded-md border-black px-4 py-1 bg-white"
          >
            <h2 class="font-medium text-[12px]">High Score</h2>
            <p id="high-score" class="font-bold text-[12px]"><?php echo $highScore ?></p>

          </div>
          <div
    class="flex flex-col items-center border-[2px] rounded-md border-black w-[70px] px-5 py-1 bg-white"
  >
    <h2 class="font-medium text-[12px]">Time</h2>
    <p id="timer" class="font-bold text-[12px]">00:00</p>
  </div>
</div>
<div class="start flex gap-1">
          <a
            href="./profile.php"
            class="flex items-center px-2 py-1 border rounded-md bg-black text-white text-[12px] font-bold"
          >
            <img
              src="../../assets/user.svg"
              alt="profile"
              class="w-5 filter invert"
            />
          </a>

          <a
            class="flex items-center px-2 py-1 border rounded-md bg-black text-white text-[12px] font-bold"
            href="./leaderboard.php"
          >
            <img
              src="../../assets/leaderboard.svg"
              alt="Leaderboard"
              class="w-5 h-4"
            />
          </a>

          <button
            id="play-btn"
            onclick="startNewGame()"
            class="start px-4 py-2 border rounded-md bg-black text-white text-[12px] font-bold"
          >
            Start Game
          </button>
        
          
          <!-- Initially hide the Pause button -->
          <button
  id="pause-resume"
  class="start px-4 py-2 border rounded-md bg-black text-white text-[12px] font-bold hidden"
>
  Pause
</button>



          <a
    href="../logout.php"
    class="start px-4 py-2 border rounded-md bg-black text-white text-[12px] font-bold"
  >
    Log Out
  </a>
        </div>
      </div>
    </div>
        </div>
        

    <!-- Centering Container -->
    <div class="flex flex-col items-center mt-8">
      <!-- 2048 Board Container -->
      <div
        id="board"
        class="mb-4 flex flex-wrap gap-2 content-start w-[356px] h-[360px] p-[8px] bg-neutral-200 border-2 border-black rounded-md shadow-2xl"
      ></div>
      <!-- add this directly after your board div -->
      <div
        id="game-over"
        class="hidden fixed inset-0 bg-black bg-opacity-75 flex flex-col items-center justify-center z-50"
      >
        <h2 class="text-white text-3xl mb-4">Game Over!</h2>
        <p class="text-white mb-6">
          Your score: <span id="final-score">0</span>
        </p>
      
      </div>

      <!-- Minimal Footer -->
      <div class="mb-4 text-sm text-gray-600">© 2025 | 2048 Bloom</div>
    </div>

    <script src="script.js"></script>
    <script src="time.js"></script>
    <script src="scoreManager.js"></script>
    <script>
      const popup = document.getElementById("popup");
      const showLink = document.getElementById("showPopup");
      const closeBtn = document.getElementById("okay");

      function toggleShow() {
        popup.classList.remove("hidden");
      }
      function toogleClose() {
        popup.classList.add("hidden");
      }

      showLink.addEventListener("click", function (e) {
        e.preventDefault();
        toggleShow();
      });

      closeBtn.addEventListener("click", toogleClose);
    </script>
  </body>
</html>
