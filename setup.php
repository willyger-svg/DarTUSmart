
?>
<?php
// Washa ripoti ya makosa ili tuone shida iko wapi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🛠️ Mchakato wa Kurekebisha Database</h1>";

// Jaribu kuunganisha kwa kutumia faili la config
if (!file_exists('config/db.php')) {
    die("❌ Kosa: Faili la config/db.php halionekani! Hakikisha umelitengeneza.");
}

include 'config/db.php';

// Hakikisha connection ipo
if ($conn->connect_error) {
    die("❌ Connection Failed: " . $conn->connect_error);
}
echo "✅ Connection imekubali! <br>";

// SQL ya kutengeneza Table ya Users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'users' imetengenezwa.<br>";
    
    // Weka Admin
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $check = $conn->query("SELECT id FROM users WHERE username='admin'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO users (username, password, role) VALUES ('admin', '$pass', 'admin')");
        echo "✅ Admin ameongezwa (User: admin, Pass: admin123).<br>";
    } else {
        echo "ℹ️ Admin yupo tayari.<br>";
    }
} else {
    echo "❌ Imeshindikana kutengeneza table: " . $conn->error . "<br>";
}

echo "<hr><h3>🎉 Kila kitu tayari! <a href='index.php'>Bofya hapa uka-Login</a></h3>";
?>
