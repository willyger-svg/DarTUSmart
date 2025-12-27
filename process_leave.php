<?php
// api/process_leave.php
// Tunasema: Onyesha makosa yote (Error Reporting On)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// 1. ULINZI
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    die("❌ Huna mamlaka ya kufanya hivi. <a href='../index.php'>Rudi Mwanzo</a>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Hakiki kama data zimefika
    if(!isset($_POST['request_id']) || !isset($_POST['action'])) {
        die("❌ Data hazijakamilika.");
    }

    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; 
    $comment = isset($_POST['comment']) ? $conn->real_escape_string($_POST['comment']) : '';
    
    // 3. Badilisha Status
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    // 4. Update Database
    $sql = "UPDATE leave_requests 
            SET status = '$new_status', admin_comment = '$comment' 
            WHERE id = '$request_id'";

    if ($conn->query($sql) === TRUE) {
        // Tumia JavaScript kurudi ili kuepuka 'Headers Sent' error
        echo '<script type="text/javascript">';
        echo 'alert("✅ Imekamilika! Ombi limeshughulikiwa.");';
        echo 'window.location.href = "../teacher/dashboard.php";'; // Hapa inarudi dashboard
        echo '</script>';
        exit;
    } else {
        echo "❌ Hitilafu ya Database: " . $conn->error;
    }

} else {
    // Kama mtu kajaribu kufungua faili hili bila kutuma fomu
    header("Location: ../teacher/dashboard.php");
    exit();
}
?>
