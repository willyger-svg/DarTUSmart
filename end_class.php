<?php
// api/end_class.php
session_start();
require_once '../config/db.php';

// 1. Ulinzi: Hakikisha ni Mwalimu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Huna ruhusa.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $teacher_id = $_SESSION['user_id']; // Tunahitaji ID ya mwalimu anayefunga

    // 2. Hakikisha ID ya ratiba imetumwa
    if (isset($_POST['schedule_id'])) {
        $id = intval($_POST['schedule_id']);
        
        // 3. Zima Kipindi (Tunahakikisha ni kipindi chake tu: AND teacher_id = ...)
        $sql = "UPDATE schedules SET is_active = 0 WHERE id = '$id' AND teacher_id = '$teacher_id'";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                alert('✅ Kipindi kimefungwa kikamilifu!'); 
                window.location.href='../teacher/dashboard.php';
            </script>";
        } else {
            echo "<script>
                alert('❌ Hitilafu ya Database: " . $conn->error . "'); 
                window.location.href='../teacher/dashboard.php';
            </script>";
        }
    } else {
        // Kama ID haikufika
        echo "<script>
            alert('❌ ID ya kipindi haikufika (System Error).'); 
            window.location.href='../teacher/dashboard.php';
        </script>";
    }
}
?>
