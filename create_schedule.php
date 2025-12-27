<?php
// api/create_schedule.php
session_start();
require_once '../config/db.php';

// Muda wa Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

// Ulinzi: Hakikisha ni Mwalimu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Huruhusiwi.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_SESSION['user_id'];
    $course_id = intval($_POST['course_id']);
    
    // *** UPDATE: POKEA SUBJECT ID ***
    $subject_id = intval($_POST['subject_id']); 
    
    // Pokea Data Zingine
    $topic = $conn->real_escape_string($_POST['topic']);
    $venue = $conn->real_escape_string($_POST['venue']);
    
    $class_date = $_POST['class_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $today = date('Y-m-d');
    $sasa = date('H:i');

    // 1. Ulinzi: Tarehe iliyopita
    if ($class_date < $today) {
        echo "<script>alert('❌ Huwezi kupanga ratiba kwa tarehe iliyopita!'); window.location.href='../teacher/dashboard.php';</script>";
        exit();
    }

    // 2. Ulinzi: Muda uliopita (Kama ni leo)
    if ($class_date == $today && $start_time < $sasa) {
        echo "<script>alert('❌ Muda wa kuanza umeshapita! Tafadhali panga muda ujao.'); window.location.href='../teacher/dashboard.php';</script>";
        exit();
    }

    // 3. Ulinzi: Muda wa kuisha usiwe nyuma ya kuanza
    if ($end_time <= $start_time) {
        echo "<script>alert('❌ Muda wa kumaliza lazima uwe mbele ya muda wa kuanza!'); window.location.href='../teacher/dashboard.php';</script>";
        exit();
    }

    // 4. Ulinzi: Kuzuia Muingiliano (Double Booking)
    $check_clash = "SELECT id FROM schedules WHERE teacher_id = '$teacher_id' 
                    AND class_date = '$class_date' 
                    AND (
                        ('$start_time' >= start_time AND '$start_time' < end_time) 
                        OR ('$end_time' > start_time AND '$end_time' <= end_time)
                        OR (start_time >= '$start_time' AND end_time <= '$end_time')
                    )";
    
    if ($conn->query($check_clash)->num_rows > 0) {
        echo "<script>alert('❌ Una kipindi kingine muda huo! Angalia ratiba yako usipishanishe vipindi.'); window.location.href='../teacher/dashboard.php';</script>";
        exit();
    }

    // Token Mpya ya QR
    $session_token = "SC-" . rand(1000, 9999) . "-" . time();

    // *** UPDATE: INGIZA subject_id KWENYE DATABASE ***
    $sql = "INSERT INTO schedules (teacher_id, course_id, subject_id, class_date, start_time, end_time, session_token, is_active, topic, venue) 
            VALUES ('$teacher_id', '$course_id', '$subject_id', '$class_date', '$start_time', '$end_time', '$session_token', 0, '$topic', '$venue')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('✅ Ratiba Imepangwa Kikamilifu!'); window.location.href='../teacher/dashboard.php';</script>";
    } else {
        echo "Hitilafu: " . $conn->error;
    }
}
?>
