<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
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
  echo json_encode(['success' => false, 'error' => 'DB接続失敗']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$start = trim($data['start'] ?? '');
$end   = trim($data['end'] ?? '');

$location = trim($data['location'] ?? '');
$who_name = trim($data['who_name'] ?? '');
$description = trim($data['description'] ?? '');

if ($title === '' || $start === '' || $end === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => '必須項目（タイトル/開始/終了）が不足しています']);
  exit;
}

// 重複禁止の対象を「場所」単位でやるので必須にする
if ($location === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => '場所は必須です（重複チェックのため）']);
  exit;
}

// start < end チェック（文字列でも比較できないので PHPで確認）
try {
  $dtStart = new DateTime($start);
  $dtEnd   = new DateTime($end);
  if ($dtStart >= $dtEnd) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '終了日時は開始日時より後にしてください']);
    exit;
  }
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => '日時形式が不正です']);
  exit;
}

try {
  $pdo->beginTransaction();

  // ★ 重複チェック：同じ場所で
  //   既存.start < 新.end AND 既存.end > 新.start なら「重複」
  //   競合を防ぐため FOR UPDATE でロック
  $check = $pdo->prepare(
    'SELECT id, title, start, end
     FROM reservations
     WHERE location = :location
       AND start < :new_end
       AND end > :new_start
     LIMIT 1
     FOR UPDATE'
  );
  $check->execute([
    ':location' => $location,
    ':new_start' => $start,
    ':new_end' => $end,
  ]);

  $conflict = $check->fetch(PDO::FETCH_ASSOC);
  if ($conflict) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode([
      'success' => false,
      'error' => 'その場所は指定時間に既に予約があります',
      'conflict' => $conflict,
    ]);
    exit;
  }

  // 登録
  $stmt = $pdo->prepare(
    'INSERT INTO reservations (user_id, location, who_name, title, start, end, description)
     VALUES (:user_id, :location, :who_name, :title, :start, :end, :description)'
  );
  $stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':location' => $location,
    ':who_name' => $who_name,
    ':title' => $title,
    ':start' => $start,
    ':end' => $end,
    ':description' => ($description === '' ? null : $description),
  ]);

  $pdo->commit();
  echo json_encode(['success' => true]);
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => '登録に失敗しました']);
}
