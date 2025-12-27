<?php
// api/diagnose.php
// FILE HILI NI KWA AJILI YA KUCHUNGUZA TATIZO TU
session_start();
require_once '../config/db.php';
date_default_timezone_set('Africa/Dar_es_Salaam');

echo "<h1>🕵️‍♂️ Ripoti ya Uchunguzi (System Diagnosis)</h1>";
echo "<hr>";

// 1. ANGALIA SESSION YA MWANAFUNZI
echo "<h3>1. Mwanafunzi Aliyeingia (William)</h3>";
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $sql = "SELECT id, full_name, role, course_id FROM users WHERE id = '$uid'";
    $res = $conn->query($sql);
    $user = $res->fetch_assoc();
    
    echo "Jina: " . $user['full_name'] . "<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "<strong>Course ID Yake: " . ($user['course_id'] ? $user['course_id'] : 'HANA KOZI (NULL)') . "</strong><br>";
    
    // TAFUTA JINA LA KOZI YAKE
    if($user['course_id']) {
        $cid = $user['course_id'];
        $cname = $conn->query("SELECT course_name FROM courses WHERE id = '$cid'")->fetch_assoc()['course_name'];
        echo "Jina la Kozi: <span style='color:blue'>$cname</span>";
    }
} else {
    echo "<span style='color:red'>Hujaingia (Login first).</span>";
}
echo "<hr>";

// 2. ANGALIA VIPINDI VILIVYOPO LEO
echo "<h3>2. Vipindi Vilivyopangwa Leo (" . date('Y-m-d') . ")</h3>";
$today = date('Y-m-d');
$sql_sch = "SELECT s.*, c.course_name FROM schedules s JOIN courses c ON s.course_id = c.id WHERE class_date = '$today'";
$res_sch = $conn->query($sql_sch);

if ($res_sch->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Mwalimu ID</th><th>Course ID</th><th>Jina la Kozi</th><th>Muda</th><th>Status (Active)</th></tr>";
    while ($row = $res_sch->fetch_assoc()) {
        $active_color = ($row['is_active'] == 1) ? 'green' : 'red';
        $active_text = ($row['is_active'] == 1) ? 'LINANEDELEA (1)' : 'LIMEZIMWA (0)';
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['teacher_id'] . "</td>";
        echo "<td><strong>" . $row['course_id'] . "</strong></td>"; // Hii ndio tunalinganisha
        echo "<td>" . $row['course_name'] . "</td>";
        echo "<td>" . $row['start_time'] . " - " . $row['end_time'] . "</td>";
        echo "<td style='color:$active_color; font-weight:bold;'>" . $active_text . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Hakuna ratiba yoyote iliyopangwa leo kwenye Database.</p>";
}

echo "<hr>";
echo "<h3>HITIMISHO:</h3>";
echo "<p>Ili William aone kipindi, lazima <strong>Course ID yake (Sehemu ya 1)</strong> ifanane na <strong>Course ID ya Kipindi (Sehemu ya 2)</strong>.</p>";
echo "<p>Pia, ili aone 'Linaendelea', lazima Status iwe <strong>ACTIVE (1)</strong>.</p>";
?>
