<?php
// api/delete_schedule.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Huna ruhusa.");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $teacher_id = $_SESSION['user_id'];

    // Futa ratiba (Ila hakikisha ni ya mwalimu huyu)
    $sql = "DELETE FROM schedules WHERE id = '$id' AND teacher_id = '$teacher_id'";

    if ($conn->query($sql) === TRUE) {
        // Futa na attendance zinazohusiana nayo ili kusafisha database
        $conn->query("DELETE FROM attendance WHERE schedule_id = '$id'");
        echo "<script>alert('✅ Ratiba imefutwa!'); window.location.href='../teacher/dashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Hitilafu!'); window.location.href='../teacher/dashboard.php';</script>";
    }
} else {
    header("Location: ../teacher/dashboard.php");
}
?>
