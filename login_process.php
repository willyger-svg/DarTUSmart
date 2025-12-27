<?php
// api/login_process.php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // 1. Tafuta mtumiaji kwa email
    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. Hakiki Password (Inalinganisha iliyoandikwa na ile iliyofichwa)
        if (password_verify($password, $user['password'])) {
            
            // Password ni SAHIHI - Tunatengeneza Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['course_id'] = $user['course_id'];

            // 3. Muelekeze kulingana na cheo chake (Redirection)
            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] == 'teacher') {
                header("Location: ../teacher/dashboard.php");
            } elseif ($user['role'] == 'student') {
                header("Location: ../student/dashboard.php");
            }
            exit();

        } else {
            // Password Sio Sahihi
            echo "<script>alert('❌ Nenosiri sio sahihi!'); window.location.href='../index.php';</script>";
        }
    } else {
        // Email haipo
        echo "<script>alert('❌ Akaunti hii haipo. Tafadhali jisajili.'); window.location.href='../index.php';</script>";
    }
}
?>
