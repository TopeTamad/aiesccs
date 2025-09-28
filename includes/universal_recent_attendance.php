<?php
// Ensure timezone is set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Manila');
}
session_start();
include __DIR__ . '/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : null;

if ($type === 'teacher' && !$teacher_id && isset($_SESSION['teacher_id'])) {
    $teacher_id = $_SESSION['teacher_id'];
}

if ($type === 'teacher' && !$teacher_id) {
    exit('Not authorized');
}

// We'll compute one row per student with time_in/time_out and pick the subject at those times (subject_in/subject_out)
$limit_int = max(1, intval($limit));

// Helper: build paired records by matching a scan to the next scan with the same subject for that student
function build_pairs_by_subject($rows, $limit) {
    $by_student = [];
    foreach ($rows as $r) {
        $sid = $r['student_id'];
        if (!isset($by_student[$sid])) $by_student[$sid] = [];
        $by_student[$sid][] = $r;
    }

    $pairs = [];
    foreach ($by_student as $sid => $list) {
        // chronological order
        usort($list, function($a, $b){ return strtotime($a['scan_time']) <=> strtotime($b['scan_time']); });
        $n = count($list);
        $used = array_fill(0, $n, false);
        for ($i = 0; $i < $n; $i++) {
            if ($used[$i]) continue;
            $in = $list[$i];
            $used[$i] = true;
            $out = null;
            // find the next unused scan with the same subject
            for ($j = $i + 1; $j < $n; $j++) {
                if ($used[$j]) continue;
                if ($list[$j]['subject_id'] == $in['subject_id']) {
                    $out = $list[$j];
                    $used[$j] = true;
                    break;
                }
            }
            $pairs[] = [
                'student_id' => $sid,
                'name' => $in['name'] ?? null,
                'section' => $in['section'] ?? null,
                'year_level' => $in['year_level'] ?? null,
                'pc_number' => $in['pc_number'] ?? null,
                'gender' => $in['gender'] ?? null,
                'time_in' => $in['scan_time'],
                'time_out' => $out ? $out['scan_time'] : null,
                'subject_in' => $in['subject_name'] ?? null,
                'subject_out' => $out ? $out['subject_name'] : null,
            ];
        }
    }
    // sort by time_in desc
    usort($pairs, function($a, $b){ return strtotime($b['time_in']) <=> strtotime($a['time_in']); });
    return array_slice($pairs, 0, $limit);
}

if ($type === 'teacher') {
    // resolve numeric teacher id (internal id)
    $tid_row = $conn->query("SELECT id FROM teachers WHERE teacher_id = '" . $conn->real_escape_string($teacher_id) . "'")->fetch_assoc();
    if (!$tid_row) exit('Not authorized');
    $tid = intval($tid_row['id']);

    // fetch recent scans today for this teacher's subjects
    $limit_int = max(1, intval($limit));
    $fetch_amount = $limit_int * 8;
    $fetch_sql = "SELECT a.*, s.name, s.section, s.year_level, s.pc_number, s.gender, sub.subject_name, sub.id AS subject_id FROM attendance a
        LEFT JOIN students s ON s.student_id = a.student_id
        LEFT JOIN subjects sub ON sub.id = a.subject_id
        WHERE sub.teacher_id = " . $tid . " AND DATE(a.scan_time)=CURDATE()
        ORDER BY a.scan_time DESC LIMIT " . intval($fetch_amount);
    $fres = $conn->query($fetch_sql);
    $rows = [];
    if ($fres) { while ($r = $fres->fetch_assoc()) $rows[] = $r; }
    $pairs = build_pairs_by_subject($rows, $limit_int);
    $result = new class($pairs) { private $data; private $idx=0; public $num_rows = 0; public function __construct($d){ $this->data=array_values($d); $this->num_rows = count($this->data);} public function fetch_assoc(){ if($this->idx<count($this->data)) return $this->data[$this->idx++]; return false;} public function num_rows(){ return $this->num_rows;} };

} elseif ($type === 'admin') {
    // admin => today's scans
    $limit_int = max(1, intval($limit));
    $fetch_amount = $limit_int * 8;
    $fetch_sql = "SELECT a.*, s.name, s.section, s.year_level, s.pc_number, s.gender, sub.subject_name, sub.id AS subject_id FROM attendance a
        LEFT JOIN students s ON s.student_id = a.student_id
        LEFT JOIN subjects sub ON sub.id = a.subject_id
        WHERE DATE(a.scan_time)=CURDATE()
        ORDER BY a.scan_time DESC LIMIT " . intval($fetch_amount);
    $fres = $conn->query($fetch_sql);
    $rows = [];
    if ($fres) { while ($r = $fres->fetch_assoc()) $rows[] = $r; }
    $pairs = build_pairs_by_subject($rows, $limit_int);
    $result = new class($pairs) { private $data; private $idx=0; public $num_rows = 0; public function __construct($d){ $this->data=array_values($d); $this->num_rows = count($this->data);} public function fetch_assoc(){ if($this->idx<count($this->data)) return $this->data[$this->idx++]; return false;} public function num_rows(){ return $this->num_rows;} };

} else {
    // all => recent scans across all subjects (no date limit)
    $limit_int = max(1, intval($limit));
    $fetch_amount = $limit_int * 8;
    $fetch_sql = "SELECT a.*, s.name, s.section, s.year_level, s.pc_number, s.gender, sub.subject_name, sub.id AS subject_id FROM attendance a
        LEFT JOIN students s ON s.student_id = a.student_id
        LEFT JOIN subjects sub ON sub.id = a.subject_id
        ORDER BY a.scan_time DESC LIMIT " . intval($fetch_amount);
    $fres = $conn->query($fetch_sql);
    $rows = [];
    if ($fres) { while ($r = $fres->fetch_assoc()) $rows[] = $r; }
    $pairs = build_pairs_by_subject($rows, $limit_int);
    $result = new class($pairs) { private $data; private $idx=0; public $num_rows = 0; public function __construct($d){ $this->data=array_values($d); $this->num_rows = count($this->data);} public function fetch_assoc(){ if($this->idx<count($this->data)) return $this->data[$this->idx++]; return false;} public function num_rows(){ return $this->num_rows;} };

}

if (!$result) {
    // fallback empty result set to avoid errors in rendering
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return false; }
        public function num_rows() { return $this->num_rows; }
    };
}
?>

<table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden shadow">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <?php endif; ?>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
            <?php endif; ?>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subjects</th>
            <?php endif; ?>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC Number</th>
            <?php endif; ?>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-blue-50 transition">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                <?= htmlspecialchars($row['student_id']) ?>
            </td>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?= htmlspecialchars($row['name'] ?? '-') ?>
                </td>
            <?php endif; ?>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <?= htmlspecialchars($row['section'] ?? '-') ?>
            </td>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?= isset($row['year_level']) ? htmlspecialchars($row['year_level']) : '-' ?>
                </td>
            <?php endif; ?>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?= htmlspecialchars($row['subject_out'] ?? $row['subject_in'] ?? '-') ?>
                </td>
            <?php endif; ?>
            <?php if ($type === 'admin' || $type === 'all'): ?>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?= htmlspecialchars($row['pc_number'] ?? '') ?>
                </td>
            <?php endif; ?>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <?= $row['time_in'] ? date('Y-m-d h:i:s A', strtotime($row['time_in'])) : '-' ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <?= $row['time_out'] ? date('Y-m-d h:i:s A', strtotime($row['time_out'])) : '-' ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php
// close any prepared statements if present (we used direct queries here)
?>