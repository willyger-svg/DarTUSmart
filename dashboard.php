<?php
// teacher/dashboard.php
session_start();
require_once '../config/db.php';

// MUHIMU: Set Timezone iwe Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

// 1. ULINZI
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];

// ============================================================
// 2. LOGIC ZA MUDA (AUTO START & STOP)
// ============================================================
$today = date('Y-m-d');
$current_time = date('H:i:s');

// A. AUTO-CLOSE
$conn->query("UPDATE schedules SET is_active = 0 WHERE class_date = '$today' AND end_time < '$current_time' AND is_active = 1");

// B. AUTO-START
$conn->query("UPDATE schedules SET is_active = 1 WHERE teacher_id = '$user_id' AND class_date = '$today' AND start_time <= '$current_time' AND end_time > '$current_time' AND is_active = 0");

// 3. KIPINDI KINACHOENDELEA (ACTIVE CLASS)
$active_class_sql = "SELECT s.*, c.course_name, c.course_code, sub.subject_name 
                     FROM schedules s 
                     JOIN courses c ON s.course_id = c.id
                     LEFT JOIN subjects sub ON s.subject_id = sub.id
                     WHERE s.teacher_id = '$user_id' 
                     AND s.class_date = '$today'
                     AND s.is_active = 1
                     ORDER BY s.start_time DESC LIMIT 1";
$active_class_result = $conn->query($active_class_sql);
$active_session = $active_class_result->fetch_assoc();

// RATIBA ZIJAZO
$my_schedules_sql = "SELECT s.*, c.course_name, c.course_code, sub.subject_name 
                     FROM schedules s 
                     JOIN courses c ON s.course_id = c.id
                     LEFT JOIN subjects sub ON s.subject_id = sub.id
                     WHERE s.teacher_id = '$user_id' 
                     AND (s.class_date > '$today' OR (s.class_date = '$today' AND s.end_time > '$current_time'))
                     ORDER BY s.class_date ASC, s.start_time ASC";
$my_schedules_result = $conn->query($my_schedules_sql);

// ============================================================
// 4. MAOMBI YA RUHUSA (SMART ROUTING) 🧠
// ============================================================
// Logic: Onyesha ruhusa ambazo zimeelekezwa kwenye kipindi (schedule) ambacho Mwalimu huyu ndiye aliyekipanga.
$requests_sql = "SELECT lr.*, u.full_name, c.course_code, sub.subject_name, s.start_time, s.class_date
                 FROM leave_requests lr 
                 JOIN users u ON lr.student_id = u.id 
                 JOIN schedules s ON lr.schedule_id = s.id
                 JOIN courses c ON s.course_id = c.id
                 LEFT JOIN subjects sub ON s.subject_id = sub.id
                 WHERE lr.status = 'pending' 
                 AND s.teacher_id = '$user_id'
                 ORDER BY lr.start_date ASC";
$requests_result = $conn->query($requests_sql);

// 5. HISTORIA YA RUHUSA (ZILIZOJIBIWA NA HUYU MWALIMU)
$history_sql = "SELECT lr.*, u.full_name, c.course_code, sub.subject_name, s.class_date
                FROM leave_requests lr 
                JOIN users u ON lr.student_id = u.id 
                JOIN schedules s ON lr.schedule_id = s.id
                JOIN courses c ON s.course_id = c.id
                LEFT JOIN subjects sub ON s.subject_id = sub.id
                WHERE lr.status != 'pending' 
                AND s.teacher_id = '$user_id'
                ORDER BY lr.request_date DESC LIMIT 20";
$history_result = $conn->query($history_sql);


// 6. MAHUDHURIO (LIST)
$attend_sql = "SELECT a.*, u.full_name, s.start_time, c.course_code, sub.subject_name 
               FROM attendance a 
               JOIN users u ON a.student_id = u.id 
               JOIN schedules s ON a.schedule_id = s.id 
               JOIN courses c ON s.course_id = c.id
               LEFT JOIN subjects sub ON s.subject_id = sub.id
               WHERE s.teacher_id = '$user_id' 
               AND a.scanned_at >= (NOW() - INTERVAL 24 HOUR)
               ORDER BY a.scanned_at DESC";
$attend_result = $conn->query($attend_sql);

// 7. COUNTS & DATA
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$courses_result = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
$subjects_result = $conn->query("SELECT * FROM subjects ORDER BY subject_name ASC");
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mwalimu</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .action-btn { padding: 5px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: bold; }
        .btn-approve { background: #2e7d32; }
        .btn-reject { background: #c62828; }
        .btn-delete { background: #c62828; padding: 5px 10px; font-size: 0.8rem; }
        .btn-archive { background: #607d8b; padding: 5px 10px; font-size: 0.8rem; text-decoration: none; color: white; border-radius: 4px;}
        
        .req-card { border-left: 4px solid var(--primary-color); background: #fff; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .history-card { border-left: 4px solid #757575; background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-radius: 5px;}
        
        .qr-box { border: 2px dashed var(--primary-color); padding: 10px; display: inline-block; border-radius: 10px; margin-top: 10px; background: white; position: relative; }
        .qr-img { width: 160px; height: 160px; }
        .timer-bar { height: 4px; background: #e53935; width: 0%; transition: width 1s linear; margin-top: 5px; border-radius: 2px; }
        
        .attend-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        .attend-table th, .attend-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .attend-table th { background-color: var(--primary-color); color: white; }
        .attend-table tr:nth-child(even) { background-color: #f2f2f2; }
        
        .schedule-item { background: #fff; border-bottom: 1px solid #eee; padding: 10px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Status Badge */
        .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; color: white; font-weight: bold; }
        .status-approved { background: #2e7d32; }
        .status-rejected { background: #c62828; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <div class="header-profile">
            <div>
                <h2 style="color: var(--primary-color);">Mwalimu <?php echo explode(' ', $teacher_name)[0]; ?> 👨‍🏫</h2>
                <p class="text-muted" style="margin:0;">Simamia Darasa</p>
            </div>
            <div class="profile-pic-small" style="background: var(--primary-color); color: white;">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
        </div>

        <div id="tab-home" class="tab-content">
            <div class="card stat-grid">
                <div class="stat-box"><div class="stat-number"><?php echo $total_students; ?></div><small>Wanafunzi</small></div>
                <div class="stat-box"><div class="stat-number" style="color: #2e7d32;"><?php echo $attend_result->num_rows; ?></div><small>Waliohudhuria (24h)</small></div>
            </div>

            <div class="card">
                <h3>⚡ Hali ya Darasa</h3>
                
                <?php if($active_session): ?>
                    <div style="text-align: center; background: #e3f2fd; padding: 15px; border-radius: 10px; border: 1px solid #90caf9;">
                        <h4 style="color: var(--primary-color); margin-bottom: 5px;">🔥 Kipindi Kinaendelea</h4>
                        <p style="font-size: 1.1rem;">
                            <strong><?php echo $active_session['course_code']; ?></strong> - 
                            <?php echo $active_session['subject_name'] ? $active_session['subject_name'] : 'Somo Halikutajwa'; ?>
                        </p>
                        <?php if(!empty($active_session['topic'])): ?><p style="margin:0; font-weight:bold; font-size: 0.9rem;">Mada: <?php echo $active_session['topic']; ?></p><?php endif; ?>
                        
                        <div class="qr-box">
                            <img id="dynamicQR" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $active_session['session_token']; ?>" class="qr-img" alt="QR Code">
                            <div class="timer-bar" id="timerBar"></div>
                        </div>
                        
                        <form action="../api/end_class.php" method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="schedule_id" value="<?php echo $active_session['id']; ?>">
                            <button type="submit" class="btn-primary" style="background: #c62828;">
                                <i class="fa-solid fa-stop"></i> MALIZA KIPINDI
                            </button>
                        </form>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            let scheduleId = <?php echo isset($active_session['id']) ? $active_session['id'] : 'null'; ?>;
                            if(scheduleId) {
                                let progressBar = document.getElementById('timerBar');
                                let width = 0;
                                setInterval(() => { width += (100/15); if(width > 100) width = 0; if(progressBar) progressBar.style.width = width + '%'; }, 1000);
                                setInterval(() => {
                                    fetch('../api/rotate_token.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'schedule_id=' + scheduleId })
                                    .then(response => response.json())
                                    .then(data => { if(data.status === 'success') { document.getElementById('dynamicQR').src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" + data.new_token; } });
                                }, 15000); 
                            }
                        });
                    </script>

                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <p class="text-muted">Hakuna kipindi kinachoendelea sasa.</p>
                        <button class="btn-primary" onclick="switchNav('schedule')" style="margin-top: 10px;">
                            <i class="fa-regular fa-calendar-plus"></i> Panga Ratiba Mpya
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>📅 Ratiba Zangu Zijazo</h3>
                <?php if ($my_schedules_result->num_rows > 0): ?>
                    <?php while($sch = $my_schedules_result->fetch_assoc()): ?>
                        <div class="schedule-item">
                            <div>
                                <strong><?php echo $sch['course_code']; ?></strong> - 
                                <span><?php echo $sch['subject_name']; ?></span><br>
                                <span style="font-size: 0.85rem; color: #555;">
                                    <?php echo date('d M', strtotime($sch['class_date'])); ?> | 
                                    <?php echo date('H:i', strtotime($sch['start_time'])); ?> - <?php echo date('H:i', strtotime($sch['end_time'])); ?>
                                </span>
                            </div>
                            <div>
                                <a href="../api/delete_schedule.php?id=<?php echo $sch['id']; ?>" onclick="return confirm('Una uhakika unataka kufuta?');" class="action-btn btn-delete">
                                    <i class="fa-solid fa-trash"></i> FUTA
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted" style="padding:10px;">Hakuna ratiba zijazo.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-attendance" class="tab-content" style="display: none;">
            <h3>📋 Mahudhurio (Saa 24)</h3>
            <div class="card" id="attendanceCard">
                <button class="btn-primary" onclick="downloadPDF()" style="background: #333; margin-bottom:10px; width:auto; padding: 5px 15px; font-size: 0.8rem;">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF
                </button>
                <div id="printArea" style="background: white; padding: 10px;">
                    <h4 style="text-align:center; margin-bottom:10px; color:#333;">Ripoti ya Mahudhurio</h4>
                    <?php if ($attend_result->num_rows > 0): ?>
                        <table class="attend-table">
                            <thead><tr><th>Muda</th><th>Jina</th><th>Somo</th><th>Kozi</th></tr></thead>
                            <tbody>
                                <?php while($row = $attend_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($row['scanned_at'])); ?></td>
                                        <td><?php echo $row['full_name']; ?></td>
                                        <td><?php echo $row['subject_name'] ? $row['subject_name'] : '-'; ?></td>
                                        <td><?php echo $row['course_code']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted" style="text-align:center; padding:20px;">Hakuna data.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="tab-schedule" class="tab-content" style="display: none;">
            <h3>📅 Panga Kipindi (Planning)</h3>
            <div class="card">
                <form action="../api/create_schedule.php" method="POST">
                    <p class="text-muted">Chagua Kozi na Somo unalotaka kufundisha.</p>
                    
                    <div class="input-group">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <select name="course_id" id="courseSelect" required onchange="filterSubjects()">
                            <option value="" disabled selected>Chagua Kozi (Darasa)</option>
                            <?php 
                            if ($courses_result->num_rows > 0) { 
                                while($c = $courses_result->fetch_assoc()) { 
                                    echo "<option value='".$c['id']."'>".$c['course_name']."</option>"; 
                                } 
                            } 
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <i class="fa-solid fa-book"></i>
                        <select name="subject_id" id="subjectSelect" required disabled>
                            <option value="" disabled selected>Kwanza Chagua Kozi ☝️</option>
                            <?php 
                            if ($subjects_result->num_rows > 0) { 
                                while($s = $subjects_result->fetch_assoc()) { 
                                    echo "<option value='".$s['id']."' data-course='".$s['course_id']."'>".$s['subject_name']." (".$s['subject_code'].")</option>"; 
                                } 
                            } 
                            ?>
                        </select>
                    </div>

                    <div class="input-group"><i class="fa-solid fa-heading"></i><input type="text" name="topic" placeholder="Mada ya Leo (Topic)" required></div>
                    <div class="input-group"><i class="fa-solid fa-location-dot"></i><input type="text" name="venue" placeholder="Mahali (Venue)" required></div>
                    <div class="input-group"><label style="font-size:0.8rem; font-weight:bold;">Tarehe:</label><input type="date" name="class_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div><label>Anza:</label><div class="input-group"><input type="time" name="start_time" required></div></div>
                        <div><label>Maliza:</label><div class="input-group"><input type="time" name="end_time" required></div></div>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-calendar-check"></i> Hifadhi Ratiba</button>
                </form>
            </div>
        </div>

        <div id="tab-requests" class="tab-content" style="display: none;">
            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                <button class="btn-primary" onclick="toggleRequestView('pending')" style="flex:1; background:#333;">Mpya (Pending)</button>
                <button class="btn-primary" onclick="toggleRequestView('history')" style="flex:1; background:#757575;">Historia</button>
            </div>

            <div id="req-pending">
                <h3>📩 Maombi Mapya (Vipindi Vyako)</h3>
                <?php if ($requests_result->num_rows > 0): ?>
                    <?php while($req = $requests_result->fetch_assoc()): ?>
                        <div class="req-card">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?php echo $req['full_name']; ?></strong>
                                <span style="background: #eee; padding: 2px 5px; border-radius: 4px; font-size: 0.7rem;"><?php echo $req['course_code']; ?></span>
                            </div>
                            
                            <p style="font-size: 0.85rem; margin: 5px 0; color: #333;">
                                <i class="fa-solid fa-book"></i> <?php echo $req['subject_name']; ?> 
                                <br>
                                <i class="fa-regular fa-clock"></i> <?php echo date('d M', strtotime($req['class_date'])); ?> @ <?php echo date('H:i', strtotime($req['start_time'])); ?>
                            </p>
                            
                            <p style="font-size: 0.9rem; color: #555; margin-bottom: 5px; font-style: italic;">"<?php echo $req['reason']; ?>"</p>
                            
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <form action="../api/process_leave.php" method="POST" style="flex:1;"><input type="hidden" name="request_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="action-btn btn-approve" style="width: 100%;">KUBALI</button></form>
                                <form action="../api/process_leave.php" method="POST" style="flex:1;"><input type="hidden" name="request_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="action" value="reject"><button type="submit" class="action-btn btn-reject" style="width: 100%;">KATAA</button></form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted" style="text-align: center; padding: 20px;">Hakuna maombi mapya kwa vipindi vyako.</p>
                <?php endif; ?>
            </div>

            <div id="req-history" style="display: none;">
                <h3>📂 Historia ya Maamuzi Yako</h3>
                <?php if ($history_result->num_rows > 0): ?>
                    <?php while($hist = $history_result->fetch_assoc()): ?>
                        <div class="history-card">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?php echo $hist['full_name']; ?></strong>
                                <?php if($hist['status']=='approved'): ?>
                                    <span class="status-badge status-approved">IMEKUBALIWA</span>
                                <?php else: ?>
                                    <span class="status-badge status-rejected">IMEKATALIWA</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo $hist['subject_name']; ?> | <?php echo date('d M', strtotime($hist['class_date'])); ?>
                            </small>
                            <div style="text-align: right; margin-top: 5px;">
                                <a href="../api/delete_request_history.php?id=<?php echo $hist['id']; ?>" class="btn-archive" onclick="return confirm('Futa historia hii?');"><i class="fa-solid fa-trash"></i> Futa</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted" style="text-align: center; padding: 20px;">Hakuna historia bado.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-notices" class="tab-content" style="display: none;">
            <h3>📢 Toa Tangazo</h3>
            <div class="card">
                <form action="../api/post_notice.php" method="POST">
                    <div class="input-group"><input type="text" name="title" placeholder="Kichwa" required></div>
                    <div class="input-group"><textarea name="content" rows="4" placeholder="Ujumbe..." required></textarea></div>
                    <div class="input-group"><select name="target"><option value="all">Wote</option><option value="students">Wanafunzi Tu</option></select></div>
                    <button type="submit" class="btn-primary" style="background: #e91e63;">POST TANGAZO</button>
                </form>
            </div>
        </div>

        <div id="tab-profile" class="tab-content" style="display: none;">
            <div class="card">
                <div class="input-group"><i class="fa-solid fa-user"></i><input type="text" value="<?php echo $teacher_name; ?>" readonly></div>
                <button class="btn-primary" style="background: #e53935;" onclick="window.location.href='../index.php'">Ondoka (Logout)</button>
            </div>
        </div>

    </div>

    <div class="bottom-nav">
        <button class="nav-item active" onclick="switchNav('home')"><i class="fa-solid fa-chart-pie"></i> Home</button>
        <button class="nav-item" onclick="switchNav('schedule')"><i class="fa-regular fa-calendar-plus"></i> Ratiba</button>
        <button class="nav-item" onclick="switchNav('attendance')"><i class="fa-solid fa-clipboard-user"></i> List</button>
        <button class="nav-item" onclick="switchNav('requests')"><i class="fa-solid fa-inbox"></i> Maombi</button>
        <button class="nav-item" onclick="switchNav('notices')"><i class="fa-solid fa-bullhorn"></i> Habari</button>
        <button class="nav-item" onclick="switchNav('profile')"><i class="fa-solid fa-user-tie"></i> Profile</button>
    </div>

    <script>
        function switchNav(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tabName).style.display = 'block';
            if (event && event.currentTarget) { event.currentTarget.classList.add('active'); }
        }

        // TOGGLE REQUEST VIEWS (Pending vs History)
        function toggleRequestView(view) {
            document.getElementById('req-pending').style.display = 'none';
            document.getElementById('req-history').style.display = 'none';
            if(view === 'pending') {
                document.getElementById('req-pending').style.display = 'block';
            } else {
                document.getElementById('req-history').style.display = 'block';
            }
        }

        function downloadPDF() {
            var element = document.getElementById('printArea');
            var opt = { margin: 0.5, filename: 'Attendance.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' } };
            html2pdf().set(opt).from(element).save();
        }

        function filterSubjects() {
            var courseId = document.getElementById("courseSelect").value;
            var subjectSelect = document.getElementById("subjectSelect");
            var options = subjectSelect.getElementsByTagName("option");
            subjectSelect.disabled = false;
            subjectSelect.value = "";
            var hasSubjects = false;
            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                if (option.getAttribute("data-course") == courseId) {
                    option.style.display = "block";
                    hasSubjects = true;
                } else {
                    if(option.value !== "") option.style.display = "none";
                }
            }
            if(!hasSubjects) {
                subjectSelect.options[0].text = "Hakuna Masomo kwa Kozi hii";
                subjectSelect.disabled = true;
            } else {
                subjectSelect.options[0].text = "Chagua Somo Sasa 👇";
            }
        }
    </script>
</body>
</html>
