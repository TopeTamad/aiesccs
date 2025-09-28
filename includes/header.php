<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
// Allow pages to request a custom/external sidebar (for slimmer sidebars)
// Set $use_custom_sidebar = true in a page before including this file to enable.
$use_custom_sidebar = isset($use_custom_sidebar) ? (bool)$use_custom_sidebar : false;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Header styles */
            .pro-header {
            background: linear-gradient(90deg, #4f8cff 0%, #a18fff 100%);
            box-shadow: 0 2px 12px rgba(79,140,255,0.08);
            border-bottom: 1.5px solid #e0e0e0;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            position: fixed;
            top: 0;
            right: 0;
            left: 20rem;
            z-index: 20;
        }
        .admin-icon {
            background: #fff;
            color: #4f8cff;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            box-shadow: 0 2px 8px rgba(79,140,255,0.10);
        }
        .admin-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            margin-left: 16px;
        }
        .clock-badge {
            background: #fff;
            color: #4f8cff;
            border-radius: 999px;
            padding: 7px 18px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 1px 6px rgba(79,140,255,0.08);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        /* Solid rectangular sidebar with consistent wider width */
        .glass-sidebar{
            background: #ffffff;
            box-shadow: 0 6px 24px rgba(31,38,135,0.06);
            backdrop-filter: none;
            border-radius: 0 !important;
            border: 1px solid rgba(0,0,0,0.06);
            transform: none !important;
            transition: none !important;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 20rem; /* match teacher sidebar (320px) */
            z-index: 10001;
            padding-top: 36px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 28px; /* wider padding for better proportions */
            border-radius: 0 !important;
            font-weight: 700;
            color: #374151;
            transition: none !important;
            margin-bottom: 6px;
            font-size: 1.125rem; /* slightly larger text */
        }
        .sidebar-link i {
            font-size: 1.375em; /* larger icon */
            min-width: 1.75rem; /* consistent icon width */
        }
        .sidebar-link.active, .sidebar-link:hover {
            background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%);
            color: #fff !important;
            box-shadow: 0 6px 18px rgba(99,102,241,0.08);
            transform: translateY(-1px);
        }
        .sidebar-logo {
            font-size: 1.5rem; /* larger logo text */
            font-weight: 800;
            color: #6366f1;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        /* Mobile: keep same fixed rectangle (no sliding). Hide hamburger. */
        .sidebar-hamburger { display: none !important; }
        @media (max-width: 900px) {
            .glass-sidebar { position: fixed; left: 20rem; top:0; height:100vh; width:20rem; transform:none !important; }
            .glass-sidebar.open { left: 0; }
            .ml-80 { margin-left: 20rem !important; }
        }
        /* overrides to remove small rounding/movement on children */
        .sidebar-link { border-radius: 0 !important; transition: none !important; }
        /* larger logo size */
        .glass-sidebar img { 
            border-radius: 9999px !important; 
            width: 160px !important; /* larger profile image */
            height: 160px !important;
            border: 4px solid #93c5fd !important; /* blue-300 border */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>

    <script>
        // Clock update function
        function updateHeaderClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            document.getElementById('headerClock').innerText = timeString;
        }
        // Update clock every second
        setInterval(updateHeaderClock, 1000);
        updateHeaderClock(); // Initial call

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;
            sidebar.classList.toggle('open');
        }
        // Close sidebar on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('open')) sidebar.classList.remove('open');
            }
        });
    </script>
</head>
<body class="bg-gradient-to-tr from-blue-100 via-purple-100 to-pink-100 min-h-screen">
<!-- Hamburger for mobile -->
<div class="sidebar-hamburger hidden lg:hidden" onclick="toggleSidebar()" aria-label="Open menu">
    <i class="fa fa-bars text-indigo-700 text-2xl"></i>
</div>

<div class="flex min-h-screen">
    <?php if (!$use_custom_sidebar): ?>
    <!-- Sidebar (desktop: wider w-80, mobile: slide-in) -->
    <div id="sidebar" class="glass-sidebar w-[20rem] lg:w-[20rem] bg-white shadow-lg rounded-2xl flex flex-col py-6 px-5 fixed lg:inset-y-6 lg:left-6 z-20 lg:translate-x-0 transform transition-transform duration-300 ease-in-out lg:static lg:rounded-xl">
        <!-- Logo -->
        <div class="flex flex-col items-center mb-8 px-2">
            <img src="assets/img/logo.png" alt="Logo" class="rounded-full object-cover shadow-md mb-3 bg-gray-100" onerror="this.style.display='none'">
            <h2 class="sidebar-logo">IS4AG5</h2>
            <p class="text-xs text-gray-500 mt-1"></p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 flex flex-col gap-1 mt-3">
            <a href="dashboard.php" class="sidebar-link <?php if($currentPage==='dashboard.php') echo 'active'; ?>"><i class="fa-solid fa-chart-pie text-indigo-500"></i>Dashboard</a>
            <a href="students.php" class="sidebar-link <?php if($currentPage==='students.php') echo 'active'; ?>"><i class="fa-solid fa-user-graduate text-indigo-500"></i>Students</a>
            <a href="add_teacher.php" class="sidebar-link <?php if($currentPage==='add_teacher.php') echo 'active'; ?>"><i class="fa-solid fa-user-plus text-indigo-500"></i>Faculty</a>
            <a href="attendance.php" class="sidebar-link <?php if($currentPage==='attendance.php') echo 'active'; ?>"><i class="fa-solid fa-calendar-check text-indigo-500"></i>Attendance</a>
            <a href="student_dashboard.php" class="sidebar-link <?php if($currentPage==='student_dashboard.php') echo 'active'; ?>"><i class="fa-solid fa-tachometer-alt text-indigo-500"></i>Student Dashboard</a>

            <hr class="my-3 border-gray-300">

            <a href="add_subject.php" class="sidebar-link <?php if($currentPage==='add_subject.php') echo 'active'; ?>"><i class="fa-solid fa-book-medical text-indigo-500"></i>Add Subject</a>
            <a href="assign_subjects.php" class="sidebar-link <?php if($currentPage==='assign_subjects.php') echo 'active'; ?>"><i class="fa-solid fa-book-open-reader text-indigo-500"></i>Assign Subjects</a>
            <a href="manage_teachers.php" class="sidebar-link <?php if($currentPage==='manage_teachers.php') echo 'active'; ?>"><i class="fa-solid fa-users-gear text-indigo-500"></i>Manage Faculty</a>
            <a href="manage_subjects.php" class="sidebar-link <?php if($currentPage==='manage_subjects.php') echo 'active'; ?>"><i class="fa-solid fa-book-open-reader text-indigo-500"></i>Manage Subjects</a>
        </nav>

        <!-- Logout -->
        <form action="logout.php" method="post" class="mt-6 w-full px-2">
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

    <!-- Main content wrapper: apply left margin to clear the larger sidebar on wide screens -->
    <div class="flex-1 ml-[20rem] lg:ml-[20rem]">
    <?php else: ?>
    <!-- Using external custom sidebar: include the page-specific sidebar here so pages only need to set $use_custom_sidebar = true before including header.php -->
    <?php
    $sidebarPath = __DIR__ . '/sidebar.php';
    if (file_exists($sidebarPath)) {
        include $sidebarPath;
    }
    ?>
    <div class="flex-1 main-content">
    <?php endif; ?>
        <!-- Header -->
        <header class="pro-header">
            <div style="display: flex; align-items: center;">
                <span class="admin-icon"><i class="fas fa-user-shield"></i></span>
                <?php if (!$use_custom_sidebar): ?>
                    <h1 class="admin-title">Welcome <?= isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'User' ?></h1>
                <?php else: ?>
                    <!-- custom sidebar pages: hide textual welcome and keep icon only -->
                    <h1 class="admin-title">&nbsp;</h1>
                <?php endif; ?>
            </div>
            <div class="clock-badge">
                <i class="fas fa-clock"></i>
                <span id="headerClock"></span>
            </div>
        </header>

        <?php if ($use_custom_sidebar): ?>
        <style>
            /* When using the teacher/custom sidebar (20rem), shift header to align flush with sidebar */
            .pro-header {
                left: 20rem !important; /* matches .sidebar w-80 (20rem) */
                width: calc(100% - 20rem) !important;
                border-radius: 0 !important;
            }
            /* ensure main content uses same margin-left (sidebar.php sets .main-content margin-left: 20rem) */
            @media (min-width: 901px) {
                .main-content { margin-left: 20rem !important; }
            }
        </style>
        <?php endif; ?>
        <!-- Add spacing to prevent content from going under header -->
        <div style="height: 80px;"></div>
        <!-- Main content starts here -->
