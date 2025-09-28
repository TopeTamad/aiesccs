<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../includes/db.php';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>
<table>
    <thead>
        <tr>
            <th>Student ID</th>
            <th>Section</th>
            <th>Year</th>
            <th>Subject</th>
            <th>PC Number</th>
            <th>Status</th>
            <th>Scan Time</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $recent = $conn->query("SELECT a.student_id, s.section, s.year_level, sub.subject_name, a.status, a.scan_time, s.pc_number FROM attendance a JOIN students s ON a.student_id = s.student_id LEFT JOIN subjects sub ON a.subject_id = sub.id WHERE DATE(a.scan_time)='$date' ORDER BY a.scan_time DESC LIMIT 5");
        while ($row = $recent->fetch_assoc()):
        ?>
            <tr>
                <td><?= htmlspecialchars($row['student_id']) ?></td>
                <td><?= htmlspecialchars($row['section']) ?></td>
                <td><?= htmlspecialchars($row['year_level']) ?></td>
                <td><?= htmlspecialchars($row['subject_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['pc_number'] ?? '') ?></td>
                <td>
                    <?php if ($row['status'] === 'Present'): ?>
                        <span style="color:#00FF00; font-weight:700; font-size:1.05rem;">Present</span>
                    <?php else: ?>
                        <span style="color:#FF0000; font-weight:700; font-size:1.05rem;">Signed Out</span>
                    <?php endif; ?>
                </td>
                <td><?= date('Y-m-d h:i:s A', strtotime($row['scan_time'])) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table> 