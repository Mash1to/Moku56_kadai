<?php
session_start();
header('Content-Type: application/json');

// ログインチェック
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
$id = $data['id'] ?? null;

if ($id === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '予約IDが指定されていません']);
    exit;
}

try {
    // 自分の予約だけ削除できるように制限
    $stmt = $pdo->prepare(
        'DELETE FROM reservations
        WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
        ':id'      => $id,
        ':user_id' => $_SESSION['user_id'],
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => '予約が見つからないか、権限がありません']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '削除に失敗しました']);
}
