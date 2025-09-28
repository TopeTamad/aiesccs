<?php
if (!isset($teacher_name)) {
    $teacher_name = isset($_SESSION['teacher_name']) ? $_SESSION['teacher_name'] : '';
}
?>

<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure DB connection exists; include if missing
if (!isset($conn)) {
    $dbPath = __DIR__ . '/db.php';
    if (file_exists($dbPath)) {
        include_once $dbPath;
    }
}

// Fetch profile picture for logged-in teacher (if any)
$sidebarProfilePic = 'assets/img/logo.png';
$sidebarTeacherName = htmlspecialchars($teacher_name);
if (isset($_SESSION['teacher_id']) && isset($conn)) {
    $tId = $_SESSION['teacher_id'];
    $pstmt = $conn->prepare("SELECT profile_pic, name FROM teachers WHERE teacher_id = ? LIMIT 1");
    if ($pstmt) {
        $pstmt->bind_param('s', $tId);
        $pstmt->execute();
        $pstmt->bind_result($db_profile_pic, $db_name);
        if ($pstmt->fetch()) {
            if (!empty($db_profile_pic)) {
                $sidebarProfilePic = 'assets/img/' . $db_profile_pic;
            }
            if (!empty($db_name)) {
                $sidebarTeacherName = htmlspecialchars($db_name);
            }
        }
        $pstmt->close();
    }
}

?>

<!-- Wider Sidebar -->
<aside class="sidebar fixed top-0 left-0 h-full w-80 bg-white shadow-lg z-30 flex flex-col transition-all duration-300">
    <div class="flex flex-col items-center py-8 border-b border-gray-200">
        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-300 mb-2 bg-blue-100">
            <img src="<?= $sidebarProfilePic ?>" alt="profile" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='assets/img/logo.png'">
        </div>
        <div class="mt-2 text-center">
            <div class="font-bold text-xl text-gray-800"><?= $sidebarTeacherName ?></div>
            <div class="text-sm text-gray-500">Faculty</div>
        </div>
    </div>
    <nav class="flex-1 flex flex-col gap-2 mt-6 px-4">
        <a href="teacher_dashboard.php" class="sidebar-link px-6 py-4 rounded-xl flex items-center gap-3 text-gray-700 font-semibold hover:bg-blue-500 hover:text-white transition-all <?php if(basename($_SERVER['PHP_SELF'])=='teacher_dashboard.php') echo 'bg-blue-600 text-white'; ?>">
            <i class="fas fa-home text-xl" style="color: blue;"></i> 
            <span class="text-lg">Dashboard</span>
        </a>
        <a href="teacher_scan.php" class="sidebar-link px-6 py-4 rounded-xl flex items-center gap-3 text-gray-700 font-semibold hover:bg-blue-500 hover:text-white transition-all <?php if(basename($_SERVER['PHP_SELF'])=='teacher_scan.php') echo 'bg-blue-600 text-white'; ?>">
            <i class="fas fa-barcode text-xl" style="color: blue;"></i> 
            <span class="text-lg">Scan Attendance</span>
        </a>
        <a href="teacher_students.php" class="sidebar-link px-6 py-4 rounded-xl flex items-center gap-3 text-gray-700 font-semibold hover:bg-blue-500 hover:text-white transition-all <?php if(basename($_SERVER['PHP_SELF'])=='teacher_students.php') echo 'bg-blue-600 text-white'; ?>">
            <i class="fas fa-user-graduate text-xl" style="color: blue;"></i> 
            <span class="text-lg">View Students</span>
        </a>

        <a href="teacher_attendance_records.php" class="sidebar-link px-6 py-4 rounded-xl flex items-center gap-3 text-gray-700 font-semibold hover:bg-blue-500 hover:text-white transition-all <?php if(basename($_SERVER['PHP_SELF'])=='teacher_attendance_records.php') echo 'bg-blue-600 text-white'; ?>">
            <i class="fas fa-chart-bar text-xl" style="color: blue;"></i> 
            <span class="text-lg">Attendance Records</span>
        </a>

        <a href="teacher_logout.php" class="sidebar-link px-6 py-4 rounded-xl flex items-center gap-3 text-red-600 font-semibold hover:bg-red-600 hover:text-white transition-all mt-auto mb-6">
            <i class="fas fa-sign-out-alt text-xl"></i> 
            <span class="text-lg">Logout</span>
        </a>
    </nav>
</aside>

<!-- Adjust main content margin to match wider sidebar -->
<style>
    .sidebar-link.active, 
    .sidebar-link:hover { 
        background: linear-gradient(90deg, #4f8cff 0%, #a18fff 100%); 
        color: #fff !important; 
    }
    .sidebar-link i { 
        min-width: 2rem; 
    }
    @media (min-width: 901px) {
        .main-content {
            margin-left: 20rem; /* 320px - matches sidebar width */
        }
    }
    @media (max-width: 900px) {
        .sidebar { 
            left: -320px; 
        }
        .sidebar.open { 
            left: 0; 
        }
        .main-content {
            margin-left: 0;
        }
    }
    /* Enforce fixed width and avoid layout shifts when links become active */
    .sidebar {
        width: 20rem !important; /* 320px */
        min-width: 20rem !important;
        max-width: 20rem !important;
        box-sizing: border-box;
    }
    .sidebar .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar .sidebar-link i {
        flex: 0 0 auto;
        transform: none !important;
    }
    /* Prevent hover/active states from changing padding/size */
    .sidebar .sidebar-link, .sidebar .sidebar-link * {
        transition: background-color 0.18s ease, color 0.18s ease !important;
    }
    /* Lock link padding and prevent transform-based shifts */
    .sidebar .sidebar-link {
        padding: 0.9rem 1.25rem; /* fixed padding */
        transform: none !important;
    }
    .sidebar .sidebar-link.active, .sidebar .sidebar-link:hover {
        box-shadow: none !important; /* avoid extra shadow that may alter visuals */
    }
    /* Ensure text doesn't push layout: limit label width inside link */
    .sidebar .sidebar-link span {
        display: inline-block;
        max-width: calc(20rem - 4.5rem); /* leave room for icon and padding */
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>