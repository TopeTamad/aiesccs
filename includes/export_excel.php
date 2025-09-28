<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../includes/db.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

 $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
 $gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

// Build query based on filters
// Build grouped query: one row per student for the requested date (time_in/time_out)
// Use MIN(scan_time) as time_in and MAX(scan_time) as time_out. Choose subject_out if available else subject_in.
if (isset($_GET['show_all']) && $_GET['show_all'] == '1') {
    $query = "SELECT g.student_id, s.name, s.section, s.year_level, s.gender, s.pc_number, "
           . "MIN(a.scan_time) AS time_in, MAX(a.scan_time) AS time_out, "
           . "(SELECT sub.subject_name FROM attendance a2 LEFT JOIN subjects sub ON a2.subject_id = sub.id WHERE a2.student_id = g.student_id AND a2.scan_time = (SELECT MAX(scan_time) FROM attendance a3 WHERE a3.student_id = g.student_id) LIMIT 1) AS subject_latest "
           . "FROM attendance a JOIN students s ON a.student_id = s.student_id JOIN (SELECT student_id FROM attendance GROUP BY student_id) g ON g.student_id = a.student_id";
    if ($gender_filter) {
        $query .= " WHERE s.gender = '" . $conn->real_escape_string($gender_filter) . "'";
    }
    $query .= " GROUP BY g.student_id ORDER BY time_out DESC";
} else {
    $query = "SELECT g.student_id, s.name, s.section, s.year_level, s.gender, s.pc_number, "
           . "MIN(a.scan_time) AS time_in, MAX(a.scan_time) AS time_out, "
           . "(SELECT sub.subject_name FROM attendance a2 LEFT JOIN subjects sub ON a2.subject_id = sub.id WHERE a2.student_id = g.student_id AND DATE(a2.scan_time) = '" . $conn->real_escape_string($date) . "' AND a2.scan_time = (SELECT MAX(scan_time) FROM attendance a3 WHERE a3.student_id = g.student_id AND DATE(a3.scan_time) = '" . $conn->real_escape_string($date) . "') LIMIT 1) AS subject_latest "
           . "FROM attendance a JOIN students s ON a.student_id = s.student_id JOIN (SELECT student_id FROM attendance WHERE DATE(scan_time) = '" . $conn->real_escape_string($date) . "' GROUP BY student_id) g ON g.student_id = a.student_id WHERE DATE(a.scan_time)='" . $conn->real_escape_string($date) . "'";
    if ($gender_filter) {
        $query .= " AND s.gender = '" . $conn->real_escape_string($gender_filter) . "'";
    }
    $query .= " GROUP BY g.student_id ORDER BY time_out DESC";
}

$result = $conn->query($query);

// Create Excel content
echo '<table border="1">';
echo '<tr style="background-color: #6366f1; color: white; font-weight: bold;">';
echo '<td>Student ID</td>';
echo '<td>Name</td>';
echo '<td>Section</td>';
echo '<td>Year</td>';
echo '<td>Gender</td>';
echo '<td>PC Number</td>';
echo '<td>Subjects</td>';
echo '<td>Time In</td>';
echo '<td>Time Out</td>';
echo '</tr>';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['section']) . '</td>';
        echo '<td>' . htmlspecialchars($row['year_level']) . '</td>';
        echo '<td>' . htmlspecialchars($row['gender'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['pc_number'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['subject_latest'] ?? '-') . '</td>';
        echo '<td>' . ($row['time_in'] ? date('M j, Y g:i A', strtotime($row['time_in'])) : '-') . '</td>';
        echo '<td>' . ($row['time_out'] ? date('M j, Y g:i A', strtotime($row['time_out'])) : '-') . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="9" style="text-align: center;">No attendance records found.</td></tr>';
}

echo '</table>';
?>




