<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['org_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$start = trim($data['start'] ?? '');
$end   = trim($data['end'] ?? '');
$room_id = (int)($data['room_id'] ?? 0);

$who_name = trim($data['who_name'] ?? '');
$description = trim($data['description'] ?? '');

if ($title === '' || $start === '' || $end === '' || $room_id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => '必須項目（タイトル/開始/終了/部屋）が不足しています']);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
    'appuser',
    'apppass',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  $org_id = (int)$_SESSION['org_id'];
  $user_id = (int)$_SESSION['user_id'];

  // 部屋が自組織のものかチェック
  $stmt = $pdo->prepare('SELECT id FROM rooms WHERE id = :room_id AND org_id = :org_id');
  $stmt->execute([':room_id' => $room_id, ':org_id' => $org_id]);
  if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'その部屋は利用できません']);
    exit;
  }

  $pdo->beginTransaction();

  // ★ 重複チェック：同じ部屋 × 時間が重なる
  $check = $pdo->prepare(
    'SELECT id
     FROM reservations
     WHERE org_id = :org_id
       AND room_id = :room_id
       AND start < :new_end
       AND end > :new_start
     LIMIT 1
     FOR UPDATE'
  );
  $check->execute([
    ':org_id' => $org_id,
    ':room_id' => $room_id,
    ':new_start' => $start,
    ':new_end' => $end,
  ]);

  if ($check->fetch()) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'その部屋は指定時間に既に予約があります']);
    exit;
  }

  // ★ INSERT（保存）
  $ins = $pdo->prepare(
    'INSERT INTO reservations (org_id, user_id, room_id, title, start, end, who_name, description)
     VALUES (:org_id, :user_id, :room_id, :title, :start, :end, :who_name, :description)'
  );
  $ins->execute([
    ':org_id' => $org_id,
    ':user_id' => $user_id,
    ':room_id' => $room_id,
    ':title' => $title,
    ':start' => $start,
    ':end' => $end,
    ':who_name' => ($who_name === '' ? null : $who_name),
    ':description' => ($description === '' ? null : $description),
  ]);

  $pdo->commit();

  echo json_encode(['success' => true]);

} catch (PDOException $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DBエラー']);
}
