<?php
include 'includes/header.php';
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Handle Add Student
if (isset($_POST['add'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $section = $_POST['section'];
    $course = 'BSIS';
    $year_level = $_POST['year_level'];
    $gender = $_POST['gender'];
    $barcode = $student_id;
    $profile_pic = null;
    $pc_number = $_POST['pc_number'] ?? null;

    $check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $check->bind_param("s", $student_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "<span class='text-red-600 font-semibold'>❌ Student ID already exists. Please use a unique ID.</span>";
    } else {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid('student_', true) . '.' . $ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'assets/img/' . $profile_pic);
        }
        $stmt = $conn->prepare("INSERT INTO students (student_id, name, section, course, year_level, gender, barcode, profile_pic, pc_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $student_id, $name, $section, $course, $year_level, $gender, $barcode, $profile_pic, $pc_number);
        $stmt->execute();
        $msg = "<span class='text-green-600 font-semibold'>✅ Student added successfully!</span>";
    }
    $check->close();
}

// Handle Edit Student
if (isset($_POST['update'])) {
    $edit_id = $_POST['edit_id'];
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $section = $_POST['section'];
    $course = 'BSIS';
    $year_level = $_POST['year_level'];
    $gender = $_POST['gender'];
    $pc_number = $_POST['pc_number'] ?? null;

    // Check if student_id already exists for other students
    $check = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
    $check->bind_param("si", $student_id, $edit_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "<span class='text-red-600 font-semibold'>❌ Student ID already exists. Please use a unique ID.</span>";
    } else {
        $profile_pic = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid('student_', true) . '.' . $ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'assets/img/' . $profile_pic);
        }
        
        if ($profile_pic) {
            $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, section = ?, course = ?, year_level = ?, gender = ?, profile_pic = ?, pc_number = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $student_id, $name, $section, $course, $year_level, $gender, $profile_pic, $pc_number, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, section = ?, course = ?, year_level = ?, gender = ?, pc_number = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $student_id, $name, $section, $course, $year_level, $gender, $pc_number, $edit_id);
        }
        
        $stmt->execute();
        $msg = "<span class='text-green-600 font-semibold'>✅ Student updated successfully!</span>";
        $stmt->close();
    }
    $check->close();
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $msg = "<span class='text-green-600 font-semibold'>✅ Student deleted successfully!</span>";
    $stmt->close();
}

// Get student data for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_student = $result->fetch_assoc();
    $stmt->close();
}

// Fetch students grouped by year then section
$students = $conn->query("SELECT * FROM students ORDER BY year_level, section, name");

// Group by year/section
$grouped = [];
$students->data_seek(0);
while ($row = $students->fetch_assoc()) {
    $grouped[$row['year_level']][$row['section']][] = $row;
}
?>

<style>
    /* Make content span the remaining width beside a 20rem sidebar and responsive */
    .app-content {
        margin-left: 20rem; /* match sidebar width */
        padding: 5rem 2rem 4rem 2rem;
        min-height: 100vh;
        box-sizing: border-box;
        background: linear-gradient(90deg, #ebf8ff 0%, #f8efff 100%);
    }
    .app-container {
        width: calc(100% - 4rem);
        margin: 0 auto;
        background: #ffffff;
        box-shadow: 0 20px 50px rgba(16,24,40,0.08);
        border-radius: 1rem;
        padding: 3rem;
        max-width: 1400px;
    }
    @media (max-width: 900px) {
        .app-content { margin-left: 0; padding: 2rem 1rem; }
        .app-container { width: 100%; padding: 1.25rem; border-radius: 0.75rem; }
    }
</style>

<div class="app-content">
    <div class="app-container">
        <h2 class="text-5xl font-extrabold mb-10 text-center text-indigo-700 tracking-tight">Student Management</h2>

        <?php if (isset($msg)) echo "<p class='mb-6 text-center text-lg'>$msg</p>"; ?>

        <!-- Add/Edit Student Form -->
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-8 mb-12 border border-indigo-100">
            <h3 class="text-2xl font-bold text-indigo-800 mb-6 text-center"><?= $edit_student ? 'Edit Student' : 'Add New Student' ?></h3>
            <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($edit_student): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_student['id'] ?>">
                <?php endif; ?>
                <input type="text" name="student_id" placeholder="Student ID" required class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg" value="<?= $edit_student ? htmlspecialchars($edit_student['student_id']) : '' ?>">
                <input type="text" name="name" placeholder="Full Name" required class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg" value="<?= $edit_student ? htmlspecialchars($edit_student['name']) : '' ?>">
                <select name="section" required class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg">
                    <option value="">Select Section</option>
                    <option value="A" <?= ($edit_student && $edit_student['section'] == 'A') ? 'selected' : '' ?>>Section A</option>
                    <option value="B" <?= ($edit_student && $edit_student['section'] == 'B') ? 'selected' : '' ?>>Section B</option>
                </select>
                <input type="text" name="course" value="BSIS" readonly class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg bg-gray-100 cursor-not-allowed text-lg">
                <select name="year_level" required class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg">
                    <option value="">Year Level</option>
                    <?php for ($i=1; $i<=4; $i++): ?>
                        <option value="<?= $i ?>" <?= ($edit_student && $edit_student['year_level'] == $i) ? 'selected' : '' ?>>Year <?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <select name="gender" required class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg">
                    <option value="">Select Gender</option>
                    <option value="Male" <?= ($edit_student && $edit_student['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($edit_student && $edit_student['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                </select>
                <input type="text" name="pc_number" placeholder="PC Number" class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-500 text-lg" value="<?= $edit_student ? htmlspecialchars($edit_student['pc_number']) : '' ?>">
                <input type="file" name="profile_pic" accept="image/*" class="p-5 border-2 border-indigo-200 rounded-xl shadow-lg text-lg">
                <?php if ($edit_student && $edit_student['profile_pic']): ?>
                    <div class="col-span-full text-center">
                        <p class="text-sm text-gray-600 mb-2">Current Photo:</p>
                        <img src="assets/img/<?= $edit_student['profile_pic'] ?>" class="w-20 h-20 object-cover rounded-full border-2 border-indigo-200 mx-auto">
                    </div>
                <?php endif; ?>
                
                <div class="col-span-full flex justify-center gap-6 mt-6">
                    <?php if ($edit_student): ?>
                        <button name="update" class="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-xl shadow-lg text-xl font-bold transition transform hover:scale-105">Update Student</button>
                    <?php else: ?>
                        <button name="add" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-xl shadow-lg text-xl font-bold transition transform hover:scale-105">Add Student</button>
                    <?php endif; ?>
                    <a href="students.php" class="bg-gray-500 hover:bg-gray-600 text-white px-8 py-4 rounded-xl shadow-lg text-xl font-bold transition transform hover:scale-105">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Student Records -->
        <div class="space-y-6">
            <?php foreach ($grouped as $year => $sections): ?>
                <div class="border-2 border-indigo-200 rounded-2xl shadow-xl bg-gradient-to-r from-indigo-50 to-purple-50">
                    <button type="button" onclick="toggleCollapse('year-<?= $year ?>')" class="w-full flex justify-between items-center px-8 py-6 font-bold text-indigo-800 text-xl hover:bg-indigo-100 transition rounded-t-2xl">
                        <span>Year <?= $year ?></span>
                        <i class="fas fa-chevron-down text-2xl"></i>
                    </button>
                    <div id="year-<?= $year ?>" style="display:none;" class="p-6">
                        <?php foreach ($sections as $section => $students_in_section): ?>
                            <div class="mb-6 border-2 border-indigo-100 rounded-xl bg-white shadow-lg">
                                <button type="button" onclick="toggleCollapse('section-<?= $year ?>-<?= $section ?>')" class="w-full flex justify-between items-center px-6 py-4 font-semibold text-indigo-700 hover:bg-indigo-50 transition rounded-t-xl">
                                    <span class="text-lg">Section <?= htmlspecialchars($section) ?></span>
                                    <i class="fas fa-chevron-down text-xl"></i>
                                </button>
                                <div id="section-<?= $year ?>-<?= $section ?>" style="display:none;" class="overflow-x-auto p-4">
                                    <table class="min-w-full text-left border-collapse">
                                        <thead class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white">
                                            <tr>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">ID</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Name</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Section</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Year</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Gender</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Photo</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">Barcode</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold">PC No.</th>
                                                <th class="px-6 py-4 border border-indigo-400 text-lg font-bold text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_in_section as $row): ?>
                                                <tr class="hover:bg-indigo-50 transition">
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg"><?= $row['student_id'] ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg font-semibold"><?= $row['name'] ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg"><?= $row['section'] ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg"><?= $row['year_level'] ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg"><?= htmlspecialchars($row['gender'] ?? '') ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-center">
                                                        <?php if ($row['profile_pic']): ?>
                                                            <img src="assets/img/<?= $row['profile_pic'] ?>" class="w-16 h-16 object-cover rounded-full border-2 border-indigo-200 shadow-lg">
                                                        <?php else: ?>
                                                            <span class="inline-block w-16 h-16 bg-gray-200 rounded-full border-2 border-indigo-200"></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 border border-indigo-200"><svg id="barcode-<?= $row['id'] ?>"></svg></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-lg"><?= htmlspecialchars($row['pc_number'] ?? '') ?></td>
                                                    <td class="px-6 py-4 border border-indigo-200 text-center">
                                                        <div class="flex flex-col gap-2">
                                                            <a href="student_profile.php?id=<?= $row['id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg transition transform hover:scale-105 w-full text-center">Profile</a>
                                                            <a href="students.php?edit=<?= $row['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg transition transform hover:scale-105 w-full text-center">Edit</a>
                                                            <a href="students.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this student?');" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg transition transform hover:scale-105 w-full text-center">Delete</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleCollapse(id) {
    var el = document.getElementById(id);
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
}
</script>

<!-- JsBarcode -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode/dist/JsBarcode.all.min.js"></script>
<script>
<?php
$students = $conn->query("SELECT * FROM students");
while ($row = $students->fetch_assoc()) {
    echo "JsBarcode('#barcode-{$row['id']}', '{$row['barcode']}', {format: 'CODE128', width:2, height:40, displayValue:true});\n";
}
?>
</script>
<?php include 'includes/footer.php'; ?>
