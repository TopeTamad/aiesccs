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

// Get teacher's subjects
$subjects_query = $conn->prepare("
    SELECT s.* 
    FROM subjects s 
    WHERE s.teacher_id = (SELECT id FROM teachers WHERE teacher_id = ?)
");
$subjects_query->bind_param("s", $teacher_id);
$subjects_query->execute();
$subjects = $subjects_query->get_result();

// Get students assigned to teacher's subjects

$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
if ($subject_filter) {
    // Show only students registered to the selected subject
    $students_query = $conn->prepare("
        SELECT DISTINCT st.* 
        FROM students st
        JOIN student_subjects ss ON st.student_id = ss.student_id
        WHERE ss.subject_id = ?
        AND st.course = 'BSIS'
        ORDER BY st.year_level, st.section, st.name
    ");
    $students_query->bind_param("i", $subject_filter);
    $students_query->execute();
    $students = $students_query->get_result();
} else {
    // No subject selected yet; do not load students
    $students = null;
}

// Group students by year then section, with males first
$grouped = [];
if ($students) {
    while ($row = $students->fetch_assoc()) {
        $grouped[$row['year_level']][$row['section']][] = $row;
    }
    
    // Sort students within each section: males first, then females
    foreach ($grouped as $year => &$sections) {
        foreach ($sections as $section => &$students_in_section) {
            usort($students_in_section, function($a, $b) {
                // If both have same gender, sort by name
                if ($a['gender'] === $b['gender']) {
                    return strcmp($a['name'], $b['name']);
                }
                // Males first, then females
                return ($a['gender'] === 'Male') ? -1 : 1;
            });
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; }
        .sidebar-link.active, .sidebar-link:hover { background: linear-gradient(90deg, #4f8cff 0%, #a18fff 100%); color: #fff !important; }
        .sidebar-link i { min-width: 1.5rem; }
        @media (max-width: 900px) {
            .sidebar { left: -220px; }
            .sidebar.open { left: 0; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex">
    <!-- Shared sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="flex-1 ml-80 min-h-screen main-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-user-graduate text-blue-500"></i>My Students
                        </h1>
                        <p class="text-gray-600 mt-2">Students assigned to your subjects</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-blue-600"><?= $students ? $students->num_rows : 0 ?></p>
                    </div>
                </div>
            </div>
            <!-- My Subjects Summary -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-book-open text-blue-500"></i>My Teaching Subjects
                </h2>
                <?php if ($subjects->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <a href="teacher_students.php?subject=<?= $subject['id'] ?>" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border-2 border-blue-200 shadow flex flex-col transition-transform duration-150 hover:-translate-y-1 hover:shadow-lg cursor-pointer">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full shadow">
                                        <?= htmlspecialchars($subject['subject_code']) ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">Assigned students only</p>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No subjects assigned yet.</p>
                        <p class="text-sm text-gray-500">Contact the administrator to assign subjects.</p>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Students by Year and Section -->
            <?php if (!empty($grouped)): ?>
                <?php foreach ($grouped as $year => $sections): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-graduation-cap text-blue-500"></i>Year <?= htmlspecialchars($year) ?>
                        </h2>
                        <?php foreach ($sections as $section => $students_in_section): ?>
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                    <i class="fas fa-users text-indigo-500"></i>
                                    Section <?= htmlspecialchars($section) ?>
                                    <span class="ml-2 bg-indigo-100 text-indigo-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                        <?= count($students_in_section) ?> students
                                    </span>
                                </h3>
                                <div class="space-y-6">
                                    <?php
                                    // Separate students by gender
                                    $male_students = array_filter($students_in_section, function($student) {
                                        return $student['gender'] === 'Male';
                                    });
                                    $female_students = array_filter($students_in_section, function($student) {
                                        return $student['gender'] === 'Female';
                                    });
                                    ?>
                                    
                                    <!-- Male Students -->
                                    <?php if (!empty($male_students)): ?>
                                        <div>
                                            <h4 class="text-md font-bold text-blue-600 mb-3 flex items-center gap-2">
                                                <i class="fas fa-mars text-blue-500"></i>Male Students (<?= count($male_students) ?>)
                                            </h4>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-blue-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Student ID</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Section</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Year</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Gender</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Barcode</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        <?php foreach ($male_students as $student): ?>
                                                            <tr class="hover:bg-blue-50">
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                    <?= htmlspecialchars($student['student_id']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['name']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['section']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['year_level']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['gender'] ?? '') ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                                                        <?= htmlspecialchars($student['barcode']) ?>
                                                                    </code>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <a href="includes/edit_student.php?student_id=<?= urlencode($student['student_id']) ?>" class="inline-flex items-center px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 text-xs font-semibold">
                                                                        <i class="fas fa-edit mr-1"></i>Edit
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Female Students -->
                                    <?php if (!empty($female_students)): ?>
                                        <div>
                                            <h4 class="text-md font-bold text-pink-600 mb-3 flex items-center gap-2">
                                                <i class="fas fa-venus text-pink-500"></i>Female Students (<?= count($female_students) ?>)
                                            </h4>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-pink-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Student ID</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Section</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Year</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Gender</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Barcode</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-pink-600 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        <?php foreach ($female_students as $student): ?>
                                                            <tr class="hover:bg-pink-50">
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                    <?= htmlspecialchars($student['student_id']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['name']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['section']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['year_level']) ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <?= htmlspecialchars($student['gender'] ?? '') ?>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                                                        <?= htmlspecialchars($student['barcode']) ?>
                                                                    </code>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <a href="includes/edit_student.php?student_id=<?= urlencode($student['student_id']) ?>" class="inline-flex items-center px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 text-xs font-semibold">
                                                                        <i class="fas fa-edit mr-1"></i>Edit
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 text-center">
                    <i class="fas fa-user-graduate text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">No Students Loaded</h3>
                    <p class="text-gray-500">Select a subject above to view its students.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 