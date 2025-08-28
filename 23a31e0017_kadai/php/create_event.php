<?php
header('Content-Type: application/json');
require 'db.php';


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['title'], $data['start'], $data['end'])) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}


try {
    $stmt = $pdo->prepare("INSERT INTO reservations (title, start, end) VALUES (?, ?, ?)");
    $stmt->execute([$data['title'], $data['start'], $data['end']]);
    echo json_encode(['success' => true, 'message' => '予約が作成されました。']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>