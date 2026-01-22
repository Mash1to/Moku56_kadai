<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['org_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'IDが不正です']);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
    'appuser',
    'apppass',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  $stmt = $pdo->prepare(
    'DELETE FROM reservations
     WHERE id = :id AND org_id = :org_id AND user_id = :user_id'
  );
  $stmt->execute([
    ':id' => $id,
    ':org_id' => (int)$_SESSION['org_id'],
    ':user_id' => (int)$_SESSION['user_id'],
  ]);

  if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => '予約が見つからないか権限がありません']);
    exit;
  }

  echo json_encode(['success' => true]);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DBエラー']);
}
