<?php
// index.php
require_once 'config/db.php';

// 1. Taarifa za Chuo
$uni_name = 'Smart School System';
$uni_short = 'DarTU';

// 2. Chota Orodha ya Kozi (Hii inahakikisha kozi mpya zinaonekana)
$courses_sql = "SELECT * FROM courses ORDER BY course_name ASC";
$courses_result = $conn->query($courses_sql);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karibu - <?php echo $uni_short; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles ndogo za kurekebisha muonekano */
        .auth-tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab-btn { flex: 1; padding: 10px; border: none; background: none; cursor: pointer; font-weight: bold; color: #777; }
        .tab-btn.active { color: #2c3e50; border-bottom: 3px solid #2c3e50; }
        .input-group { display: flex; align-items: center; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px; border-radius: 5px; background: #fff; }
        .input-group i { margin-right: 10px; color: #777; width: 20px; text-align: center; }
        .input-group input, .input-group select { border: none; outline: none; width: 100%; font-size: 1rem; background: transparent; }
        .btn-primary { width: 100%; padding: 12px; background: #2c3e50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: bold; }
        .btn-primary:hover { background: #34495e; }
    </style>
</head>
<body>
    <div class="main-container">
        
        <div class="brand-section">
            <div class="brand-content">
                <h1><?php echo $uni_short; ?>Smart</h1>
                <p><?php echo $uni_name; ?></p>
                <div class="hero-image"><i class="fa-solid fa-graduation-cap big-icon" style="font-size: 80px;"></i></div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-wrapper">
                
                <div class="auth-tabs">
                    <button class="tab-btn active" onclick="switchTab('login')">Ingia (Login)</button>
                    <button class="tab-btn" onclick="switchTab('signup')">Jisajili (Signup)</button>
                </div>

                <form id="login-form" class="auth-form active" action="api/login_process.php" method="POST">
                    <h2>Karibu Tena</h2>
                    <p class="text-muted">Ingiza taarifa zako kuendelea</p>
                    <div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="Barua Pepe (Email)" required></div>
                    <div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" name="password" placeholder="Nenosiri (Password)" required></div>
                    <button type="submit" class="btn-primary">Ingia Mfumo</button>
                </form>

                <form id="signup-form" class="auth-form" action="api/signup_process.php" method="POST" style="display: none;">
                    <h2>Anza Safari Yako</h2>
                    <p class="text-muted">Unda akaunti mpya hapa</p>

                    <div class="input-group"><i class="fa-solid fa-user"></i><input type="text" name="full_name" placeholder="Jina Kamili" required></div>
                    <div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="Barua Pepe" required></div>
                    <div class="input-group"><i class="fa-solid fa-phone"></i><input type="tel" name="phone" placeholder="Namba ya Simu" required></div>

                    <div class="input-group">
                        <i class="fa-solid fa-users"></i>
                        <select name="role" id="roleSelect" onchange="toggleInputs()" required>
                            <option value="" disabled selected>Wewe ni Nani?</option>
                            <option value="student">Mwanafunzi</option>
                            <option value="teacher">Mwalimu</option>
                            <option value="admin">Admin (Msimamizi)</option>
                        </select>
                    </div>

                    <div class="input-group" id="pinDiv" style="display:none; border-color: #e53935;">
                        <i class="fa-solid fa-key" style="color: #e53935;"></i>
                        <input type="number" name="security_pin" id="pinInput" placeholder="Ingiza PIN ya Siri" style="color: #e53935;">
                    </div>

                    <div class="input-group" id="courseDiv" style="display:none;">
                        <i class="fa-solid fa-book"></i>
                        <select name="course_id" id="courseSelect">
                            <option value="" disabled selected>Chagua Kozi Yako</option>
                            <?php
                            if ($courses_result->num_rows > 0) {
                                // Hakikisha pointer ipo mwanzo
                                $courses_result->data_seek(0);
                                while($course = $courses_result->fetch_assoc()) {
                                    echo "<option value='".$course['id']."'>".$course['course_name']." (".$course['course_code'].")</option>";
                                }
                            } else {
                                echo "<option value='' disabled>Hakuna Kozi bado</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" name="password" placeholder="Nenosiri Jipya" required></div>
                    <button type="submit" class="btn-primary">Jisajili Sasa</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // KUBADILISHA KATI YA LOGIN NA SIGNUP
        function switchTab(tab) {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('signup-form').style.display = 'none';
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            if(tab === 'login') {
                document.getElementById('login-form').style.display = 'block';
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
            } else {
                document.getElementById('signup-form').style.display = 'block';
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
            }
        }

        // KUFICHA/KUONYESHA INPUTS KULINGANA NA CHEO (NA REQUIRED LOGIC)
        function toggleInputs() {
            var role = document.getElementById('roleSelect').value;
            var courseDiv = document.getElementById('courseDiv');
            var pinDiv = document.getElementById('pinDiv');
            var courseSelect = document.getElementById('courseSelect');
            var pinInput = document.getElementById('pinInput');

            // Reset kwanza (Ficha vyote)
            courseDiv.style.display = 'none';
            pinDiv.style.display = 'none';
            
            // Ondoa required kwa muda ili isilete shida
            courseSelect.required = false;
            pinInput.required = false;

            if(role === 'student') {
                // MWANAFUNZI: Onyesha Kozi, Ficha PIN
                courseDiv.style.display = 'flex';
                courseSelect.required = true; // Lazima achague kozi
            } 
            else if (role === 'teacher' || role === 'admin') {
                // MWALIMU/ADMIN: Onyesha PIN, Ficha Kozi
                pinDiv.style.display = 'flex';
                pinInput.required = true; // Lazima aweke PIN
            }
        }
    </script>
</body>
</html>