// public/time.js

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60)
    .toString()
    .padStart(2, "0");
  const secs = (seconds % 60).toString().padStart(2, "0");
  return `${mins}:${secs}`;
}

// Now takes a boolean `reset`: if true, secondsElapsed → 0; if false, keep old value
function startTimer(reset = false) {
  clearInterval(timerInterval);

  if (reset) {
    secondsElapsed = 0;
  }
  // immediately show the “current” time
  document.getElementById("timer").textContent = formatTime(secondsElapsed);

  timerInterval = setInterval(() => {
    secondsElapsed++;
    document.getElementById("timer").textContent = formatTime(secondsElapsed);
  }, 1000);
}

function stopTimer() {
  clearInterval(timerInterval);
}
