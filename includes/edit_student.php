<?php
session_start();
include 'db.php';

// Redirect if not logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Make sure student_id is provided
if (!isset($_GET['student_id'])) {
    header("Location: ../teacher_students.php");
    exit();
}

$student_id = $_GET['student_id'];
$message = "";
$message_type = "";

// Get student details
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header("Location: ../teacher_students.php");
    exit();
}

// Update when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $section = trim($_POST['section']);
    $year_level = intval($_POST['year_level']);
    $course = trim($_POST['course']);
    $barcode = trim($_POST['barcode']);
    $pc_number = trim($_POST['pc_number']);

    // Validate inputs
    if (empty($name) || empty($section) || empty($course) || empty($barcode)) {
        $message = "❌ All fields are required!";
        $message_type = "error";
    } elseif ($year_level < 1 || $year_level > 5) {
        $message = "❌ Year level must be between 1 and 5!";
        $message_type = "error";
    } else {
        // Check if barcode already exists for another student
        $check_stmt = $conn->prepare("SELECT student_id FROM students WHERE barcode = ? AND student_id != ?");
        $check_stmt->bind_param("ss", $barcode, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "❌ Barcode already exists for another student!";
            $message_type = "error";
        } else {
            // Check if PC number already exists for another student
            if (!empty($pc_number)) {
                $pc_check_stmt = $conn->prepare("SELECT student_id FROM students WHERE pc_number = ? AND student_id != ?");
                $pc_check_stmt->bind_param("ss", $pc_number, $student_id);
                $pc_check_stmt->execute();
                $pc_check_result = $pc_check_stmt->get_result();
                
                if ($pc_check_result->num_rows > 0) {
                    $message = "❌ PC number already assigned to another student!";
                    $message_type = "error";
                } else {
                    // Update student
                    $update_stmt = $conn->prepare("
                        UPDATE students 
                        SET name = ?, section = ?, year_level = ?, course = ?, barcode = ?, pc_number = ?
                        WHERE student_id = ?
                    ");
                    $update_stmt->bind_param("ssissss", $name, $section, $year_level, $course, $barcode, $pc_number, $student_id);

                    if ($update_stmt->execute()) {
                        $message = "✅ Student updated successfully!";
                        $message_type = "success";
                        // Refresh data after update
                        $stmt->execute();
                        $student = $stmt->get_result()->fetch_assoc();
                    } else {
                        $message = "❌ Update failed: " . $conn->error;
                        $message_type = "error";
                    }
                }
            } else {
                // Update student without PC number
                $update_stmt = $conn->prepare("
                    UPDATE students 
                    SET name = ?, section = ?, year_level = ?, course = ?, barcode = ?, pc_number = ?
                    WHERE student_id = ?
                ");
                $update_stmt->bind_param("ssissss", $name, $section, $year_level, $course, $barcode, $pc_number, $student_id);

                if ($update_stmt->execute()) {
                    $message = "✅ Student updated successfully!";
                    $message_type = "success";
                    // Refresh data after update
                    $stmt->execute();
                    $student = $stmt->get_result()->fetch_assoc();
                } else {
                    $message = "❌ Update failed: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Teacher Dashboard</title>
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
    <!-- Shared sidebar (relative include because this file is in includes/) -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-72 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-edit text-blue-500"></i>Edit Student
                        </h1>
                        <p class="text-gray-600 mt-2">Update student information</p>
                    </div>
                    <div class="text-right">
                        <a href="../teacher_students.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Students
                        </a>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg text-sm font-semibold 
                        <?= $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
                        <div class="flex items-center">
                            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                            <?= $message ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Student ID (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-id-card mr-1"></i>Student ID
                            </label>
                            <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" disabled
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i>Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-users mr-1"></i>Section <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="section" value="<?= htmlspecialchars($student['section']) ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Year Level -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap mr-1"></i>Year Level <span class="text-red-500">*</span>
                            </label>
                            <select name="year_level" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= $student['year_level'] == $i ? 'selected' : '' ?>>
                                        Year <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Course -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-book mr-1"></i>Course <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="course" value="<?= htmlspecialchars($student['course']) ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-barcode mr-1"></i>Barcode <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="barcode" value="<?= htmlspecialchars($student['barcode']) ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- PC Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-desktop mr-1"></i>PC Number
                            </label>
                            <input type="text" name="pc_number" value="<?= htmlspecialchars($student['pc_number'] ?? '') ?>" 
                                   placeholder="Optional"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Leave empty if no PC is assigned</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <a href="../teacher_students.php" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
