<?php
header('Content-Type: application/json');
require 'db.php';


try {
    $stmt = $pdo->query("SELECT id, title, start, end FROM reservations");
    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'color' => '#6c63ff'
        ];
    }
    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>