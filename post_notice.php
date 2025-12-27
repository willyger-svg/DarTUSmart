<?php
// api/post_notice.php
session_start();
require_once '../config/db.php';

// Ulinzi: Mwalimu/Admin tu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'student') {
    die("Huna ruhusa.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $target = $conn->real_escape_string($_POST['target']); // 'all' au 'students'
    $posted_by = $_SESSION['user_id'];

    $sql = "INSERT INTO notices (title, content, target, posted_by) VALUES ('$title', '$content', '$target', '$posted_by')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('✅ Tangazo limetumwa!'); window.location.href='../teacher/dashboard.php';</script>";
    } else {
        echo "Hitilafu: " . $conn->error;
    }
}
?>
