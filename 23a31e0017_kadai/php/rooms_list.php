<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'], $_SESSION['org_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'ログインが必要です'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
    'appuser',
    'apppass',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );

  // ★ここが本命：接続文字コードを強制
  $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

  $stmt = $pdo->prepare('SELECT id, name FROM rooms WHERE org_id = :org_id ORDER BY name ASC');
  $stmt->execute([':org_id' => (int)$_SESSION['org_id']]);

  echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DBエラー'], JSON_UNESCAPED_UNICODE);
}
