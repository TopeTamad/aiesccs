<?php
// Feed for recent student attendance scans (returns JSON)
// Returns: id, student_id, name, status, scan_time, subject_name, photo, course, year_level

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// Include DB
$dbPath = __DIR__ . '/../includes/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => 'DB file missing']);
    exit();
}
require_once $dbPath;

// Simple auth: require login
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Optional role check
$allowed_roles = ['student', 'teacher', 'admin'];
if (isset($_SESSION['user']['role']) && !in_array($_SESSION['user']['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Use the actual column name used in your project: profile_pic
$sql = "SELECT a.id, a.student_id, COALESCE(s.name, '') AS name, a.status, a.scan_time, a.subject_name,
               COALESCE(s.profile_pic, '') AS profile_pic, COALESCE(s.course, '') AS course, COALESCE(s.year_level, '') AS year_level
        FROM attendance a
        LEFT JOIN students s ON a.student_id = s.student_id
        ORDER BY a.scan_time DESC
        LIMIT 50";

try {
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception($conn->error);
    }

    $rows = [];
    while ($r = $result->fetch_assoc()) {
        // Build safe relative photo path (assets/img/<filename>) if profile_pic present
        $photo = '';
        if (!empty($r['profile_pic'])) {
            $photoFilename = basename($r['profile_pic']);
            // Ensure file exists before returning path (optional; avoids broken URLs)
            $candidate = __DIR__ . '/../assets/img/' . $photoFilename;
            if (file_exists($candidate)) {
                $photo = 'assets/img/' . $photoFilename;
            } else {
                // still return constructed path (in case files are stored differently)
                $photo = 'assets/img/' . $photoFilename;
            }
        }

        $rows[] = [
            'id' => $r['id'],
            'student_id' => $r['student_id'],
            'name' => $r['name'],
            'status' => $r['status'],
            'scan_time' => $r['scan_time'],
            'subject_name' => $r['subject_name'],
            'photo' => $photo,
            'course' => $r['course'],
            'year_level' => $r['year_level']
        ];
    }

    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
?>
