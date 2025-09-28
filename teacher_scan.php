<?php
date_default_timezone_set('Asia/Manila');
// Tell header.php that this page will provide its own (teacher) sidebar markup
$use_custom_sidebar = true;
include 'includes/header.php';
include 'includes/db.php';

// Only allow teachers
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Fetch teacher's subjects for dropdown
$subjects = [];
$auto_subject_id = null;
$auto_subject_name = null;
$subject_stmt = $conn->prepare("SELECT id, subject_name, schedule_days, start_time, end_time FROM subjects WHERE teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)");
$subject_stmt->bind_param("s", $teacher_id);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
while ($row = $subject_result->fetch_assoc()) {
    $subjects[] = $row;
}
$subject_stmt->close();

// Determine current subject by schedule (auto-select)
$nowTime = date('H:i:s');
$dayShort = date('D'); // Mon, Tue, ...
foreach ($subjects as $subj) {
    $days = array_map('trim', explode(',', (string)($subj['schedule_days'] ?? '')));
    if (!in_array($dayShort, $days, true)) {
        continue;
    }
    $start = $subj['start_time'] ?? null;
    $end = $subj['end_time'] ?? null;
    if (!$start || !$end) {
        continue;
    }
    // Handle ranges that cross midnight
    if ($end < $start) {
        if ($nowTime >= $start || $nowTime <= $end) {
            $auto_subject_id = (int)$subj['id'];
            $auto_subject_name = $subj['subject_name'];
            break;
        }
    } else {
        if ($nowTime >= $start && $nowTime <= $end) {
            $auto_subject_id = (int)$subj['id'];
            $auto_subject_name = $subj['subject_name'];
            break;
        }
    }
}

$student = null;
$msg = "";
$force_subject_id = null;
$force_subject_name = null;

// Helper: check if student has a 'Present' record for a subject on the given date
function has_present_today($conn, $student_id, $subject_id, $date) {
    $chk = $conn->prepare("SELECT COUNT(*) AS c FROM attendance WHERE student_id = ? AND subject_id = ? AND DATE(scan_time) = ? AND status = 'Present'");
    $chk->bind_param('sis', $student_id, $subject_id, $date);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    $chk->close();
    return intval($row['c']) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode']);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    if (!$subject_id && $auto_subject_id) {
        $subject_id = $auto_subject_id;
    }
    if (!$subject_id || !$barcode) {
        $msg = "<span class='text-red-600 font-bold'>❌ Please select a subject and scan a barcode.</span>";
    } else {
        // Get student info
        $stmt = $conn->prepare("SELECT * FROM students WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student) {
            $student_id = $student['student_id'];
            $now = date('Y-m-d H:i:s');
            $today = date('Y-m-d');

            // Get current subject name (for messages / inserts)
            $sub_stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
            $sub_stmt->bind_param("i", $subject_id);
            $sub_stmt->execute();
            $sub_stmt->bind_result($subject_name);
            $sub_stmt->fetch();
            $sub_stmt->close();

            // 1) RESOLVE existing "Pending Sign Out" entries for this student today.
            // Behavior changed: Instead of updating the existing pending row, insert an immutable
            // 'Signed Out' record to preserve history, then mark the original pending row as
            // 'Resolved Pending' so it's clear it was handled. If any were resolved, we stop
            // here and ask the user to scan again to sign in for a new subject.
            $resolved_prev = [];
            $pendingStmt = $conn->prepare("SELECT id, subject_id, subject_name FROM attendance WHERE student_id = ? AND DATE(scan_time) = ? AND status = 'Pending Sign Out' ORDER BY scan_time ASC");
            $pendingStmt->bind_param("ss", $student_id, $today);
            $pendingStmt->execute();
            $pendingResult = $pendingStmt->get_result();
            while ($pend = $pendingResult->fetch_assoc()) {
                // Insert a new immutable Signed Out record (audit) using the current timestamp
                $dedupe_seconds = 5;
                $dedupe_check = $conn->prepare("SELECT scan_time FROM attendance WHERE student_id=? AND subject_id=? ORDER BY scan_time DESC LIMIT 1");
                $dedupe_check->bind_param('si', $student_id, $pend['subject_id']);
                $dedupe_check->execute();
                $dedupe_res = $dedupe_check->get_result();
                $doInsertSigned = true;
                if ($dr = $dedupe_res->fetch_assoc()) {
                    $lastScanTime = strtotime($dr['scan_time']);
                    if (time() - $lastScanTime <= $dedupe_seconds) {
                        $doInsertSigned = false;
                    }
                }
                $dedupe_check->close();

                if ($doInsertSigned) {
                    // Only insert Signed Out if there was a prior Present today for that subject
                    if (has_present_today($conn, $student_id, $pend['subject_id'], $today)) {
                        $insertSigned = $conn->prepare("INSERT INTO attendance (student_id, scan_time, status, subject_id, subject_name) VALUES (?, ?, 'Signed Out', ?, ?)");
                        $insertSigned->bind_param("ssis", $student_id, $now, $pend['subject_id'], $pend['subject_name']);
                        $insertSigned->execute();
                        $insertSigned->close();
                    }
                }

                // Mark the original pending row as resolved so UI/queries can tell it's been handled
                $markResolved = $conn->prepare("UPDATE attendance SET status = 'Resolved Pending' WHERE id = ?");
                $markResolved->bind_param("i", $pend['id']);
                $markResolved->execute();
                $markResolved->close();

                $resolved_prev[] = $pend; // record resolved for message
            }
            $pendingStmt->close();

                if (!empty($resolved_prev)) {
                    // If we resolved pending sign-outs, do not proceed further in this request.
                    $parts = [];
                    $names = array_map(function($r){ return htmlspecialchars($r['subject_name']); }, $resolved_prev);
                    $parts[] = "✅ Time Out from " . implode(', ', $names) . " (resolved pending).";
                    $parts[] = "ℹ️ Please scan again to Time In for a new subject.";
                    $msg = implode(' ', $parts);
                } else {
                // 2) No pending to resolve. Check last attendance today to see if last is Present for a different subject.
                $prev_candidate = null;
                $lastAny = $conn->prepare("SELECT status, subject_id, subject_name FROM attendance WHERE student_id = ? AND DATE(scan_time) = ? ORDER BY scan_time DESC LIMIT 1");
                $lastAny->bind_param("ss", $student_id, $today);
                $lastAny->execute();
                $lastAnyResult = $lastAny->get_result();
                if ($lastAnyRow = $lastAnyResult->fetch_assoc()) {
                    if (isset($lastAnyRow['status']) && $lastAnyRow['status'] === 'Present' && (int)$lastAnyRow['subject_id'] !== $subject_id) {
                        $prev_candidate = [
                            'subject_id' => (int)$lastAnyRow['subject_id'],
                            'subject_name' => $lastAnyRow['subject_name'] ?? ''
                        ];
                    }
                }
                $lastAny->close();

                if ($prev_candidate) {
                    // 3) Previous present in a different subject exists.
                    // Disable one-scan auto-switch: require explicit Time Out first.
                    // If student is currently marked Present in another subject, do not auto insert
                    // Signed Out / Present records. Instead, instruct the user to Time Out the
                    // previous subject (scan that subject) before attempting Time In for a new one.

              $msg = "⚠️ Student is currently Time In for <b>" . htmlspecialchars($prev_candidate['subject_name']) . "</b>. " .
                  "Please scan to Time Out from that subject first before Time In to <b>" . htmlspecialchars($subject_name) . "</b>.";

              // Suggest / auto-select the previous subject so the next scan will Time Out that subject.
              $force_subject_id = (int)$prev_candidate['subject_id'];
              $force_subject_name = $prev_candidate['subject_name'];

                } else {
                    // 4) No pending and no previous Present in another subject — proceed to toggle current subject as normal.

                    // NEW: Verify the student is registered to the selected subject before allowing sign-in/out toggle.
                    $regStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM student_subjects WHERE student_id = ? AND subject_id = ?");
                    $regStmt->bind_param("si", $student_id, $subject_id);
                    $regStmt->execute();
                    $regStmt->bind_result($regCount);
                    $regStmt->fetch();
                    $regStmt->close();

                    if (empty($regCount)) {
                        // Student is not registered for this subject — do not allow sign in/out for it.
                        $msg = "<span class='text-red-600 font-bold'>❌ Student is not registered in the selected subject.</span>";
                    } else {
                        // Student is registered — proceed to toggle their status for this subject.
                        $lastScan = $conn->prepare("SELECT status FROM attendance WHERE student_id = ? AND subject_id = ? AND DATE(scan_time) = ? ORDER BY scan_time DESC LIMIT 1");
                        $lastScan->bind_param("sis", $student_id, $subject_id, $today);
                        $lastScan->execute();
                        $lastResult = $lastScan->get_result();
                        $nextStatus = 'Present';
                        if ($lastRow = $lastResult->fetch_assoc()) {
                            if ($lastRow['status'] === 'Present') {
                                $nextStatus = 'Signed Out';
                            } else {
                                $nextStatus = 'Present';
                            }
                        }
                        $lastScan->close();

                        // Insert attendance for the current subject with nextStatus (timestamp = now) (dedupe)
                        $dedupe_seconds = 5;
                        $dedupe_check = $conn->prepare("SELECT scan_time FROM attendance WHERE student_id=? AND subject_id=? ORDER BY scan_time DESC LIMIT 1");
                        $dedupe_check->bind_param('si', $student_id, $subject_id);
                        $dedupe_check->execute();
                        $dedupe_res = $dedupe_check->get_result();
                        $doInsertToggle = true;
                        if ($dr = $dedupe_res->fetch_assoc()) {
                            $lastScanTime = strtotime($dr['scan_time']);
                            if (time() - $lastScanTime <= $dedupe_seconds) {
                                $doInsertToggle = false;
                            }
                        }
                        $dedupe_check->close();

                        if ($doInsertToggle) {
                            // If nextStatus is Signed Out, ensure a prior Present exists today for this subject
                            if ($nextStatus === 'Signed Out') {
                                if (has_present_today($conn, $student_id, $subject_id, $today)) {
                                    $insert = $conn->prepare("INSERT INTO attendance (student_id, scan_time, status, subject_id, subject_name) VALUES (?, ?, ?, ?, ?)");
                                    $insert->bind_param("sssis", $student_id, $now, $nextStatus, $subject_id, $subject_name);
                                    $insert->execute();
                                    $insert->close();
                                } else {
                                    // No prior Present — do not insert Signed Out; instead insert Present and inform
                                    $insert = $conn->prepare("INSERT INTO attendance (student_id, scan_time, status, subject_id, subject_name) VALUES (?, ?, 'Present', ?, ?)");
                                    $insert->bind_param("sssis", $student_id, $now, $subject_id, $subject_name);
                                    $insert->execute();
                                    $insert->close();
                                    $nextStatus = 'Present';
                                }
                            } else {
                                $insert = $conn->prepare("INSERT INTO attendance (student_id, scan_time, status, subject_id, subject_name) VALUES (?, ?, ?, ?, ?)");
                                $insert->bind_param("sssis", $student_id, $now, $nextStatus, $subject_id, $subject_name);
                                $insert->execute();
                                $insert->close();
                            }
                        }

                        // Message
                        if ($nextStatus === 'Present') {
                            // Present = Time In (display wording only)
                            $msg = "✅ Time In for <b>" . htmlspecialchars($subject_name) . "</b> — " . htmlspecialchars($student['name']) . ".";
                        } else {
                            // Signed Out = Time Out (display wording only)
                            $msg = "📤 Time Out from <b>" . htmlspecialchars($subject_name) . "</b> — " . htmlspecialchars($student['name']) . ".";
                        }
                    }
                }
            }
        } else {
            $msg = "<span class='text-red-600 font-bold'>❌ Student not found!</span>";
        }
    }
}
?>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-6 pt-8 pb-8 w-full">
            <div class="grid grid-cols-1 gap-10">
                <!-- Left Side: Attendance Scanner -->
                <div class="flex flex-col">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-sky-500 to-blue-600 rounded-full shadow-lg mb-6">
                            <i class="fas fa-qrcode text-blue text-3xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-sky-800">Attendance Scanner</h1>
                        <p class="text-sky-600 text-md">Scan student barcodes to record attendance</p>

                    </div>
                    <?php if (!empty($msg)): ?>
                    <div class="mb-6">
                        <div class="bg-white rounded-xl p-4 text-center shadow-lg border border-sky-400">
                            <p class="text-lg font-semibold text-sky-800"><?= $msg ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <form method="post" id="scan-form" autocomplete="off" class="space-y-8 bg-white rounded-2xl shadow-xl p-8 border-2 border-sky-400">
                        <div>
                            <?php if (!empty($auto_subject_id)): ?>
                                <label class="block text-sky-700 font-bold mb-3 text-xl">Current Subject</label>
                                <div class="w-full p-5 border-2 border-sky-300 rounded-lg bg-sky-50 text-sky-900 font-semibold">
                                    <?= htmlspecialchars($auto_subject_name) ?>
                                </div>
                                <input type="hidden" name="subject_id" value="<?= (int)$auto_subject_id ?>">
                            <?php else: ?>
                                <label class="block text-sky-700 font-bold mb-3 text-xl">Select Subject</label>
                                <select name="subject_id" id="subject_id" class="w-full p-5 border-2 border-sky-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-200 transition text-xl">
                                    <option value="">Choose a subject...</option>
                                    <?php foreach ($subjects as $sub): ?>
                                        <?php
                                            $isSelected = false;
                                            if (isset($_POST['subject_id']) && $_POST['subject_id'] == $sub['id']) {
                                                $isSelected = true;
                                            }
                                        ?>
                                        <option value="<?= $sub['id'] ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($sub['subject_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sky-700 font-bold mb-3 text-xl">Student Barcode</label>
                            <div class="relative">
                                <input type="text" name="barcode" id="barcode" class="w-full p-6 border-2 border-sky-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-200 transition text-2xl text-center font-bold placeholder-sky-400" placeholder="Scan barcode here" autofocus autocomplete="off">
                                <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-barcode text-sky-400 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php if ($student): ?>
                    <div class="mt-6">
                        <div class="bg-white rounded-xl border border-sky-200 shadow-lg overflow-hidden">
                            <div class="w-full bg-gradient-to-r from-sky-500 to-blue-600 py-3 text-center">
                                <h3 class="font-bold text-lg text-white flex items-center justify-center gap-2">
                                    <i class="fas fa-id-card"></i> Student Information
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="flex flex-col items-center mb-4">
                                    <?php if ($student['profile_pic']): ?>
                                        <img src="assets/img/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile" class="w-24 h-24 object-cover rounded-full border-4 border-sky-200 shadow mb-3">
                                    <?php else: ?>
                                        <div class="w-24 h-24 bg-gradient-to-br from-sky-100 to-blue-100 rounded-full flex items-center justify-center border-4 border-sky-200 shadow mb-3">
                                            <i class="fas fa-user-graduate text-3xl text-sky-500"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="text-xl font-bold text-sky-800 mb-1"><?php echo htmlspecialchars($student['name']); ?></h4>
                                    <span class="text-sky-600 text-sm">Student</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="text-center p-3 bg-sky-50 rounded-lg">
                                        <span class="block text-sky-600 font-medium text-xs mb-1">Student ID</span>
                                        <span class="block font-bold text-sky-800"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                    </div>
                                    <div class="text-center p-3 bg-sky-50 rounded-lg">
                                        <span class="block text-sky-600 font-medium text-xs mb-1">Section</span>
                                        <span class="block font-bold text-sky-800"><?php echo htmlspecialchars($student['section']); ?></span>
                                    </div>
                                    <div class="text-center p-3 bg-sky-50 rounded-lg">
                                        <span class="block text-sky-600 font-medium text-xs mb-1">PC Number</span>
                                        <span class="block font-bold text-sky-800"><?php echo htmlspecialchars($student['pc_number']); ?></span>
                                    </div>
                                    <div class="text-center p-3 bg-sky-50 rounded-lg">
                                        <span class="block text-sky-600 font-medium text-xs mb-1">Course</span>
                                        <span class="block font-bold text-sky-800"><?php echo htmlspecialchars($student['course']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    const barcodeInput = document.getElementById('barcode');
    barcodeInput.focus();
    barcodeInput.addEventListener('input', function() {
        if (barcodeInput.value.length > 0) {
            document.getElementById('scan-form').submit();
        }
    });
    window.onload = function() {
        barcodeInput.focus();
    };
    // If server suggests a subject to force (Time Out candidate), apply it so next scan will sign out.
    <?php if (!empty($force_subject_id)): ?>
    (function(){
        try {
            // If the page shows a selectable <select> (no auto subject), set its value.
            var sel = document.getElementById('subject_id');
            if (sel) {
                sel.value = <?= (int)$force_subject_id ?>;
            }
            // If page uses a hidden input for auto-selected subject, replace it with the forced id
            var hidden = document.querySelector('input[type=hidden][name="subject_id"]');
            if (hidden) {
                hidden.value = <?= (int)$force_subject_id ?>;
            }
            // If there's a visible Current Subject display, update its text (best-effort)
            var cs = document.querySelector('div[aria-label="current-subject"]');
            if (cs) cs.textContent = <?= json_encode($force_subject_name) ?>;
            // Keep focus on barcode input so teacher can immediately rescan to sign out
            barcodeInput.focus();
        } catch(e) { console.error('apply forced subject error', e); }
    })();
    <?php endif; ?>
    // Removed live clock
    // Removed live feed auto-refresh (Attendance Records panel removed)
    </script>

    <?php include 'includes/footer.php'; ?>
