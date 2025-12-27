<?php
require_once '../config/db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $new_token = "SC-" . rand(1000, 9999) . "-" . time(); // Token mpya
    $sql = "UPDATE schedules SET session_token = '$new_token' WHERE id = '$schedule_id'";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'new_token' => $new_token]);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
