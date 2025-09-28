<?php
header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Manila');
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$response = [
    'realtime' => [],
    'by_year' => [],
    'by_subject' => []
];

// Realtime: count Present vs Signed Out for the given date
$stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE DATE(scan_time)=? GROUP BY status");
$stmt->bind_param('s', $date);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $response['realtime'][$r['status']] = (int)$r['cnt'];
}
$stmt->close();

// By year: stacked counts per status
$stmt = $conn->prepare("SELECT s.year_level AS year, a.status, COUNT(*) AS cnt FROM attendance a JOIN students s ON a.student_id = s.student_id WHERE DATE(a.scan_time)=? GROUP BY s.year_level, a.status ORDER BY s.year_level");
$stmt->bind_param('s', $date);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $y = $r['year'];
    if (!isset($response['by_year'][$y])) $response['by_year'][$y] = [];
    $response['by_year'][$y][$r['status']] = (int)$r['cnt'];
}
$stmt->close();

// By subject: top subjects by sign-ins (status = Present)
$stmt = $conn->prepare("SELECT sub.subject_name as subject, COUNT(*) as cnt FROM attendance a JOIN subjects sub ON a.subject_id = sub.id WHERE DATE(a.scan_time)=? AND a.status='Present' GROUP BY sub.subject_name ORDER BY cnt DESC LIMIT 10");
// If subjects table/columns differ, fallback to subject field in attendance
if (!$stmt) {
    $stmt = $conn->prepare("SELECT subject as subject, COUNT(*) as cnt FROM attendance WHERE DATE(scan_time)=? AND status='Present' GROUP BY subject ORDER BY cnt DESC LIMIT 10");
}
$stmt->bind_param('s', $date);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $response['by_subject'][] = ['subject' => $r['subject'], 'count' => (int)$r['cnt']];
}
$stmt->close();

echo json_encode($response);

?>
