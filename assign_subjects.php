<?php
include 'includes/header.php';
include 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle subject assignment to students
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_subject'])) {
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $subject_id = $_POST['subject_id'];
    
    if (empty($student_ids) || empty($subject_id)) {
        $error = "Please select at least one student and a subject!";
    } else {
        // Filter out student_ids that do not exist in students table
        $invalid = 0;
        if (count($student_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $types = str_repeat('s', count($student_ids));
            $sql = "SELECT student_id FROM students WHERE student_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$student_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_student_ids = [];
            while ($row = $result->fetch_assoc()) {
                $existing_student_ids[] = $row['student_id'];
            }
            $stmt->close();
            // Count invalids
            $invalid = count($student_ids) - count($existing_student_ids);
            // Only keep student_ids that exist
            $student_ids = $existing_student_ids;
        }
        $assigned = 0;
        $already = 0;
        $new_assignments = [];
        // Check which assignments already exist
        if (count($student_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $types = 'i' . str_repeat('s', count($student_ids));
            $check_sql = "SELECT student_id FROM student_subjects WHERE subject_id = ? AND student_id IN ($placeholders)";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param($types, $subject_id, ...$student_ids);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $existing_ids = [];
            while ($row = $result->fetch_assoc()) {
                $existing_ids[] = $row['student_id'];
            }
            $check_stmt->close();
            foreach ($student_ids as $student_id) {
                if (!in_array($student_id, $existing_ids)) {
                    $new_assignments[] = $student_id;
                } else {
                    $already++;
                }
            }
        }
        // Multi-row insert for new assignments
        if (count($new_assignments) > 0) {
            $insert_sql = "INSERT INTO student_subjects (student_id, subject_id) VALUES ";
            $insert_values = [];
            $insert_types = '';
            foreach ($new_assignments as $student_id) {
                $insert_sql .= '(?,?),';
                $insert_values[] = $student_id;
                $insert_values[] = $subject_id;
                $insert_types .= 'si';
            }
            $insert_sql = rtrim($insert_sql, ',');
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param(str_repeat('si', count($new_assignments)), ...$insert_values);
            if ($insert_stmt->execute()) {
                $assigned = count($new_assignments);
            } else {
                $error = "MySQL Error: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        if ($assigned > 0) {
            $message = "$assigned student(s) assigned successfully!";
        }
        if ($already > 0) {
            $error = "$already student(s) already had this subject.";
        }
        if ($invalid > 0) {
            $error .= " ($invalid student(s) were invalid and not assigned.)";
        }
    }
}

// Handle teacher assignment to subjects
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teacher'])) {
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    
    if (empty($subject_id) || empty($teacher_id)) {
        $error = "Please select both subject and teacher!";
    } else {
        $stmt = $conn->prepare("UPDATE subjects SET teacher_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $teacher_id, $subject_id);
        
        if ($stmt->execute()) {
            $message = "Teacher assigned to subject successfully!";
        } else {
            $error = "Error assigning teacher: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle remove subject assignment
if (isset($_GET['remove_assignment'])) {
    $assignment_id = intval($_GET['remove_assignment']);
    $delete_stmt = $conn->prepare("DELETE FROM student_subjects WHERE id = ?");
    $delete_stmt->bind_param("i", $assignment_id);
    
    if ($delete_stmt->execute()) {
        $message = "Subject assignment removed successfully!";
    } else {
        $error = "Error removing assignment: " . $conn->error;
    }
    $delete_stmt->close();
}

// Get all students
$students = $conn->query("SELECT * FROM students WHERE course = 'BSIS' ORDER BY year_level, section, name");

// Build studentOptions array for JS (year-section => students)
$studentOptions = [];
if ($students) {
    $students->data_seek(0);
    while ($student = $students->fetch_assoc()) {
        $key = $student['year_level'] . '-' . $student['section'];
        if (!isset($studentOptions[$key])) $studentOptions[$key] = [];
        $studentOptions[$key][] = [
            'id' => $student['student_id'],
            'name' => $student['name'],
            'year' => $student['year_level'],
            'section' => $student['section']
        ];
    }
    $students->data_seek(0); // Reset pointer for later use
}

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_code");

// Get all teachers
$teachers = $conn->query("SELECT * FROM teachers ORDER BY name");

// Get current assignments grouped by subject and irregular
$subject_assignments = [];
$irregular_assignments = [];
$assignments = $conn->query("
    SELECT ss.id, s.student_id, s.name as student_name, s.section, s.year_level,
           sub.subject_code, sub.subject_name, t.name as teacher_name
    FROM student_subjects ss
    JOIN students s ON ss.student_id = s.student_id
    JOIN subjects sub ON ss.subject_id = sub.id
    LEFT JOIN teachers t ON sub.teacher_id = t.id
    ORDER BY sub.subject_code, s.year_level, s.section, s.name
");
while ($row = $assignments->fetch_assoc()) {
    if (strtolower($row['section']) === 'irregular') {
        $irregular_assignments[] = $row;
    } else {
        $subj_key = $row['subject_code'] . ' - ' . $row['subject_name'];
        if (!isset($subject_assignments[$subj_key])) $subject_assignments[$subj_key] = [
            'teacher' => $row['teacher_name'],
            'students' => []
        ];
        $subject_assignments[$subj_key]['students'][] = $row;
    }
}
?>
<div class="container">
    <div class="manage-container">
        <div class="manage-header">
            <h2>Subject Assignment Management</h2>
            <div class="header-actions">
                <a href="add_subject.php" class="btn-add">Add New Subject</a>
                <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <!-- Assign Subject to Student -->
        <div class="assignment-section">
            <h3>Assign Subject to Student</h3>
            <form method="POST" class="assignment-form" id="assignForm">
                <input type="hidden" name="assign_subject" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="filter_year">Year *</label>
                        <select id="filter_year" required>
                            <option value="">Select Year</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_section">Section *</label>
                        <select id="filter_section" required>
                            <option value="">Select Section</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                        </select>
                    </div>
                    <div class="form-group" style="min-width:220px;">
                        <label>Students *</label>
                        <div style="margin-bottom: 8px;">
                            <input type="checkbox" id="select_all_students"> <label for="select_all_students" style="font-weight:normal;">Select All</label>
                        </div>
                        <div id="student_checkbox_list" style="max-height:180px;overflow-y:auto;border:1px solid #e1e5e9;border-radius:8px;padding:8px;background:#fff;min-width:200px;">
                            <span style="color:#888;">Select year and section</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php 
                            $subjects->data_seek(0);
                            while ($subject = $subjects->fetch_assoc()): 
                            ?>
                                <option value="<?= $subject['id'] ?>">
                                    <?= htmlspecialchars($subject['subject_code']) ?> - <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-primary">Assign Subject</button>
                    </div>
                </div>
            </form>
        </div>
        <!-- Assign Teacher to Subject -->
        <div class="assignment-section">
            <h3>Assign Teacher to Subject</h3>
            <form method="POST" class="assignment-form">
                <input type="hidden" name="assign_teacher" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="teacher_subject_id">Subject *</label>
                        <select id="teacher_subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php 
                            $subjects->data_seek(0);
                            while ($subject = $subjects->fetch_assoc()): 
                            ?>
                                <option value="<?= $subject['id'] ?>">
                                    <?= htmlspecialchars($subject['subject_code']) ?> - <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="teacher_id">Faculty *</label>
                        <select id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php 
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?= $teacher['id'] ?>">
                                    <?= htmlspecialchars($teacher['teacher_id']) ?> - <?= htmlspecialchars($teacher['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-primary">Assign Faculty</button>
                    </div>
                </div>
            </form>
        </div>
        <!-- Current Assignments -->
        <div class="assignment-section">
            <h3>📋 Current Subject Assignments</h3>
            <div class="table-container">
                <style>
                .subject-folder { margin-bottom: 18px; border-radius: 10px; border: 1px solid #e0eafc; background: #f8fafc; }
                .subject-header { cursor: pointer; padding: 16px 24px; font-weight: 600; font-size: 1.1rem; color: #003366; background: #e0eafc; border-radius: 10px 10px 0 0; display: flex; align-items: center; justify-content: space-between; }
                .subject-header:hover { background: #d0e2f7; }
                .subject-content { display: none; padding: 0 0 18px 0; }
                .subject-table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 0; }
                .subject-table th, .subject-table td { padding: 10px 12px; border: 1px solid #e0eafc; text-align: left; }
                .subject-table th { background: #f3f6fd; font-weight: 600; }
                .irregular-folder { border: 2px solid #f5576c; background: #fff0f3; }
                .irregular-header { color: #c82333; background: #ffe3e8; }
                </style>
                <script>
                function toggleSubjectContent(id) {
                    var el = document.getElementById(id);
                    if (el.style.display === 'none' || el.style.display === '') {
                        el.style.display = 'block';
                    } else {
                        el.style.display = 'none';
                    }
                }
                </script>
                <?php if (count($subject_assignments) > 0): ?>
                    <?php $sfolder = 0; foreach ($subject_assignments as $subj => $info): $sfolder++; ?>
                        <div class="subject-folder">
                            <div class="subject-header" onclick="toggleSubjectContent('subject-content-<?= $sfolder ?>')">
                                <span><?= htmlspecialchars($subj) ?></span>
                                <span style="font-size:0.95em;color:#0074D9;">
                                    <?= $info['teacher'] ? htmlspecialchars($info['teacher']) : '<span style=\'color:#888\'>No teacher</span>' ?>
                                </span>
                            </div>
                            <div class="subject-content" id="subject-content-<?= $sfolder ?>">
                                <table class="subject-table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Year</th>
                                            <th>Section</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($info['students'] as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['student_id']) ?></td>
                                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                                <td><?= htmlspecialchars($row['year_level']) ?></td>
                                                <td><?= htmlspecialchars($row['section']) ?></td>
                                                <td class="actions">
                                                    <a href="?remove_assignment=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Remove this subject assignment?')">Remove</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (count($subject_assignments) === 0 && count($irregular_assignments) === 0): ?>
                    <div class="no-data" style="padding:24px;text-align:center;">No subject assignments found.</div>
                <?php endif; ?>
                <?php if (count($irregular_assignments) > 0): ?>
                    <div class="subject-folder irregular-folder">
                        <div class="subject-header irregular-header" onclick="toggleSubjectContent('irregular-folder')">
                            <span>Irregular Students</span>
                            <span style="font-size:0.95em;">All Years</span>
                        </div>
                        <div class="subject-content" id="irregular-folder">
                            <table class="subject-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($irregular_assignments as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                                            <td><?= htmlspecialchars($row['year_level']) ?></td>
                                            <td><?= htmlspecialchars($row['subject_code'] . ' - ' . $row['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($row['teacher_name'] ?? 'No teacher') ?></td>
                                            <td class="actions">
                                                <a href="?remove_assignment=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Remove this subject assignment?')">Remove</a>
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
    </div>
</div>

<script>
// Student options by year-section
const studentOptions = <?php echo json_encode($studentOptions); ?>;
const studentCheckboxList = document.getElementById('student_checkbox_list');
const yearSelect = document.getElementById('filter_year');
const sectionSelect = document.getElementById('filter_section');
const selectAllCheckbox = document.getElementById('select_all_students');

function updateStudentCheckboxList() {
    const year = yearSelect.value;
    const section = sectionSelect.value;
    studentCheckboxList.innerHTML = '';
    if (year && section) {
        const key = year + '-' + section;
        if (studentOptions[key]) {
            studentOptions[key].forEach(stu => {
                const label = document.createElement('label');
                label.style.display = 'block';
                label.style.marginBottom = '4px';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.name = 'student_ids[]';
                cb.value = stu.id;
                label.appendChild(cb);
                label.appendChild(document.createTextNode(' ' + stu.id + ' - ' + stu.name));
                studentCheckboxList.appendChild(label);
            });
        } else {
            studentCheckboxList.innerHTML = '<span style="color:#888;">No students found for this year/section</span>';
        }
    } else {
        studentCheckboxList.innerHTML = '<span style="color:#888;">Select year and section</span>';
    }
    // Reset select all checkbox
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
}
yearSelect.addEventListener('change', updateStudentCheckboxList);
sectionSelect.addEventListener('change', updateStudentCheckboxList);

// Select All functionality
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = studentCheckboxList.querySelectorAll('input[type="checkbox"][name="student_ids[]"]');
        checkboxes.forEach(cb => { cb.checked = selectAllCheckbox.checked; });
    });
    // When student checkboxes change, update select all state
    studentCheckboxList.addEventListener('change', function(e) {
        if (e.target && e.target.type === 'checkbox' && e.target.name === 'student_ids[]') {
            const checkboxes = studentCheckboxList.querySelectorAll('input[type="checkbox"][name="student_ids[]"]');
            const checked = studentCheckboxList.querySelectorAll('input[type="checkbox"][name="student_ids[]"]:checked');
            selectAllCheckbox.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
        }
    });
}
</script>

<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(to right, #74ebd5, #ACB6E5);
        color: #333;
    }

    .container {
        max-width: 1300px;
        margin: 30px auto;
        padding: 0 24px;
        margin-left: 40rem; /* moved to ~40rem per request */
        box-sizing: border-box;
    }
    @media screen and (max-width:900px) {
        .container { margin-left: 0; padding: 0 12px; }
    }

    .manage-container {
        background: white;
        border-radius: 18px;
        padding: 36px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .manage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 15px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .manage-header h2 {
        margin: 0;
        color: #003366;
        font-size: 2rem;
        font-weight: 800;
    }

    .header-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn-add,
    .btn-back {
        text-decoration: none;
        padding: 14px 24px;
        border-radius: 10px;
        font-weight: 700;
        transition: all 0.3s ease;
        font-size: 1.05rem;
    }

    .btn-add {
        background: #28a745;
        color: white;
        border: 1px solid #28a745;
    }

    .btn-add:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .btn-back {
        background: #0074D9;
        color: white;
        border: 1px solid #0074D9;
    }

    .btn-back:hover {
        background: #005fa3;
        transform: translateY(-2px);
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .assignment-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }

    .assignment-section h3 {
        margin: 0 0 20px 0;
        color: #003366;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .assignment-form {
        margin-bottom: 0;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group select {
        width: 100%;
        padding: 14px;
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        font-size: 18px;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-group select:focus {
        outline: none;
        border-color: #0074D9;
        box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
    }

    .btn-primary {
        background: #0074D9;
        color: white;
        border: none;
        padding: 14px 26px;
        border-radius: 10px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #005fa3;
        transform: translateY(-2px);
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    thead {
        background-color: #0074D9;
        color: white;
    }

    th, td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    th {
        font-weight: 600;
    }

    .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-delete {
        background: #dc3545;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-1px);
    }

    .no-data {
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }

    .no-teacher {
        color: #6c757d;
        font-style: italic;
    }

    @media screen and (max-width: 768px) {
        .container {
            margin: 15px auto;
        }

        .manage-container {
            padding: 20px;
        }

        .manage-header {
            flex-direction: column;
            text-align: center;
        }

        .header-actions {
            justify-content: center;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .actions {
            flex-direction: column;
        }
    }
</style>

<?php include 'includes/footer.php'; ?> 