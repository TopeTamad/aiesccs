<?php
include 'includes/db.php';

// Ensure session is started before checking auth so header redirects work
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$student = null;
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode']);
    $stmt = $conn->prepare("SELECT * FROM students WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        $student_id = $student['student_id'];
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // Determine if 'lab' column exists in subjects table
        $hasLabSubject = false;
        if ($chk = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'lab'")) {
            if ($row = $chk->fetch_assoc()) { $hasLabSubject = intval($row['c']) > 0; }
        }

        // Get the subject (and lab if exists) information for this student
        $subject_sql = $hasLabSubject
            ? "SELECT s.id AS subject_id, s.lab AS lab FROM subjects s JOIN student_subjects ss ON s.id = ss.subject_id WHERE ss.student_id = ? LIMIT 1"
            : "SELECT s.id AS subject_id, NULL AS lab FROM subjects s JOIN student_subjects ss ON s.id = ss.subject_id WHERE ss.student_id = ? LIMIT 1";

        $subject_info = $conn->prepare($subject_sql);
        $subject_info->bind_param("s", $student_id);
        $subject_info->execute();
        $subject_result = $subject_info->get_result();
        $subject_data = $subject_result->fetch_assoc();
        
        $subject_id = $subject_data['subject_id'] ?? null;
        $lab = $subject_data['lab'] ?? null;

        // Get the last attendance record for today
        $lastScan = $conn->prepare("SELECT status FROM attendance WHERE student_id=? AND DATE(scan_time)=? ORDER BY scan_time DESC LIMIT 1");
        $lastScan->bind_param("ss", $student_id, $today);
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
        
        // Dedupe guard: don't insert if last attendance for this student has timestamp within N seconds
        $dedupe_seconds = 5;
        $dedupe_check = $conn->prepare("SELECT scan_time FROM attendance WHERE student_id=? ORDER BY scan_time DESC LIMIT 1");
        $dedupe_check->bind_param('s', $student_id);
        $dedupe_check->execute();
        $dedupe_res = $dedupe_check->get_result();
        $doInsert = true;
        if ($dr = $dedupe_res->fetch_assoc()) {
            $lastScanTime = strtotime($dr['scan_time']);
            if (time() - $lastScanTime <= $dedupe_seconds) {
                $doInsert = false;
            }
        }
        $dedupe_check->close();

        if ($doInsert) {
            // Determine if 'lab' column exists in attendance table
            $hasLabAttendance = false;
            if ($chk2 = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'lab'")) {
                if ($row2 = $chk2->fetch_assoc()) { $hasLabAttendance = intval($row2['c']) > 0; }
            }

            if ($hasLabAttendance) {
                // Insert attendance with subject_id and lab information
                $insert = $conn->prepare("INSERT INTO attendance (student_id, subject_id, lab, scan_time, status) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("sssss", $student_id, $subject_id, $lab, $now, $nextStatus);
            } else {
                // Insert attendance without lab column
                $insert = $conn->prepare("INSERT INTO attendance (student_id, subject_id, scan_time, status) VALUES (?, ?, ?, ?)");
                $insert->bind_param("ssss", $student_id, $subject_id, $now, $nextStatus);
            }
            $insert->execute();
        } else {
            // Skip insert due to dedupe; adjust message accordingly
            if ($nextStatus === 'Present') {
                $msg = "ℹ️ Duplicate scan ignored (too fast).";
            } else {
                $msg = "ℹ️ Duplicate sign-out ignored (too fast).";
            }
        }
        
        if ($doInsert) {
            if ($nextStatus === 'Present') {
                $msg = "✅ Attendance recorded for <b>" . htmlspecialchars($student['name']) . "</b>! (Sign In)";
            } else {
                $msg = "📤 Sign-out recorded for <b>" . htmlspecialchars($student['name']) . "</b>! (Sign Out)";
            }
        }
    } else {
        $msg = "<span style='color:red;'>❌ Student not found!</span>";
    }
}
?>

<!-- Tailwind Container -->
<style>
    .app-content { margin-left: 20rem; padding: 3.5rem 2rem; min-height:100vh; box-sizing:border-box; background: linear-gradient(90deg,#f0f7ff 0%, #fff0fb 100%); }
    .app-container { max-width: 3100px; margin: 0 auto; }
    @media (max-width:1900px){ .app-content{margin-left:0;padding:2rem;} }
</style>

<div class="app-content">
    <div class="app-container">
        <?php if (!empty($msg)) echo "<p class='text-center mb-8 text-xl font-semibold'>$msg</p>"; ?>
        <form method="post" id="scan-form" autocomplete="off" class="mb-10 flex flex-col items-center gap-6">
            <input type="text" name="barcode" id="barcode"
                class="w-96 p-5 border-2 border-indigo-200 rounded-xl shadow focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition text-2xl placeholder-gray-700 text-gray-800 font-semibold text-center" placeholder="Scan barcode here" autofocus autocomplete="off">
        </form>
        
        <?php if ($student): ?>
        <div class="p-6">
            <div class="bg-white rounded-2xl shadow-lg border border-sky-100 p-6 w-full max-w-sm mx-auto">
                <!-- Profile -->
                <div class="flex flex-col items-center mb-6">
                    <?php if ($student['profile_pic']): ?>
                        <img src="assets/img/<?php echo htmlspecialchars($student['profile_pic']); ?>" 
                             alt="Profile" 
                             class="w-28 h-28 object-cover rounded-full border-4 border-sky-200 shadow-md mb-3">
                    <?php else: ?>
                        <div class="w-28 h-28 bg-gradient-to-br from-sky-100 to-blue-100 rounded-full flex items-center justify-center border-4 border-sky-200 shadow-md mb-3">
                            <i class="fas fa-user-graduate text-4xl text-sky-500"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="text-2xl font-bold text-sky-800 mb-1">
                        <?php echo htmlspecialchars($student['name']); ?>
                    </h4>
                    <span class="text-sky-600 text-sm font-medium">Student</span>
                </div>

                <!-- Student Info -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="text-center p-4 bg-sky-50 rounded-xl shadow-sm hover:shadow-md transition">
                        <span class="block text-sky-600 font-medium text-xs mb-1">Student ID</span>
                        <span class="block font-bold text-sky-800">
                            <?php echo htmlspecialchars($student['student_id']); ?>
                        </span>
                    </div>
                    <div class="text-center p-4 bg-sky-50 rounded-xl shadow-sm hover:shadow-md transition">
                        <span class="block text-sky-600 font-medium text-xs mb-1">Section</span>
                        <span class="block font-bold text-sky-800">
                            <?php echo htmlspecialchars($student['section']); ?>
                        </span>
                    </div>
                    <div class="text-center p-4 bg-sky-50 rounded-xl shadow-sm hover:shadow-md transition">
                        <span class="block text-sky-600 font-medium text-xs mb-1">PC Number</span>
                        <span class="block font-bold text-sky-800">
                            <?php echo htmlspecialchars($student['pc_number']); ?>
                        </span>
                    </div>
                    <div class="text-center p-4 bg-sky-50 rounded-xl shadow-sm hover:shadow-md transition">
                        <span class="block text-sky-600 font-medium text-xs mb-1">Course</span>
                        <span class="block font-bold text-sky-800">
                            <?php echo htmlspecialchars($student['course']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="max-w-6xl mx-auto mt-12 bg-white shadow-2xl rounded-3xl p-12">
        <h2 class="text-2xl font-bold mb-8 text-center text-indigo-700">List of All Attendance Records</h2>
        <div class="text-center mb-4">
            <a href="includes/all_attendance.php" target="_blank" class="inline-block bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg font-semibold hover:bg-indigo-200 transition">See All</a>
        </div>
        <div class="overflow-x-auto" id="all-attendance-table"></div>
    </div>
</div>

<!-- JS Script -->
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

// Auto-refresh all attendance table every 1 second
setInterval(function() {
    fetch('includes/all_attendance.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('all-attendance-table').innerHTML = html;
        });
}, 1000);
</script>

<?php include 'includes/footer.php'; ?>