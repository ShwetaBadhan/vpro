<?php
include('db/config.php');
header('Content-Type: application/json');

$events = [];
$sql = "SELECT event_id, title, description, event_date, type FROM event_calendar WHERE status='1'";
$result = mysqli_query($db, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = [
            'id' => $row['event_id'],
            'title' => $row['title'],
            'start' => $row['event_date'],
            'description' => $row['description'],
            'type' => $row['type']
        ];
    }
}

echo json_encode($events);
?>
