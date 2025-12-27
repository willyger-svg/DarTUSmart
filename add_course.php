<?php
// api/add_course.php
session_start();
require_once '../config/db.php';

// Ni Admin pekee anaruhusiwa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Huruhusiwi.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $course_code = $conn->real_escape_string($_POST['course_code']);

    $sql = "INSERT INTO courses (course_name, course_code) VALUES ('$course_name', '$course_code')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('✅ Somo limesajiliwa!'); window.location.href='../admin/dashboard.php';</script>";
    } else {
        echo "Hitilafu: " . $conn->error;
    }
}
?>
