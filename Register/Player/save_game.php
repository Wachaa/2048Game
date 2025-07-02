<?php
require_once __DIR__ . '/../config.php';
session_start();

// Must be POST with JSON
$input = json_decode(file_get_contents('php://input'), true);

// --- 1. Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$newScore  = isset($input['score']) ? (int) $input['score'] : 0;
$seconds   = isset($input['time_taken']) ? (int) $input['time_taken'] : 0;
// Convert seconds to H:i:s for the TIME column
$timeTaken = gmdate('H:i:s', $seconds);
error_log("Parsed time_taken: $seconds → $timeTaken");

// --- 2. Always insert into score_history
$histStmt = $conn->prepare("
    INSERT INTO score_history (user_id, score, time_taken)
    VALUES (?, ?, ?)
");
$histStmt->bind_param("iis", $userId, $newScore, $timeTaken);
$histStmt->execute();
$histStmt->close();

// --- 3. Fetch existing high score
$stmt = $conn->prepare("SELECT high_score FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentHighScore);
$stmt->fetch();
$stmt->close();

// --- 4. Compare & possibly update users.high_score + users.top_time
$isNewHigh = false;
if ($newScore > $currentHighScore) {
    $update = $conn->prepare("
        UPDATE users
        SET high_score = ?, top_time = ?
        WHERE id = ?
    ");
    $update->bind_param("isi", $newScore, $timeTaken, $userId);
    $update->execute();
    $update->close();
    $isNewHigh = true;
    $currentHighScore = $newScore;
}

echo json_encode([
    'success'       => true,
    'newHighScore'  => $isNewHigh,
    'highScore'     => $currentHighScore,
    'topTime'       => $isNewHigh ? $timeTaken : null
]);
