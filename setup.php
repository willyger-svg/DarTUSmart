<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/db.php';

echo "<h2>🛠️ Tunaongeza Tables Zilizokosekana...</h2>";

// 1. Users (Hii ipo, lakini tunahakikisha)
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL
);";
$conn->query($sql);

// 2. COURSES (Hii ndiyo iliyolalamika 'Doesn't exist')
$sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(50) NOT NULL UNIQUE,
    teacher_id INT
);";
$conn->query($sql);

// 3. STUDENTS (Wanafunzi)
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    reg_number VARCHAR(50) NOT NULL UNIQUE
);";
$conn->query($sql);

// 4. ATTENDANCE (Mahudhurio)
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    status ENUM('Present', 'Absent', 'Excused'),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

if ($conn->query($sql) === TRUE) {
    echo "<h1>✅ Tables zote (Courses, Students, Attendance) zimewekwa!</h1>";
    echo "<h3><a href='index.php'>BOFYA HAPA KUINGIA (LOGIN)</a></h3>";
} else {
    echo "❌ Kosa: " . $conn->error;
}
?>

