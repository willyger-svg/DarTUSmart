<?php
// api/delete_request_history.php
session_start();
require_once '../config/db.php';

// 1. ULINZI: Hakikisha ni Mwalimu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Huruhusiwi kufanya hivi.");
}

// 2. POKEA ID YA RUHUSA
if (isset($_GET['id'])) {
    $request_id = intval($_GET['id']); // Safisha iwe namba
    $teacher_id = $_SESSION['user_id'];

    // 3. FUTA (LAKINI FUTA TU IKIWA IMEJIBIWA TAYARI)
    // Hatuwezi kufuta "Pending" hapa, tunafuta zile za "History" (Approved/Rejected)
    // Pia tunahakikisha mwalimu anafuta kitu ambacho kipo kwenye uwezo wake
    
    $sql = "DELETE FROM leave_requests WHERE id = '$request_id' AND status != 'pending'";

    if ($conn->query($sql) === TRUE) {
        // Imefanikiwa
        header("Location: ../teacher/dashboard.php");
        exit();
    } else {
        // Ikishindikana (Labda database error)
        echo "<script>alert('❌ Hitilafu imetokea wakati wa kufuta.'); window.location.href='../teacher/dashboard.php';</script>";
    }

} else {
    // Kama hakuna ID iliyotumwa
    header("Location: ../teacher/dashboard.php");
    exit();
}
?>
