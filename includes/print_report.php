<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../includes/db.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

// Build grouped query: one row per student for the requested date (time_in/time_out)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Print</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        
        .header p {
            color: #666;
            margin: 5px 0;
        }
        
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        
        .filters span {
            font-weight: bold;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #333;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .status-present {
            color: #00FF00;
            font-weight: bold;
        }
        
        .status-signed-out {
            color: #FF0000;
            font-weight: bold;
        }
        
        .print-btn {
            background: #1976d2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .print-btn:hover {
            background: #1565c0;
        }
        
        .back-btn {
            background: #666;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
        <a href="javascript:history.back()" class="back-btn">← Back</a>
    </div>
    
    <div class="header">
        <h1>Attendance Report</h1>
        <p>Generated on: <?= date('F j, Y \a\t g:i A') ?></p>
        <p>School: BSIS Attendance System</p>
    </div>
    
    <div class="filters">
        <p><span>Date:</span> <?= isset($_GET['show_all']) && $_GET['show_all'] == '1' ? 'All Records' : date('F j, Y', strtotime($date)) ?></p>
        <p><span>Gender Filter:</span> <?= $gender_filter ? $gender_filter : 'All' ?></p>
        <p><span>Total Records:</span> <?= $result ? $result->num_rows : 0 ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Section</th>
                <th>Year</th>
                <th>Gender</th>
                <th>PC Number</th>
                <th>Subjects</th>
                <th>Time In</th>
                <th>Time Out</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['section']) ?></td>
                        <td><?= htmlspecialchars($row['year_level']) ?></td>
                        <td><?= htmlspecialchars($row['gender'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['pc_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['subject_latest'] ?? '-') ?></td>
                        <td><?= $row['time_in'] ? date('M j, Y g:i A', strtotime($row['time_in'])) : '-' ?></td>
                        <td><?= $row['time_out'] ? date('M j, Y g:i A', strtotime($row['time_out'])) : '-' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">No attendance records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>




