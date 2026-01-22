<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode([]);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
    'appuser',
    'apppass',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([]);
  exit;
}

$sql = 'SELECT id, title, start, end, location, who_name, description
        FROM reservations
        WHERE user_id = :user_id
        ORDER BY start ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]);

$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $events[] = [
    'id' => $row['id'],
    'title' => $row['title'],  // カレンダー表示のメイン
    'start' => $row['start'],
    'end' => $row['end'],
    'extendedProps' => [
      'location' => $row['location'],
      'who_name' => $row['who_name'],
      'description' => $row['description'],
    ],
  ];
}

echo json_encode($events);
