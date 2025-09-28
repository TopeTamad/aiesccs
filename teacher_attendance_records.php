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

// Load teacher's subjects
$subjects_query = $conn->prepare("SELECT s.* FROM subjects s WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)");
$subjects_query->bind_param("s", $teacher_id);
$subjects_query->execute();
$subjects = $subjects_query->get_result();

$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$students = [];
$error = null;

// If requested, export CSV for the current subject+date
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $subject_filter) {
    // We'll build the same data used for the page below; reuse logic by setting a flag
    $do_csv = true;
} else {
    $do_csv = false;
}

// Validate subject belongs to teacher and gather attendance
if ($subject_filter) {
    // Fetch subject and ensure it's assigned to this teacher
    $sub_stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ? AND teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)");
    $sub_stmt->bind_param("is", $subject_filter, $teacher_id);
    $sub_stmt->execute();
    $sub_res = $sub_stmt->get_result();
    if ($sub_res->num_rows === 0) {
        $error = 'Selected subject not found or not assigned to you.';
    } else {
        $subject_row = $sub_res->fetch_assoc();

        // Get students assigned to this subject
        $stmt = $conn->prepare(
            "SELECT st.* FROM students st JOIN student_subjects ss ON st.student_id = ss.student_id WHERE ss.subject_id = ? AND st.course = 'BSIS' ORDER BY st.year_level, st.section, st.name"
        );
        $stmt->bind_param("i", $subject_filter);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[$row['student_id']] = $row;
        }

        // Collect attendance dates for this subject (used to highlight calendar days)
        $attendance_dates = [];
        $ad_stmt = $conn->prepare("SELECT DISTINCT DATE(scan_time) AS d FROM attendance WHERE subject_id = ? ORDER BY d DESC");
        $ad_stmt->bind_param("i", $subject_filter);
        $ad_stmt->execute();
        $ad_res = $ad_stmt->get_result();
        while ($ar = $ad_res->fetch_assoc()) {
            $attendance_dates[] = $ar['d'];
        }

            // Get first and second scan times per student for that subject+date
        if (!empty($students)) {
            $placeholders = implode(',', array_fill(0, 1, '?'));
            // First scan per student
            $att_stmt = $conn->prepare(
                "SELECT student_id, MIN(scan_time) AS first_time FROM attendance WHERE subject_id = ? AND DATE(scan_time) = ? GROUP BY student_id"
            );
            $att_stmt->bind_param("is", $subject_filter, $date_filter);
            $att_stmt->execute();
            $att_res = $att_stmt->get_result();
            $first_times = [];
            while ($a = $att_res->fetch_assoc()) {
                $first_times[$a['student_id']] = $a['first_time'];
            }

            // Second scan per student (the next scan after the first_time)
            $second_times = [];
            if (!empty($first_times)) {
                // We'll fetch the second earliest scan_time per student by selecting scan_time > first_time and taking MIN
                $placeholders = implode(',', array_fill(0, count($first_times), '?'));
                // But prepared statements with dynamic IN lists are inconvenient; instead run a single query per student (safe since class sizes are small), or build a single query using the subject+date filter grouping by student and using ROW_NUMBER if supported. Simpler approach: query all scans for the date+subject and compute second per student in PHP.
                $all_stmt = $conn->prepare("SELECT student_id, scan_time FROM attendance WHERE subject_id = ? AND DATE(scan_time) = ? ORDER BY student_id, scan_time ASC");
                $all_stmt->bind_param('is', $subject_filter, $date_filter);
                $all_stmt->execute();
                $all_res = $all_stmt->get_result();
                $scans_by_student = [];
                while ($rowScan = $all_res->fetch_assoc()) {
                    $sid = $rowScan['student_id'];
                    if (!isset($scans_by_student[$sid])) $scans_by_student[$sid] = [];
                    $scans_by_student[$sid][] = $rowScan['scan_time'];
                }
                // Decide Time Out as the last scan of the day for that student.
                // Require a small minimum gap between Time In and Time Out to avoid counting near-duplicate scans
                $min_gap_seconds = 5 * 60; // 5 minutes
                foreach ($scans_by_student as $sid => $times) {
                    if (count($times) >= 2) {
                        $first_scan = $times[0];
                        $last_scan = $times[count($times) - 1];

                        // Prefer the already computed first_time if present (MIN), otherwise use the first element
                        $reference_first = isset($first_times[$sid]) ? $first_times[$sid] : $first_scan;

                        $gap = strtotime($last_scan) - strtotime($reference_first);
                        if ($gap >= $min_gap_seconds && strtotime($last_scan) > strtotime($reference_first)) {
                            $second_times[$sid] = $last_scan;
                        } else {
                            // Last scan too close to first scan - treat as no Time Out
                            $second_times[$sid] = null;
                        }
                    } else {
                        $second_times[$sid] = null;
                    }
                }
            }

            // Determine present/late/absent based on subject start_time
            $totals = ['total' => count($students), 'present' => 0, 'late' => 0, 'absent' => 0];
            $subject_start_time = $subject_row['start_time']; // e.g. '22:50:00'

            foreach ($students as $sid => $s) {
                    if (isset($first_times[$sid]) && $first_times[$sid]) {
                    $first = $first_times[$sid];
                    $second = $second_times[$sid] ?? null;
                    // Compute thresholds based on subject start_time
                    $start_dt = strtotime($date_filter . ' ' . $subject_start_time);
                    $present_deadline = $start_dt + (15 * 60); // within 15 minutes -> Present
                    $late_deadline = $start_dt + (30 * 60); // within 30 minutes -> Late

                    $first_ts = strtotime($first);

                    if ($first_ts <= $present_deadline) {
                        $status = 'Present';
                    } elseif ($first_ts <= $late_deadline) {
                        $status = 'Late';
                    } else {
                        $status = 'Absent';
                    }

                    $students[$sid]['first_time'] = $first;
                    $students[$sid]['second_time'] = $second;
                } else {
                    $status = 'Absent';
                    $students[$sid]['first_time'] = null;
                    $students[$sid]['second_time'] = null;
                }
                $students[$sid]['computed_status'] = $status;
                if ($status === 'Present') $totals['present']++;
                elseif ($status === 'Late') $totals['late']++;
                else $totals['absent']++;
            }
        } else {
            $totals = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
        }
    }
} else {
    $totals = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
}

// After computing statuses, build gender groups: Male, Female, Other
$gender_groups = ['Male' => [], 'Female' => [], 'Other' => []];
foreach ($students as $sid => $s) {
    $g = isset($s['gender']) ? trim($s['gender']) : '';
    $key = 'Other';
    if (strtolower($g) === 'male' || strtolower($g) === 'm') $key = 'Male';
    elseif (strtolower($g) === 'female' || strtolower($g) === 'f') $key = 'Female';
    $gender_groups[$key][$sid] = $s;
}

// If CSV export was requested, stream the CSV and exit
if ($do_csv) {
    // Prepare CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_' . $subject_filter . '_' . $date_filter . '.csv');

    $output = fopen('php://output', 'w');
    // Column headers
    fputcsv($output, ['Student ID', 'Name', 'Section', 'Year', 'Status', 'Time In', 'Time Out']);

    // Write rows in the same order as on page
    foreach ($students as $s) {
        fputcsv($output, [
            $s['student_id'],
            $s['name'],
            $s['section'],
            $s['year_level'],
            $s['computed_status'] ?? 'Absent',
            $s['first_time'] ?? '',
            $s['second_time'] ?? ''
        ]);
    }

    fclose($output);
    exit();
}

// Prepare display variables for the UI (subject name, code, start time, deadlines)
$display_subject_name = '';
$display_subject_code = '';
$display_start_time = '';
$present_deadline_str = '';
$late_deadline_str = '';
if ($subject_filter && isset($subject_row)) {
    $display_subject_name = $subject_row['subject_name'];
    $display_subject_code = $subject_row['subject_code'];
    $display_start_time = $subject_row['start_time'];
    $tmp_start_dt = strtotime($date_filter . ' ' . $display_start_time);
    if ($tmp_start_dt) {
        // Display in 12-hour format with AM/PM
        $display_start_time = date('g:i A', $tmp_start_dt);
        $present_deadline_str = date('g:i A', $tmp_start_dt + 15 * 60);
        $late_deadline_str = date('g:i A', $tmp_start_dt + 30 * 60);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - Teacher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; } .sidebar-link.active, .sidebar-link:hover { background: linear-gradient(90deg, #4f8cff 0%, #a18fff 100%); color: #fff !important; } </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex">
    <!-- Shared sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 ml-80 min-h-screen main-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-full bg-blue-50 border border-blue-100 p-3">
                            <i class="fas fa-clipboard-list text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-800">Class Record — <?= htmlspecialchars($display_subject_name ?: 'Attendance Records') ?></h1>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($display_subject_code) ?> • <?= htmlspecialchars($display_start_time) ?> • <?= htmlspecialchars($date_filter) ?></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-blue-600"><?= $totals['total'] ?></p>
                    </div>
                </div>
                <?php if ($subject_filter): ?>
                    <div class="mt-4 text-sm text-gray-600">Deadlines: Present &lt;= <?= $present_deadline_str ?: '-' ?>, Late &lt;= <?= $late_deadline_str ?: '-' ?>, after that Absent</div>
                <?php endif; ?>
            </div>

            <!-- Realtime Recent Attendance removed (dashboard provides this) -->

                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                <style>
                    /* Print styles: hide sidebar and controls */
                    @media print {
                        .sidebar-link, .main-controls, .no-print { display: none !important; }
                        .main-content { margin-left: 0 !important; }
                        body { background: #fff !important; }
                    }
                        /* Table polish */
                        .class-table thead th { position: sticky; top: 0; background: #fff; z-index: 10; }
                        .badge-present { background: #ECFDF5; color: #065F46; padding: 4px 8px; border-radius: 6px; font-weight:600; }
                        .badge-late { background: #FFF7ED; color: #92400E; padding: 4px 8px; border-radius: 6px; font-weight:600; }
                        .badge-absent { background: #FEF2F2; color: #991B1B; padding: 4px 8px; border-radius: 6px; font-weight:600; }
                </style>

                <form method="get" class="flex flex-wrap gap-4 items-end main-controls">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Subject</label>
                        <select name="subject" class="block mt-1 px-3 py-2 border rounded">
                            <option value="">-- Select subject --</option>
                            <?php while ($sub = $subjects->fetch_assoc()): ?>
                                <option value="<?= $sub['id'] ?>" <?= $subject_filter == $sub['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Date</label>
                        <!-- Use a text input so Flatpickr can attach when a subject is selected -->
                        <input id="datePicker" type="text" name="date" value="<?= htmlspecialchars($date_filter) ?>" class="block mt-1 px-3 py-2 border rounded" autocomplete="off" />
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Show</button>
                        <?php if ($subject_filter): ?>
                            <a href="?subject=<?= $subject_filter ?>&date=<?= htmlspecialchars($date_filter) ?>&export=csv" class="ml-2 inline-block px-4 py-2 bg-green-600 text-white rounded">Export CSV</a>
                            <button type="button" onclick="window.print();" class="ml-2 inline-block px-4 py-2 bg-gray-600 text-white rounded no-print">Print</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($subject_filter): ?>
                <!-- Flatpickr assets and highlighting only when a subject is selected -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                <style>
                    .flatpickr-day.has-attendance { background:#D1FAE5; color:#065F46 !important; border-radius:6px; }
                </style>
                <script>
                    (function(){
                        var attended = <?= json_encode(array_values($attendance_dates)) ?> || [];
                        // Debug - remove or comment out in production
                        console.log('attendance dates for subject <?= $subject_filter ?>:', attended);

                        function markDay(dateObj, dayElem){
                            if (!dayElem) return;
                            var y = dateObj.getFullYear();
                            var m = (dateObj.getMonth()+1).toString().padStart(2,'0');
                            var d = dateObj.getDate().toString().padStart(2,'0');
                            var iso = y+'-'+m+'-'+d;
                            if (attended.indexOf(iso) !== -1) {
                                dayElem.classList.add('has-attendance');
                            }
                        }

                        flatpickr('#datePicker', {
                            dateFormat: 'Y-m-d',
                            defaultDate: '<?= htmlspecialchars($date_filter) ?>',
                            onDayCreate: function(dObj, dStr, fpDay){
                                markDay(fpDay.dateObj, fpDay.node);
                            },
                            onChange: function(selectedDates, dateStr){
                                var f = document.querySelector('form');
                                if (f){
                                    var inp = document.querySelector('input[name="date"]');
                                    if (inp) inp.value = dateStr;
                                    f.submit();
                                }
                            }
                        });
                    })();
                </script>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <strong>Error:</strong>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                        <div>
                            <button onclick="this.parentNode.parentNode.parentNode.remove()" class="text-red-600 font-bold">Dismiss</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <?php if (!$subject_filter): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 text-center">
                    <i class="fas fa-info-circle text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">No subject selected</h3>
                    <p class="text-gray-500">Please choose a subject and date above to view attendance.</p>
                </div>
            <?php else: ?>
                <?php if ($totals['total'] === 0): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 text-center mb-6">
                        <i class="fas fa-user-slash text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">No students assigned</h3>
                        <p class="text-gray-500">There are no students assigned to this subject.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($gender_groups as $glabel => $gstudents): ?>
                    <?php if (empty($gstudents)) continue; ?>
                    <?php
                        // compute group totals
                        $g_tot = ['total' => count($gstudents), 'present' => 0, 'late' => 0, 'absent' => 0, 'second_scan' => 0];
                        foreach ($gstudents as $gs) {
                            if (isset($gs['computed_status'])) {
                                if ($gs['computed_status'] === 'Present') $g_tot['present']++;
                                elseif ($gs['computed_status'] === 'Late') $g_tot['late']++;
                                else $g_tot['absent']++;
                            } else {
                                $g_tot['absent']++;
                            }
                            if (!empty($gs['second_time'])) $g_tot['second_scan']++;
                        }
                    ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($glabel) ?> — <?= htmlspecialchars($display_subject_name ?: 'Attendance') ?></h2>
                                <p class="text-sm text-gray-600">Date: <?= htmlspecialchars($date_filter) ?> • Students: <?= $g_tot['total'] ?></p>
                            </div>
                            <div>
                                    <div class="inline-flex rounded-lg overflow-hidden shadow-sm border border-gray-100">
                                    <div class="px-4 py-2 bg-green-50 text-green-700 border-r border-gray-100 text-center">
                                        <div class="text-xs">Present</div>
                                        <div class="text-xl font-bold"><?= $g_tot['present'] ?></div>
                                    </div>
                                    <div class="px-4 py-2 bg-yellow-50 text-yellow-800 border-r border-gray-100 text-center">
                                        <div class="text-xs">Late</div>
                                        <div class="text-xl font-bold"><?= $g_tot['late'] ?></div>
                                    </div>
                                    <div class="px-4 py-2 bg-red-50 text-red-700 text-center">
                                        <div class="text-xs">Absent</div>
                                        <div class="text-xl font-bold"><?= $g_tot['absent'] ?></div>
                                    </div>
                                        <div class="px-4 py-2 bg-blue-50 text-blue-700 text-center border-l border-gray-100">
                                            <div class="text-xs">Time Out</div>
                                            <div class="text-xl font-bold"><?= $g_tot['second_scan'] ?></div>
                                        </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 class-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Student ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Section</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Time In</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Time Out</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php $i = 1; foreach ($gstudents as $s): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= $i++ ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($s['student_id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($s['name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($s['section']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($s['year_level']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php if ($s['computed_status'] === 'Present'): ?>
                                                    <span class="badge-present">Present</span>
                                                <?php elseif ($s['computed_status'] === 'Late'): ?>
                                                    <span class="badge-late">Late</span>
                                                <?php else: ?>
                                                    <span class="badge-absent">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $s['first_time'] ? htmlspecialchars($s['first_time']) : '-' ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $s['second_time'] ? htmlspecialchars($s['second_time']) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<!-- Teacher realtime feed removed (dashboard provides recent attendance) -->
