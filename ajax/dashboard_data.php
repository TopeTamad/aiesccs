<?php
session_start();
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Require teacher login (keeps same behavior as dashboard)
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$teacher_id = $_SESSION['teacher_id'];

// Attendance overview (Sign In / Sign Out) for this teacher
$attendance_overview_query = $conn->prepare("
    SELECT 
        CASE 
            WHEN status = 'Present' THEN 'Sign In'
            WHEN status = 'Signed Out' THEN 'Sign Out'
            ELSE status
        END as status,
        COUNT(*) as count
    FROM attendance 
    WHERE DATE(scan_time) = ? 
    AND subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))
    AND status IN ('Present', 'Signed Out')
    GROUP BY 
        CASE 
            WHEN status = 'Present' THEN 'Sign In'
            WHEN status = 'Signed Out' THEN 'Sign Out'
            ELSE status
        END
");
$attendance_overview_query->bind_param('ss', $today, $teacher_id);
$attendance_overview_query->execute();
$attendance_overview = $attendance_overview_query->get_result();
$attendance_stats = array(
    'Sign In' => 0,
    'Sign Out' => 0
);
while ($row = $attendance_overview->fetch_assoc()) {
    $attendance_stats[$row['status']] = (int)$row['count'];
}

// Weekly trends (Mon-Fri)
$weekly_trends = array();
$days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
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

// Today's total attendance
$today_attendance = array_sum($attendance_stats);

// Present count per subject for this teacher (today)
$subject_present_labels = [];
$subject_present_counts = [];
$present_query = $conn->prepare(
    "SELECT s.subject_name, SUM(CASE WHEN DATE(a.scan_time)=? AND a.status='Present' THEN 1 ELSE 0 END) as present_count
     FROM subjects s
     LEFT JOIN attendance a ON a.subject_id = s.id
     WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)
     GROUP BY s.id
     ORDER BY present_count DESC"
);
$present_query->bind_param('ss', $today, $teacher_id);
$present_query->execute();
$present_res = $present_query->get_result();
while ($r = $present_res->fetch_assoc()) {
    $subject_present_labels[] = $r['subject_name'];
    $subject_present_counts[] = (int)$r['present_count'];
}

// Current time/date info
$current_day = date('D');
$current_time = date('g:i A');
$current_date = date('F j, Y');

// Latest scan (most recent attendance record) - include student info and subject
$latest_scan = null;
$latest_stmt = $conn->prepare("SELECT a.*, s.name AS student_name, s.student_id AS student_id_no, s.section, s.year_level, s.profile_pic AS student_pic, sub.subject_name\n    FROM attendance a\n    JOIN students s ON a.student_id = s.student_id\n    LEFT JOIN subjects sub ON a.subject_id = sub.id\n    WHERE a.subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))\n    ORDER BY a.scan_time DESC\n    LIMIT 1");
if ($latest_stmt) {
    $latest_stmt->bind_param('s', $teacher_id);
    $latest_stmt->execute();
    $res = $latest_stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $latest_scan = [
            'student_name' => $row['student_name'] ?? null,
            'student_id' => $row['student_id_no'] ?? null,
            'section' => $row['section'] ?? null,
            'year_level' => $row['year_level'] ?? null,
            'status' => $row['status'] ?? null,
            'subject_name' => $row['subject_name'] ?? null,
            'student_pic' => !empty($row['student_pic']) ? 'assets/img/' . $row['student_pic'] : 'assets/img/logo.png',
            'scan_time' => !empty($row['scan_time']) ? date('g:i:s A', strtotime($row['scan_time'])) : null,
            'raw_scan_time' => $row['scan_time'] ?? null
        ];
    }
    $latest_stmt->close();
}

// Build response
$response = [
    'attendance_stats' => $attendance_stats,
    'weekly_trends' => $weekly_trends,
    'today_attendance' => $today_attendance,
    'subject_present_labels' => $subject_present_labels,
    'subject_present_counts' => $subject_present_counts,
    'current_day' => $current_day,
    'current_time' => $current_time,
    'current_date' => $current_date,
    'latest_scan' => $latest_scan
];

header('Content-Type: application/json');
echo json_encode($response);
?>
<?php
session_start();
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Get attendance by year level
$year_level_query = "SELECT year_level, COUNT(DISTINCT a.student_id) as count 
                    FROM attendance a 
                    JOIN students s ON a.student_id = s.student_id 
                    WHERE DATE(scan_time) = '$today' 
                    GROUP BY year_level 
                    ORDER BY year_level";
$year_level_result = $conn->query($year_level_query);

$year_level_data = [
    'labels' => [],
    'values' => []
];

while ($row = $year_level_result->fetch_assoc()) {
    $year_level_data['labels'][] = "Year " . $row['year_level'];
    $year_level_data['values'][] = (int)$row['count'];
}

// Get attendance by year and section
$year_section_query = "SELECT CONCAT('Year ', year_level, ' - ', section) as year_section, 
                             COUNT(DISTINCT a.student_id) as count 
                      FROM attendance a 
                      JOIN students s ON a.student_id = s.student_id 
                      WHERE DATE(scan_time) = '$today' 
                      GROUP BY year_level, section 
                      ORDER BY year_level, section";
$year_section_result = $conn->query($year_section_query);

$year_section_data = [
    'labels' => [],
    'values' => []
];

while ($row = $year_section_result->fetch_assoc()) {
    $year_section_data['labels'][] = $row['year_section'];
    $year_section_data['values'][] = (int)$row['count'];
}

// Get attendance by subject for today
$subject_query = "SELECT s.subject_name, COUNT(DISTINCT a.student_id) as count 
                 FROM attendance a 
                 JOIN students st ON a.student_id = st.student_id 
                 JOIN subjects s ON st.year_level = s.year_level 
                 WHERE DATE(scan_time) = '$today' 
                 GROUP BY s.subject_id 
                 ORDER BY s.subject_name";
$subject_result = $conn->query($subject_query);

$subject_data = [
    'labels' => [],
    'values' => []
];

while ($row = $subject_result->fetch_assoc()) {
    $subject_data['labels'][] = $row['subject_name'];
    $subject_data['values'][] = (int)$row['count'];
}

// Return all data as JSON
$response = [
    'yearLevel' => $year_level_data,
    'yearSection' => $year_section_data,
    'subjects' => $subject_data
];

header('Content-Type: application/json');
echo json_encode($response);
session_start();
include '../includes/db.php';

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$teacher_id = $_SESSION['teacher_id'];
$today = date('Y-m-d');

// Get attendance overview
$attendance_overview_query = $conn->prepare("
    SELECT 
        CASE 
            WHEN status = 'Present' THEN 'Sign In'
            WHEN status = 'Signed Out' THEN 'Sign Out'
            ELSE status
        END as status,
        COUNT(*) as count
    FROM attendance 
    WHERE DATE(scan_time) = ? 
    AND subject_id IN (SELECT id FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?))
    AND status IN ('Present', 'Signed Out')
    GROUP BY 
        CASE 
            WHEN status = 'Present' THEN 'Sign In'
            WHEN status = 'Signed Out' THEN 'Sign Out'
            ELSE status
        END
");
$attendance_overview_query->bind_param('ss', $today, $teacher_id);
$attendance_overview_query->execute();
$attendance_overview = $attendance_overview_query->get_result();
$attendance_stats = array(
    'Sign In' => 0,
    'Sign Out' => 0
);
while ($row = $attendance_overview->fetch_assoc()) {
    $attendance_stats[$row['status']] = (int)$row['count'];
}

// Get weekly trends
$weekly_trends = array();
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri');

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

// Get today's total attendance
$today_attendance = array_sum($attendance_stats);

// Get present count per subject for this teacher (today)
$subject_present_labels = [];
$subject_present_counts = [];
$present_query = $conn->prepare("\n+    SELECT s.subject_name, \n+           SUM(CASE WHEN DATE(a.scan_time)=? AND a.status = 'Present' THEN 1 ELSE 0 END) as present_count\n+    FROM subjects s\n+    LEFT JOIN attendance a ON a.subject_id = s.id\n+    WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)\n+    GROUP BY s.id\n+    ORDER BY present_count DESC\n+");
$present_query->bind_param('ss', $today, $teacher_id);
$present_query->execute();
$present_res = $present_query->get_result();
while ($r = $present_res->fetch_assoc()) {
    $subject_present_labels[] = $r['subject_name'];
    $subject_present_counts[] = (int)$r['present_count'];
}

// Get current day information
$current_day = date('D');
$current_time = date('g:i A');
$current_date = date('F j, Y');

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'attendance_stats' => $attendance_stats,
    'weekly_trends' => $weekly_trends,
    'today_attendance' => $today_attendance,
    'subject_present_labels' => $subject_present_labels,
    'subject_present_counts' => $subject_present_counts,
    'current_day' => $current_day,
    'current_time' => $current_time,
    'current_date' => $current_date
]);
?>