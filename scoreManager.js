// Initialize high score display when the DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  initHighScore();
});

// Retrieve the current high score from localStorage
function getHighScore() {
  return parseInt(localStorage.getItem("highScore")) || 0;
}

// Update the high score if the current score exceeds it
function updateHighScore(currentScore) {
  const highScore = getHighScore();
  if (currentScore > highScore) {
    localStorage.setItem("highScore", currentScore);
    document.getElementById("high-score").innerText = currentScore;
  }
}

// Initialize the high score display on page load
function initHighScore() {
  const highScore = getHighScore();
  document.getElementById("high-score").innerText = highScore;
}
