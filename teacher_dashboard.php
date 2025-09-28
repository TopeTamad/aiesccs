<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Fetch teacher profile picture
$profile_pic = null;
$profile_stmt = $conn->prepare("SELECT profile_pic FROM teachers WHERE teacher_id = ?");
$profile_stmt->bind_param("s", $teacher_id);
$profile_stmt->execute();
$profile_stmt->bind_result($profile_pic);
$profile_stmt->fetch();
$profile_stmt->close();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $new_pic = uniqid('teacher_', true) . '.' . $ext;
    move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'assets/img/' . $new_pic);
    $update_pic = $conn->prepare("UPDATE teachers SET profile_pic=? WHERE teacher_id=?");
    $update_pic->bind_param("ss", $new_pic, $teacher_id);
    $update_pic->execute();
    $profile_pic = $new_pic;
}

// Get teacher's subjects
$subjects_query = $conn->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id = s.id) as total_students
    FROM subjects s 
    WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)
");
$subjects_query->bind_param("s", $teacher_id);
$subjects_query->execute();
$subjects = $subjects_query->get_result();

// Get today's attendance statistics
$today = date('Y-m-d');

$attendance_overview_query = $conn->prepare(
    "SELECT 
        CASE 
            WHEN status = 'Present' THEN 'Time In'
            WHEN status = 'Signed Out' THEN 'Time Out'
            ELSE status
        END as status,
        COUNT(*) as count
    FROM attendance 
    WHERE DATE(scan_time) = ? 
    AND subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))
    AND status IN ('Present', 'Signed Out')
    GROUP BY 
        CASE 
            WHEN status = 'Present' THEN 'Time In'
            WHEN status = 'Signed Out' THEN 'Time Out'
            ELSE status
        END"
);
$attendance_overview_query->bind_param('ss', $today, $teacher_id);
$attendance_overview_query->execute();
$attendance_overview = $attendance_overview_query->get_result();
$attendance_stats = array(
    'Time In' => 0,
    'Time Out' => 0
);
while ($row = $attendance_overview->fetch_assoc()) {
            $attendance_stats[$row['status']] = (int)$row['count'];
}

// Get attendance for this week
$weekly_trends = array();
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
$current_day = date('D'); // Get current day abbreviation

foreach ($days as $day) {
    $date = date('Y-m-d', strtotime($day . ' this week'));
    $trend_query = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as signed_in,
            SUM(CASE WHEN status = 'Signed Out' THEN 1 ELSE 0 END) as signed_out
        FROM attendance
        WHERE DATE(scan_time) = ?
        AND subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))
    ");
    $trend_query->bind_param('ss', $date, $teacher_id);
    $trend_query->execute();
    $result = $trend_query->get_result()->fetch_assoc();
    $weekly_trends[$day] = [
        'signed_in' => (int)($result['signed_in'] ?? 0),
        'signed_out' => (int)($result['signed_out'] ?? 0)
    ];
}

// Get subject distribution
$subject_distribution_query = $conn->prepare("
    SELECT 
        s.subject_name,
        COUNT(DISTINCT ss.student_id) as student_count
    FROM subjects s
    LEFT JOIN student_subjects ss ON s.id = ss.subject_id
    WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)
    GROUP BY s.id
    ORDER BY student_count DESC
    LIMIT 4
");
$subject_distribution_query->bind_param('s', $teacher_id);
$subject_distribution_query->execute();
$subject_distribution = $subject_distribution_query->get_result();

$subject_labels = [];
$subject_data = [];
while ($row = $subject_distribution->fetch_assoc()) {
    $subject_labels[] = $row['subject_name'];
    $subject_data[] = $row['student_count'];
}

// Get present count per subject for initial render (today)
$subject_present_labels = [];
$subject_present_counts = [];
$present_stmt = $conn->prepare("SELECT s.subject_name, SUM(CASE WHEN DATE(a.scan_time)=? AND a.status='Present' THEN 1 ELSE 0 END) as present_count FROM subjects s LEFT JOIN attendance a ON a.subject_id = s.id WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?) GROUP BY s.id ORDER BY present_count DESC");
$present_stmt->bind_param('ss', $today, $teacher_id);
$present_stmt->execute();
$present_res = $present_stmt->get_result();
while ($r = $present_res->fetch_assoc()) {
    $subject_present_labels[] = $r['subject_name'];
    $subject_present_counts[] = (int)$r['present_count'];
}
$present_stmt->close();

$today_attendance = array_sum($attendance_stats);

// Get total students
$total_students = $conn->query("
    SELECT COUNT(*) as count 
    FROM students 
    WHERE course = 'BSIS'
")->fetch_assoc()['count'];

// Get teacher's subject count
$subject_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM subjects 
    WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = '$teacher_id')
")->fetch_assoc()['count'];

// Handle schedule update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $subject_id = $_POST['subject_id'];
    $days = isset($_POST['schedule_days']) ? implode(',', $_POST['schedule_days']) : '';
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $update_stmt = $conn->prepare("UPDATE subjects SET schedule_days=?, start_time=?, end_time=? WHERE id=? AND teacher_id=(SELECT id FROM teachers WHERE teacher_id=?)");
    $update_stmt->bind_param("sssds", $days, $start_time, $end_time, $subject_id, $teacher_id);
    $update_stmt->execute();
    if ($update_stmt->error) {
        die("SQL Error: " . $update_stmt->error);
    }
    $update_stmt->close();
    header("Location: teacher_dashboard.php");
    exit();
}

/*
 * NEW: Fetch the latest attendance scan so variables used in the "Latest Attendance Scan" card
 * are defined and won't trigger "Undefined variable" warnings.
 */
$studentName = 'No recent scan';
$studentId = '';
$section = '';
$yearLevel = '';
$status = '';
$subjectName = '';
$profilePicPath = 'assets/img/logo.png';
$scanTimeDisplay = '--:--:--';

$latest_stmt = $conn->prepare("
    SELECT a.*, s.name AS student_name, s.section, s.year_level, s.profile_pic AS student_pic, sub.subject_name
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    LEFT JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))
    ORDER BY a.scan_time DESC
    LIMIT 1
");
if ($latest_stmt) {
    $latest_stmt->bind_param('s', $teacher_id);
    $latest_stmt->execute();
    $latest_res = $latest_stmt->get_result();
    if ($latest_res && $row = $latest_res->fetch_assoc()) {
        $studentName = $row['student_name'] ?? 'No recent scan';
        $studentId = $row['student_id'] ?? '';
        $section = $row['section'] ?? '';
        $yearLevel = $row['year_level'] ?? '';
        $status = $row['status'] ?? '';
        $subjectName = $row['subject_name'] ?? '';
        $student_pic = $row['student_pic'] ?? '';
        $profilePicPath = !empty($student_pic) ? 'assets/img/' . $student_pic : 'assets/img/logo.png';
        $scanTimeDisplay = !empty($row['scan_time']) ? date('g:i:s A', strtotime($row['scan_time'])) : '--:--:--';
    }
    $latest_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; }
        .sidebar-link.active, .sidebar-link:hover { background: linear-gradient(90deg, #4f8cff 0%, #a18fff 100%); color: #fff !important; }
        .sidebar-link i { min-width: 1.5rem; }
        .card { 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
        }
        .card:hover { 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-4px);
            border-color: #93c5fd;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-body { padding: 1.5rem; }
        .stat-card { background: white; border-radius: 16px; padding: 1.5rem; border: 2px solid #e5e7eb; transition: all 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .stat-card:hover { border-color: #667eea; transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        /* Latest Scan styling */
        .latest-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(239,246,255,0.9) 100%);
            border: 1px solid rgba(99,102,241,0.06);
        }
        .latest-card .student-name { color: #1e3a8a; font-weight: 700; }
        .latest-card .subject-label { color: #4f46e5; font-weight: 600; }
        .latest-card .scan-time { color: #0f172a; font-weight: 700; }
        .status-badge { padding: 0.45rem 0.9rem; border-radius: 9999px; color: #fff; font-weight: 700; display: inline-block; }
        .status-present { background: linear-gradient(90deg,#06b6d4 0%,#10b981 100%); }
        .status-signedout { background: linear-gradient(90deg,#fb7185 0%,#ef4444 100%); }
        .status-other { background: linear-gradient(90deg,#9ca3af 0%,#6b7280 100%); }
        @media (max-width: 900px) { .sidebar { left: -220px; } .sidebar.open { left: 0; } }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex">
    <!-- Shared sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-80 min-h-screen main-content">
        <header class="bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 shadow-xl border-b border-blue-200/30 sticky top-0 z-10 w-full backdrop-blur-sm" style="margin-left: -18rem; width: calc(100% + 18rem);">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-4 relative">
            <div class="w-full px-6 sm:px-8 lg:px-12 flex justify-center items-center py-4 relative">
                <h1 class="text-3xl font-bold text-white" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"> Welcome, <?= htmlspecialchars($teacher_name) ?> </h1>
                <div class="hidden sm:flex items-center gap-2 bg-white/90 text-blue-700 rounded-full px-4 py-1 absolute right-6 top-1/2 -translate-y-1/2 shadow">
                    <i class="fas fa-clock"></i>
                    <span id="top-clock" class="font-semibold">--:--:-- --</span>
                </div>
            </div>
        </header>

        <main class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column: Analytics -->
                <div class="lg:w-10/12">
            <script>
                (function() {
                    function updateClock() {
                        var el = document.getElementById('top-clock');
                        if (!el) return;
                        var now = new Date();
                        var hours = now.getHours();
                        var minutes = String(now.getMinutes()).padStart(2, '0');
                        var seconds = String(now.getSeconds()).padStart(2, '0');
                        var ampm = hours >= 12 ? 'PM' : 'AM';
                        hours = hours % 12; if (hours === 0) hours = 12;
                        var hh = String(hours).padStart(2, '0');
                        el.textContent = hh + ':' + minutes + ':' + seconds + ' ' + ampm;
                    }
                    updateClock();
                    setInterval(updateClock, 1000);
                })();
            </script>

            <!-- Analytics Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <!-- Attendance Overview Card -->
                <div class="lg:col-span-4 bg-white rounded-xl shadow-lg p-4 border border-gray-200">
                                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                        <i class="fas fa-chart-pie text-blue-500 text-lg"></i>
                        <span class="bg-gradient-to-r from-blue-500 to-indigo-600 bg-clip-text text-transparent">Attendance Overview</span>
                    </h3>
                                        <div class="h-[520px] flex items-center justify-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg">
                                            <canvas id="attendanceChart" height="420" style="max-height:420px; width:100%;"></canvas>
                                        </div>
                    
                </div>

                <!-- Present Per Subject Card -->
                <div class="lg:col-span-4 bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-pie-chart text-green-500"></i> Present Per Subject (Today)
                        </h3>
                        <div class="text-sm text-gray-600">
                            <div id="currentDate" class="font-medium"></div>
                        </div>
                    </div>
                    <div class="h-[520px] flex items-center justify-center">
                        <canvas id="trendsChart" height="420" style="max-height:420px; width:100%;"></canvas>
                    </div>
                </div>

                <!-- Latest Live Scan Card (replaced Subject Distribution) -->
                <div class="lg:col-span-4 bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-qrcode text-blue-500"></i>
                            Latest Live Scan
                        </h3>
                        <img src="assets/img/logo.png" alt="logo" class="w-12 h-12 object-contain">
                    </div>

                    <div class="w-full flex items-center justify-center">
                        <!-- Square / prominent card -->
                        <a href="latest_scans.php" class="w-full max-w-md block">
                        <div id="latest-scan-large" class="w-full max-w-md h-[520px] bg-gradient-to-br from-white to-blue-50 rounded-2xl border border-blue-50 shadow-md p-6 flex flex-col items-center justify-between transition-transform hover:scale-[1.01]">
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-36 h-36 rounded-full bg-white border overflow-hidden flex items-center justify-center shadow">
                                    <img id="latest-scan-pic-large" src="<?= htmlspecialchars($profilePicPath) ?>" alt="student" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='assets/img/logo.png'">
                                </div>

                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Student</div>
                                    <div id="latest-scan-name-large" class="font-bold text-gray-800 text-2xl"><?= htmlspecialchars($studentName) ?></div>
                                    <div id="latest-scan-meta-large" class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($studentId) ?> · <?= htmlspecialchars($section) ?> · <?= htmlspecialchars($yearLevel) ?></div>
                                </div>
                            </div>

                            <div class="w-full flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span id="latest-scan-status-large" class="status-badge px-4 py-2 rounded-full text-white text-sm font-semibold bg-gray-400"><?= htmlspecialchars($status) ?></span>
                                    <div class="text-sm text-gray-600">Subject</div>
                                    <div id="latest-scan-subject-large" class="font-medium text-gray-800"> <?= htmlspecialchars($subjectName) ?></div>
                                </div>

                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Time</div>
                                    <div id="latest-scan-time-large" class="font-semibold text-gray-800"><?= htmlspecialchars($scanTimeDisplay) ?></div>
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>
                </div>
            </div>

                    <!-- Stats Cards -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 justify-center items-stretch py-6 px-6 bg-gradient-to-br from-purple-50 to-blue-50 rounded-2xl shadow-inner">
                <div class="flex flex-col items-start justify-between bg-white rounded-2xl shadow-lg p-6 min-h-[160px] border border-blue-100 transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="mb-2 flex items-center justify-center w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg text-white text-xl shadow">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="font-semibold text-blue-700 mb-1">Total Students</div>
                    <div class="text-3xl font-bold text-gray-800 mt-auto"><?= $total_students ?></div>
                </div>
                <div class="flex flex-col items-start justify-between bg-white rounded-2xl shadow-lg p-6 min-h-[160px] border border-blue-100">
                    <div class="mb-2 flex items-center justify-center w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg text-white text-xl shadow">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="font-semibold text-blue-700 mb-1">Today's Attendance</div>
                    <div id="todayAttendance" class="text-3xl font-bold text-gray-800 mt-auto"><?= $today_attendance ?></div>
                </div>
                <div class="flex flex-col items-start justify-between bg-white rounded-2xl shadow-lg p-6 min-h-[160px] border border-blue-100">
                    <div class="mb-2 flex items-center justify-center w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg text-white text-xl shadow">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="font-semibold text-blue-700 mb-1">My Subjects</div>
                    <div class="text-3xl font-bold text-gray-800 mt-auto"><?= $subject_count ?></div>
                </div>
                <div class="flex flex-col items-start justify-between bg-white rounded-2xl shadow-lg p-6 min-h-[160px] border border-blue-100">
                    <div class="mb-2 flex items-center justify-center w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg text-white text-xl shadow">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="font-semibold text-blue-700 mb-1">Department</div>
                    <div class="text-xl font-bold text-gray-800 mt-auto"><?= htmlspecialchars($_SESSION['teacher_department'] ?? 'N/A') ?></div>
                </div>
            </div>

            <script>
                let attendanceChart, trendsChart, subjectChart;
                const POLL_INTERVAL_MS = 2000; // Poll every 2000ms (2 seconds). Change to 1000 for 1s if needed.

                // Common chart options
                const commonChartOptions = {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: { top: 10, right: 8, bottom: 10, left: 8 }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                align: 'center',
                                labels: {
                                    padding: 12,
                                    boxWidth: 16,
                                    boxHeight: 8,
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded',
                                    font: {
                                        size: 12,
                                        family: '"Inter", "Segoe UI", Arial, sans-serif',
                                        weight: 500
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1e40af',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyColor: '#334155',
                                bodyFont: {
                                    size: 13
                                },
                                borderColor: '#e2e8f0',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                usePointStyle: true
                            }
                        }
                };                // Attendance Overview Pie Chart
                attendanceChart = new Chart(document.getElementById('attendanceChart'), {
                    type: 'pie',
                    data: {
                        labels: ['Time In', 'Time Out'],
                        datasets: [{
                            data: [<?= $attendance_stats['Time In'] ?>, <?= $attendance_stats['Time Out'] ?>],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)', // Blue for Sign In
                                'rgba(99, 102, 241, 0.8)'  // Indigo for Sign Out
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: commonChartOptions
                });                                // Present-per-subject Pie Chart
                trendsChart = new Chart(document.getElementById('trendsChart'), {
                    type: 'pie',
                    data: {
                        labels: <?= json_encode($subject_present_labels) ?>,
                        datasets: [{
                            label: 'Present (Today)',
                            data: <?= json_encode($subject_present_counts) ?>,
                            backgroundColor: [
                                'rgba(99, 102, 241, 0.8)',
                                'rgba(168, 85, 247, 0.8)',
                                'rgba(236, 72, 153, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                // Subject Distribution Pie Chart
                (function(){
                    var subjectEl = document.getElementById('subjectChart');
                    if (!subjectEl) return; // canvas removed — don't create chart
                    subjectChart = new Chart(subjectEl, {
                        type: 'pie',
                        data: {
                            labels: <?= json_encode($subject_labels) ?>,
                            datasets: [{
                                data: <?= json_encode($subject_data) ?>,
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(99, 102, 241, 0.8)',
                                    'rgba(168, 85, 247, 0.8)',
                                    'rgba(236, 72, 153, 0.8)'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            },
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                })();                // Function to update charts with new data
                function updateCharts() {
                    fetch('ajax/dashboard_data.php')
                        .then(response => response.json())
                        .then(data => {
                            // Update attendance chart if initialized
                            if (attendanceChart && attendanceChart.data && attendanceChart.data.datasets && attendanceChart.data.datasets[0]) {
                                // support both new keys ('Time In'/'Time Out') and legacy keys ('Sign In'/'Sign Out')
                                const tIn = (data.attendance_stats && (data.attendance_stats['Time In'] ?? data.attendance_stats['Sign In'])) ?? 0;
                                const tOut = (data.attendance_stats && (data.attendance_stats['Time Out'] ?? data.attendance_stats['Sign Out'])) ?? 0;
                                attendanceChart.data.datasets[0].data = [tIn || 0, tOut || 0];
                                attendanceChart.update();
                            }

                            // Update subject-present doughnut if provided
                            if (trendsChart && data.subject_present_labels && data.subject_present_counts) {
                                trendsChart.data.labels = data.subject_present_labels;
                                if (trendsChart.data.datasets[0]) {
                                    trendsChart.data.datasets[0].data = data.subject_present_counts;
                                }
                                trendsChart.update();
                            }

                            // Update subject present chart if provided
                            if (subjectChart && data.subject_present_labels && data.subject_present_counts) {
                                subjectChart.data.labels = data.subject_present_labels;
                                if (subjectChart.data.datasets[0]) {
                                    subjectChart.data.datasets[0].data = data.subject_present_counts;
                                }
                                subjectChart.update();
                            }

                            // Update today's attendance count
                            var tEl = document.getElementById('todayAttendance');
                            if (tEl) tEl.textContent = data.today_attendance || 0;

                            // Update current date display
                            var cEl = document.getElementById('currentDate');
                            if (cEl) cEl.textContent = (data.current_date ? data.current_date + ' • ' : '') + (data.current_time || '');

                            // Update Latest Scan card if provided (both small and large IDs)
                            if (data.latest_scan) {
                                var ls = data.latest_scan;
                                var mappings = [
                                    {pic: 'latest-scan-pic', name: 'latest-scan-name', meta: 'latest-scan-meta', status: 'latest-scan-status', subject: 'latest-scan-subject', time: 'latest-scan-time'},
                                    {pic: 'latest-scan-pic-large', name: 'latest-scan-name-large', meta: 'latest-scan-meta-large', status: 'latest-scan-status-large', subject: 'latest-scan-subject-large', time: 'latest-scan-time-large'}
                                ];

                                mappings.forEach(function(map) {
                                    var pic = document.getElementById(map.pic);
                                    var nameEl = document.getElementById(map.name);
                                    var metaEl = document.getElementById(map.meta);
                                    var statusEl = document.getElementById(map.status);
                                    var subjectEl = document.getElementById(map.subject);
                                    var timeEl = document.getElementById(map.time);

                                    if (pic && ls.student_pic) pic.src = ls.student_pic;
                                    if (nameEl) nameEl.textContent = ls.student_name || 'No recent scan';
                                    if (metaEl) metaEl.textContent = (ls.student_id || '') + (ls.section ? ' · ' + ls.section : '') + (ls.year_level ? ' · ' + ls.year_level : '');
                                    if (statusEl) {
                                        statusEl.textContent = ls.status || '';
                                        // toggle status classes for consistent styling
                                        statusEl.classList.remove('status-present','status-signedout','status-other');
                                        if (!statusEl.classList.contains('status-badge')) statusEl.classList.add('status-badge');
                                        const st = (ls.status || '').toLowerCase();
                                        if (st.includes('present') || st.includes('sign in') || st.includes('time in')) {
                                            statusEl.classList.add('status-present');
                                        } else if (st.includes('signed out') || st.includes('sign out') || st.includes('time out')) {
                                            statusEl.classList.add('status-signedout');
                                        } else {
                                            statusEl.classList.add('status-other');
                                        }
                                    }
                                    if (subjectEl) subjectEl.textContent = ls.subject_name || '';
                                    if (timeEl) timeEl.textContent = ls.scan_time || '';
                                });
                            }
                        })
                        .catch(error => console.error('Error updating charts:', error));
                }

                // Initial update and auto-refresh using configurable interval
                updateCharts();
                setInterval(updateCharts, POLL_INTERVAL_MS);
            </script>

                </div>
                
                <!-- Right Column: My Subjects -->
                <div class="lg:w-3/12 lg:sticky lg:top-20 lg:h-[calc(100vh-5rem)] lg:overflow-y-auto">
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300 transition-all duration-300">
                        <h2 class="text-2xl font-bold mb-8 flex items-center gap-3">
                            <i class="fas fa-book-open text-blue-500 text-2xl"></i>
                            <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">My Subjects</span>
                        </h2>
                        <?php if ($subjects->num_rows > 0): ?>
                            <div class="flex flex-col gap-4">
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <!-- START of Subject Card -->
                            <div class="relative bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-md border border-blue-200 p-6 hover:shadow-xl hover:border-blue-400 transition-all duration-300 transform hover:-translate-y-1">
                                <div class="flex flex-col space-y-4">
                                <div class="flex flex-col gap-3 mb-2">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-xl font-bold text-gray-800">
                                            <?= htmlspecialchars($subject['subject_name']) ?>
                                        </h3>
                                        <span class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-bold px-4 py-1.5 rounded-full shadow-md">
                                            <?= htmlspecialchars($subject['subject_code']) ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 bg-blue-50/50 px-4 py-2 rounded-lg border border-blue-100">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-users text-blue-500"></i>
                                            <span class="text-blue-600 font-medium">Students:</span>
                                            <span class="font-bold text-gray-800"><?= $subject['total_students'] ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="fas fa-users text-blue-500"></i>
                                        <span class="font-medium">Students:</span>
                                        <span class="font-bold text-gray-800"><?= $subject['total_students'] ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="fas fa-clock text-blue-500"></i>
                                        <?php if ($subject['schedule_days'] && $subject['start_time'] && $subject['end_time']): ?>
                                            <span class="font-medium">
                                                <?= htmlspecialchars($subject['schedule_days']) ?>,
                                                <?= htmlspecialchars(date('g:i a', strtotime($subject['start_time']))) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-red-500 font-medium">Schedule not set</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <form method="POST" class="space-y-3">
                                        <input type="hidden" name="update_schedule" value="1">
                                        <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                        <div class="flex flex-wrap gap-1">
                                            <?php 
                                            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                                            $selected_days = $subject['schedule_days'] ? explode(',', $subject['schedule_days']) : [];
                                            foreach ($days as $day): ?>
                                                <label class="cursor-pointer">
                                                    <input type="checkbox" name="schedule_days[]" value="<?= $day ?>" <?= in_array($day, $selected_days) ? 'checked' : '' ?> class="hidden peer">
                                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full border peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500 transition-all">
                                                        <?= $day ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="flex items-center gap-3 text-sm bg-blue-50 p-4 rounded-lg border border-blue-200">
                                            <div class="flex-1">
                                                <label class="block text-blue-600 font-medium mb-1">Start</label>
                                                <input type="time" name="start_time" value="<?= $subject['start_time'] ?? '' ?>" class="w-full border-2 border-blue-200 rounded-lg px-3 py-2 focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition-all">
                                            </div>
                                            <div class="text-blue-400 font-medium pt-6">to</div>
                                            <div class="flex-1">
                                                <label class="block text-blue-600 font-medium mb-1">End</label>
                                                <input type="time" name="end_time" value="<?= $subject['end_time'] ?? '' ?>" class="w-full border-2 border-blue-200 rounded-lg px-3 py-2 focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition-all">
                                            </div>
                                        </div>

                                <!-- Set Schedule -->
                                <form method="POST" class="mt-2 mb-4 space-y-3">
                                    <input type="hidden" name="update_schedule" value="1">
                                    <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">

                                    <!-- Days -->
                                    <div>
                                        <label class="font-semibold text-sm block mb-2">Set Schedule:</label>
                                        <div class="flex flex-wrap gap-2">
                                            <?php 
                                            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                                            $selected_days = $subject['schedule_days'] ? explode(',', $subject['schedule_days']) : [];
                                            foreach ($days as $day): ?>
                                                <label class="cursor-pointer">
                                                    <input type="checkbox" name="schedule_days[]" value="<?= $day ?>" <?= in_array($day, $selected_days) ? 'checked' : '' ?> class="hidden peer">
                                                    <span class="px-3 py-1 rounded-full border border-gray-300 text-xs font-medium peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500 transition">
                                                        <?= $day ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Time Inputs -->
                                    <div class="flex items-center gap-3">
                                        <label class="text-sm font-medium">Start:</label>
                                        <input type="time" name="start_time" value="<?= $subject['start_time'] ?? '' ?>" class="border rounded-lg px-2 py-1 text-sm">
                                        <label class="text-sm font-medium">End:</label>
                                        <input type="time" name="end_time" value="<?= $subject['end_time'] ?? '' ?>" class="border rounded-lg px-2 py-1 text-sm">
                                    </div>

                                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-2 rounded-lg font-bold shadow hover:opacity-90 transition">
                                        Save Schedule
                                    </button>
                                </form>

                                        <div class="flex gap-3 pt-4">
                                            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold hover:opacity-90 transition-all shadow-md hover:shadow-lg">
                                                Save Schedule
                                            </button>
                                            <a href="teacher_students.php?subject=<?= $subject['id'] ?>" class="flex-1 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold text-center hover:opacity-90 transition-all shadow-md hover:shadow-lg">
                                                View Class
                                            </a>
                                            <a href="teacher_attendance.php?subject=<?= $subject['id'] ?>" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold text-center hover:opacity-90 transition-all shadow-md hover:shadow-lg">
                                                Reports
                                            </a>
                                        </div>
                                    </form>
                                </div>
                                </div>
                            </div>
                            <!-- END of Subject Card -->
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No subjects assigned yet.</p>
                        <p class="text-sm text-gray-500">Contact the administrator to assign subjects.</p>
                    </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
           
</body>
</html>
