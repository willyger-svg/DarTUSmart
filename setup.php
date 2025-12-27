<?php
require_once 'config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL
);";

if ($conn->query($sql) === TRUE) {
    // Ongeza admin mmoja wa majaribio
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$pass', 'admin')");
    echo "Hongera! Database imetengenezwa na Admin amewekwa. <a href='index.php'>Bofya hapa kuingia</a>";
} else {
    echo "Kuna shida: " . $conn->error;
}
?>
