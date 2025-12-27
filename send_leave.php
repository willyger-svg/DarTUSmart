<?php
// api/send_leave.php
session_start();
require_once '../config/db.php';

// Weka saa iwe ya Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

// 1. Ulinzi: Hakikisha ni mwanafunzi aliyeingia
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Huruhusiwi kufanya hivi.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $student_id = $_SESSION['user_id'];
    
    // 2. Pokea Data Mpya
    // Sasa tunapokea 'leave_date' na array ya 'schedules' badala ya start/end range
    $reason = $conn->real_escape_string($_POST['reason']);
    $date = $conn->real_escape_string($_POST['leave_date']); // Tarehe ya ruhusa

    // Hakikisha amechagua angalau kipindi kimoja
    if (!isset($_POST['schedules']) || empty($_POST['schedules'])) {
        die("<script>alert('❌ Tafadhali chagua angalau kipindi kimoja (au tick \"Chagua Vyote\").'); window.history.back();</script>");
    }

    $selected_schedules = $_POST['schedules']; // Hii ni Array (List ya ID za vipindi)

    // 3. Hakikisha tarehe ziko sawa (Hauwezi kuomba ruhusa ya jana)
    $today = date("Y-m-d");
    if ($date < $today) {
        echo "<script>alert('❌ Huwezi kuomba ruhusa kwa tarehe iliyopita!'); window.location.href='../student/dashboard.php';</script>";
        exit();
    }

    // ============================================================
    // 4. LOGIC YA KUFUTA ZA ZAMANI (AUTO-CLEANUP) 🧹
    // ============================================================
    // Tunahesabu ana maombi mangapi sasa hivi
    $count_sql = "SELECT COUNT(*) as total FROM leave_requests WHERE student_id = '$student_id'";
    $count_result = $conn->query($count_sql);
    $total_requests = $count_result->fetch_assoc()['total'];

    // Ikiwa zimefika 10 au zaidi, futa za zamani ili kupunguza msongamano
    if ($total_requests >= 10) {
        // Tunafuta 5 za mwanzo kabisa (kwa kutumia ID ndogo)
        $conn->query("DELETE FROM leave_requests WHERE student_id = '$student_id' ORDER BY id ASC LIMIT 5");
    }

    // ============================================================
    // 5. INGIZA MAOMBI KWA KILA KIPINDI (LOOP) 🔄
    // ============================================================
    // Tunazunguka kwenye kila kipindi alichotick na kuingiza ombi
    $success = false;

    foreach ($selected_schedules as $schedule_id) {
        $sid = intval($schedule_id); // Safisha ID iwe namba

        // Tunahifadhi schedule_id ili mwalimu wa kipindi hicho tu ndiye aone
        // start_date na end_date zinawekwa sawa sababu ni per-class session
        $sql = "INSERT INTO leave_requests (student_id, schedule_id, reason, start_date, end_date, status) 
                VALUES ('$student_id', '$sid', '$reason', '$date', '$date', 'pending')";

        if ($conn->query($sql) === TRUE) {
            $success = true;
        }
    }

    if ($success) {
        echo "<script>alert('✅ Maombi yako yametumwa kwa Walimu husika!'); window.location.href='../student/dashboard.php';</script>";
    } else {
        echo "Hitilafu imetokea wakati wa kutuma.";
    }

} else {
    header("Location: ../student/dashboard.php");
}
?>
