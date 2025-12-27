
<?php
// api/signup_process.php
session_start();
require_once '../config/db.php';

// MUHIMU: Weka saa iwe ya Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Pokea Data na Safisha (Sanitize)
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password'];
    $input_pin = isset($_POST['security_pin']) ? intval($_POST['security_pin']) : 0;
    
    // 2. LOGIC YA KOZI (HAPA NDIYO PALIPOKUWA NA TATIZO)
    // Default course ni NULL (kwa walimu na admin)
    $course_id = "NULL";

    if ($role == 'student') {
        // Ikiwa ni mwanafunzi, LAZIMA achague kozi
        if (!isset($_POST['course_id']) || empty($_POST['course_id'])) {
            echo "<script>alert('❌ Lazima uchague Kozi ili kujisajili!'); window.history.back();</script>";
            exit();
        }
        $course_id = intval($_POST['course_id']);
    }

    // 3. LOGIC YA PIN (USALAMA - Formula ya Hesabu) 🔐
    // Hii inazuia mwanafunzi asijisajili kama Mwalimu au Admin kiholela
    
    $siku = intval(date('j')); // 1-31
    $mwezi = intval(date('n')); // 1-12
    $saa = intval(date('G')); // 0-23

    if ($role == 'teacher') {
        // FORMULA MWALIMU: Siku * Mwezi (Mfano: Tarehe 5 Mwezi wa 10 = 50)
        $expected_pin = $siku * $mwezi;
        
        // Ruhusu PIN ya dharura (Backdoor kwa ajili ya demo): 9999
        if ($input_pin !== $expected_pin && $input_pin !== 9999) {
            die("<script>alert('⛔ PIN ya Mwalimu sio sahihi! (Formula: Siku x Mwezi)'); window.location.href='../index.php';</script>");
        }
    } 
    elseif ($role == 'admin') {
        // FORMULA ADMIN: Saa * Siku (Mfano: Saa 4 asubuhi Tarehe 2 = 8)
        $expected_pin = $saa * $siku;

        // Ruhusu PIN ya dharura: 7777
        if ($input_pin !== $expected_pin && $input_pin !== 7777) {
            die("<script>alert('⛔ PIN ya Admin sio sahihi! (Formula: Saa x Siku)'); window.location.href='../index.php';</script>");
        }
    }

    // 4. Hakiki Email (Isijirudie)
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    if ($conn->query($check_email)->num_rows > 0) {
        die("<script>alert('❌ Email hii imeshatumika! Tafadhali tumia nyingine.'); window.history.back();</script>");
    }

    // 5. Sajili Mtu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Query imeboreshwa
    // Kumbuka: $course_id hapa inaweza kuwa namba (mfano: 1) au neno "NULL"
    $sql = "INSERT INTO users (full_name, email, phone, password, role, course_id) 
            VALUES ('$full_name', '$email', '$phone', '$hashed_password', '$role', $course_id)";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('✅ Usajili Umekamilika! Sasa unaweza kuingia (Login).'); window.location.href='../index.php';</script>";
    } else {
        // Onyesha error halisi kama ipo (kwa ajili ya debugging)
        echo "Hitilafu: " . $conn->error;
    }
}
?>
