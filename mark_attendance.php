<?php
// api/mark_attendance.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// 1. Ulinzi: Mwanafunzi tu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Huruhusiwi.']);
    exit();
}

// 2. Pokea Data ya JSON (kutoka kwa Scanner)
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Token haipo.']);
    exit();
}

$token = $conn->real_escape_string($data['token']);
$student_id = $_SESSION['user_id'];

// 3. Tafuta Ratiba yenye Token hiyo na Iko ACTIVE
$sql = "SELECT id, course_id, teacher_id FROM schedules WHERE session_token = '$token' AND is_active = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
    $schedule_id = $schedule['id'];

    // 4. Angalia kama ameshahudhuria tayari
    $check = $conn->query("SELECT id FROM attendance WHERE student_id = '$student_id' AND schedule_id = '$schedule_id'");
    
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => '⚠️ Tayari ushahudhuria darasa hili!']);
    } else {
        // 5. Weka Mahudhurio
        $insert = "INSERT INTO attendance (student_id, schedule_id, scanned_at) VALUES ('$student_id', '$schedule_id', NOW())";
        if ($conn->query($insert)) {
            echo json_encode(['status' => 'success', 'message' => '✅ Mahudhurio yamerekodiwa kikamilifu!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Hitilafu ya Database.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ QR Code hii sio sahihi au muda umekwisha!']);
}
?>
