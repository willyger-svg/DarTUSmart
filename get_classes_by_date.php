<?php
// api/get_classes_by_date.php
session_start();
require_once '../config/db.php';

// Set Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Hakikisha request imekamilika
if (!isset($_SESSION['user_id']) || !isset($_GET['date'])) {
    echo json_encode([]);
    exit();
}

$course_id = $_SESSION['course_id']; // Kozi ya mwanafunzi
$date = $conn->real_escape_string($_GET['date']); // Tarehe aliyochagua

// Vuta vipindi vya siku hiyo kwa kozi hiyo
// Tunavuta pia jina la SOMO na jina la MWALIMU ili mwanafunzi ajue anachagua nini
$sql = "SELECT s.id, s.start_time, s.end_time, sub.subject_name, sub.subject_code, u.full_name as teacher 
        FROM schedules s
        LEFT JOIN subjects sub ON s.subject_id = sub.id
        JOIN users u ON s.teacher_id = u.id
        WHERE s.course_id = '$course_id' AND s.class_date = '$date'
        ORDER BY s.start_time ASC";

$result = $conn->query($sql);

$classes = [];
while($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Rudisha data kama JSON (Hii ndiyo JavaScript inasoma)
header('Content-Type: application/json');
echo json_encode($classes);
?>
