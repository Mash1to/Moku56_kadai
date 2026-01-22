<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['org_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'ログインが必要です']);
  exit;
}

$rangeStart = $_GET['start'] ?? null;
$rangeEnd   = $_GET['end'] ?? null;

if (!$rangeStart || !$rangeEnd) {
  $rangeStart = date('Y-m-01 00:00:00');
  $rangeEnd   = date('Y-m-t 23:59:59', strtotime('+1 month'));
}

$palette = ['#6c63ff','#00b894','#ff9f1a','#e056fd','#22a6b3','#ff6b6b','#1dd1a1','#5f27cd'];
function colorFor($key, $palette) {
  $idx = abs(crc32((string)$key)) % count($palette);
  return $palette[$idx];
}

try {
  $pdo = new PDO(
    'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
    'appuser',
    'apppass',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  $org_id = (int)$_SESSION['org_id'];

  $stmt = $pdo->prepare(
    'SELECT r.id, r.title, r.start, r.end, r.room_id, ro.name AS room_name, r.who_name, r.description
     FROM reservations r
     JOIN rooms ro ON ro.id = r.room_id
     WHERE r.org_id = :org_id
       AND r.start < :rangeEnd
       AND r.end > :rangeStart
     ORDER BY r.start ASC'
  );
  $stmt->execute([
    ':org_id' => $org_id,
    ':rangeStart' => $rangeStart,
    ':rangeEnd' => $rangeEnd
  ]);

  $events = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roomName = (string)$row['room_name'];
    $color = colorFor($roomName, $palette);

    $events[] = [
      'id' => (string)$row['id'],
      'title' => $row['title'],
      'start' => $row['start'],
      'end' => $row['end'],
      'backgroundColor' => $color,
      'borderColor' => $color,
      'textColor' => '#ffffff',
      'extendedProps' => [
        'location' => $roomName,            // ← JS側は location として扱う（表示用）
        'room_id' => (int)$row['room_id'],
        'who_name' => $row['who_name'],
        'description' => $row['description'],
      ]
    ];
  }

  echo json_encode($events, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DBエラー']);
}
