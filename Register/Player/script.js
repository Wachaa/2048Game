var board;
var score = 0;
var rows = 4;
var columns = 4;
var gameStarted = false;
let timerInterval;
let secondsElapsed = 0;

// 🔢 LCG Parameters
let seed = Date.now();
const A = 1664525;
const C = 1013904223;
const M = Math.pow(2, 32);

// 🎲 LCG Function
function lcg() {
  seed = (A * seed + C) % M;
  return seed / M;
}

window.onload = function () {
  setGame();
  // Check if we need to start a new game after reload
  if (localStorage.getItem("startNewGame") === "true") {
    startNewGame(); // Start the new game after reload
    localStorage.removeItem("startNewGame"); // Remove the flag to avoid starting the game again on future reloads
  }

  // Wire up Start/New Game button
  document.getElementById("play-btn").addEventListener("click", () => {
    if (!gameStarted) {
      startNewGame();
    } else {
      resetGame();
    }
  });
};

// 🎮 Initializes the board array & UI tiles
function setGame() {
  board = [
    [0, 0, 0, 0],
    [0, 0, 0, 0],
    [0, 0, 0, 0],
    [0, 0, 0, 0],
  ];
  const container = document.getElementById("board");
  container.innerHTML = "";
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < columns; c++) {
      let tile = document.createElement("div");
      tile.id = `${r}-${c}`;
      updateTile(tile, 0);
      container.append(tile);
    }
  }
}

// 🚦 Start a fresh game: two initial tiles, reset score
function startNewGame() {
  gameStarted = false;
  const countdownOverlay = document.getElementById("countdown-overlay");
  const countdownNumber = document.getElementById("countdown-number");

  let countdown = 3;
  countdownNumber.innerText = countdown;
  countdownOverlay.classList.remove("hidden");

  const countdownInterval = setInterval(() => {
    countdown--;
    if (countdown > 0) {
      countdownNumber.innerText = countdown;
    } else {
      clearInterval(countdownInterval);
      countdownOverlay.classList.add("hidden");

      gameStarted = true;
      document.getElementById("play-btn").innerText = "Restart Game";
      score = 0;
      document.getElementById("score").innerText = score;
      setGame();
      setTwo();
      setTwo();
      startTimer(true);
      onGameStarted();
    }
  }, 1000);
}

// 🧪 Checks if there are any zeros left
function hasEmptyTile() {
  return board.some((row) => row.includes(0));
}

// ➕ Spawn a 2 or 4 in a random empty spot
function setTwo() {
  if (!hasEmptyTile()) return;
  let empties = [];
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < columns; c++) {
      if (board[r][c] === 0) empties.push({ r, c });
    }
  }
  let { r, c } = empties[Math.floor(lcg() * empties.length)];
  let value = lcg() < 0.9 ? 2 : 4;
  board[r][c] = value;
  let tile = document.getElementById(`${r}-${c}`);
  updateTile(tile, value);
}

// 🎨 Updates a single tile’s number and CSS class
function updateTile(tile, num) {
  tile.innerText = num === 0 ? "" : num;
  tile.className = "tile";
  if (num > 0) {
    tile.classList.add("x" + (num <= 4096 ? num : 8192));
  }
}

// 🎮 Handle arrow‑key moves + spawn + view update + gameover check
document.addEventListener("keyup", (e) => {
  if (!gameStarted || gamePaused) return;

  let moved = false;
  switch (e.code) {
    case "ArrowLeft":
      moved = slideLeft();
      setTwo();
      updateBoardView();
      break;
    case "ArrowRight":
      moved = slideRight();
      setTwo();
      updateBoardView();
      break;
    case "ArrowUp":
      moved = slideUp();
      setTwo();
      updateBoardView();
      break;
    case "ArrowDown":
      moved = slideDown();
      setTwo();
      updateBoardView();
      break;
  }
  if (!moved) return;

  document.getElementById("score").innerText = score;

  if (isGameOver()) {
    showGameOver();
  }
});

// 🧹 Helper: remove zeroes
function filterZero(row) {
  return row.filter((num) => num !== 0);
}

// 🧠 Slide+merge logic
function slide(row) {
  row = filterZero(row);
  for (let i = 0; i < row.length - 1; i++) {
    if (row[i] === row[i + 1]) {
      row[i] *= 2;
      row[i + 1] = 0;
      score += row[i];
    }
  }
  row = filterZero(row);
  while (row.length < columns) row.push(0);
  return row;
}

// Returns true if any tile moved
function slideLeft() {
  let moved = false;
  for (let r = 0; r < rows; r++) {
    let before = board[r].slice();
    board[r] = slide(board[r]);
    if (!arraysEqual(before, board[r])) moved = true;
  }
  return moved;
}

function slideRight() {
  let moved = false;
  for (let r = 0; r < rows; r++) {
    let before = board[r].slice();
    let rev = before.slice().reverse();
    rev = slide(rev);
    board[r] = rev.reverse();
    if (!arraysEqual(before, board[r])) moved = true;
  }
  return moved;
}

function slideUp() {
  let moved = false;
  for (let c = 0; c < columns; c++) {
    let col = board.map((r) => r[c]);
    let merged = slide(col);
    for (let r = 0; r < rows; r++) {
      if (board[r][c] !== merged[r]) moved = true;
      board[r][c] = merged[r];
    }
  }
  return moved;
}

function slideDown() {
  let moved = false;
  for (let c = 0; c < columns; c++) {
    let col = board.map((r) => r[c]).reverse();
    let merged = slide(col).reverse();
    for (let r = 0; r < rows; r++) {
      if (board[r][c] !== merged[r]) moved = true;
      board[r][c] = merged[r];
    }
  }
  return moved;
}

// ⬜ Refresh all tiles from board[]
function updateBoardView() {
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < columns; c++) {
      let tile = document.getElementById(`${r}-${c}`);
      updateTile(tile, board[r][c]);
    }
  }
}

// 🔍 Utility to compare arrays
function arraysEqual(a, b) {
  return a.length === b.length && a.every((v, i) => v === b[i]);
}

// ❌ Game‑over: no empty + no merges possible
function isGameOver() {
  if (hasEmptyTile()) return false; // If there are empty tiles, the game isn't over yet.

  // Check for possible merges: horizontally and vertically.
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < columns; c++) {
      // Check if there's a tile to the right to merge with
      if (c < columns - 1 && board[r][c] === board[r][c + 1]) {
        return false; // Merge possible horizontally
      }
      // Check if there's a tile below to merge with
      if (r < rows - 1 && board[r][c] === board[r + 1][c]) {
        return false; // Merge possible vertically
      }
    }
  }

  return true; // No empty tiles and no possible merges, so game over
}

// 🚨 Reveal the Game Over overlay
function showGameOver() {
  stopTimer(); // Stop the timer
  document.getElementById("final-score").innerText = score;
  document.getElementById("game-over").classList.remove("hidden");

  // Send score and time to server
  fetch("save_game.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      score: score,
      time_taken: secondsElapsed,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.newHighScore) {
        document.getElementById("high-score").textContent = data.highScore;
      }
    })
    .catch((error) => console.error("Error:", error));
  setTimeout(() => {
    location.reload(); // Reload the page after delay
  }, 1500); // Adjust delay as needed
}

let gamePaused = false;

// Show the pause button when the game truly starts
function onGameStarted() {
  document.getElementById("pause-resume").style.display = "inline-block";
}
// call onGameStarted() at the end of startNewGame()

// Wire up the pause/resume button once on load
document.getElementById("pause-resume").addEventListener("click", () => {
  gamePaused ? resumeGame() : pauseGame();
});

function pauseGame() {
  gamePaused = true;
  stopTimer(); // stops the interval but doesn’t zero out secondsElapsed
  document.getElementById("pause-resume").innerText = "Resume";
  document.getElementById("board").style.filter = "blur(4px)";
}
function resumeGame() {
  const overlay = document.getElementById("countdown-overlay");
  const numEl = document.getElementById("countdown-number");
  let count = 3;
  numEl.innerText = count;
  overlay.classList.remove("hidden");

  const iv = setInterval(() => {
    count--;
    if (count > 0) {
      numEl.innerText = count;
    } else {
      clearInterval(iv);
      overlay.classList.add("hidden");

      // Now resume the timer and unpause
      startTimer(false);
      gamePaused = false;
      document.getElementById("pause-resume").innerText = "Pause";
      document.getElementById("board").style.filter = "none";
    }
  }, 1000);
}
