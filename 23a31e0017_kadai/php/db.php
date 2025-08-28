<?php
$dsn = 'mysql:host=db;dbname=reservation_db;charset=utf8mb4';
$user = 'appuser';
$password = 'apppass';


try {
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
http_response_code(500);
echo json_encode(['error' => 'Database connection failed.']);
exit;
}
?>