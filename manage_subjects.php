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

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $delete_stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $message = "Subject deleted successfully!";
    } else {
        $error = "Error deleting subject: " . $conn->error;
    }
    $delete_stmt->close();
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_subject'])) {
    $edit_id = $_POST['edit_id'];
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $teacher_id = trim($_POST['teacher_id']);
    
    if (empty($subject_code) || empty($subject_name)) {
        $error = "Subject Code and Subject Name are required!";
    } else {
        // Check if subject_code already exists for other subjects
        $check = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
        $check->bind_param("si", $subject_code, $edit_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Subject Code already exists!";
        } else {
            $teacher_id = empty($teacher_id) ? null : $teacher_id;
            $update_stmt = $conn->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, teacher_id = ? WHERE id = ?");
            $update_stmt->bind_param("ssii", $subject_code, $subject_name, $teacher_id, $edit_id);
            
            if ($update_stmt->execute()) {
                $message = "Subject updated successfully!";
            } else {
                $error = "Error updating subject: " . $conn->error;
            }
            $update_stmt->close();
        }
        $check->close();
    }
}

// Get all subjects with teacher information
$subjects = $conn->query("
    SELECT s.*, t.name as teacher_name, t.teacher_id as teacher_code 
    FROM subjects s 
    LEFT JOIN teachers t ON s.teacher_id = t.id 
    ORDER BY s.subject_code
");

// Get all teachers for dropdown
$teachers = $conn->query("SELECT id, teacher_id, name, department FROM teachers ORDER BY name");
?>

<style>
    .app-content { margin-left: 20rem; padding: 3.5rem 2rem; min-height:100vh; box-sizing:border-box; background: linear-gradient(90deg,#ebf8ff 0%, #f0f4ff 100%); }
    .app-container { max-width: 1100px; margin: 0 auto; background: #fff; border-radius: 1rem; padding: 2.5rem; box-shadow: 0 12px 40px rgba(2,6,23,0.06); }
    @media (max-width:900px){ .app-content{margin-left:0;padding:2rem;} .app-container{padding:1rem;} }
</style>
<div class="app-content">
    <div class="app-container">
        <div class="flex flex-col sm:flex-row justify-between items-center border-b-2 border-blue-100 pb-5 mb-10 gap-5">
            <h2 class="text-3xl font-bold text-blue-900 flex items-center gap-3">
                Manage Subjects
            </h2>
            <a href="dashboard.php" class="text-blue-600 font-semibold px-5 py-3 text-lg rounded-lg border border-blue-600 hover:bg-blue-600 hover:text-white transition">← Back to Dashboard</a>
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

        <div class="overflow-x-auto">
            <table class="w-full border border-blue-200 rounded-lg overflow-hidden">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="px-5 py-3 text-lg">Subject Code</th>
                        <th class="px-5 py-3 text-lg">Subject Name</th>
                        <th class="px-5 py-3 text-lg">Teacher</th>
                        <th class="px-5 py-3 text-lg">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subjects->num_rows > 0): ?>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <tr class="border-b border-blue-100 hover:bg-blue-50 text-lg">
                                <td class="px-5 py-3"><?= htmlspecialchars($subject['subject_code']) ?></td>
                                <td class="px-5 py-3"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                <td class="px-5 py-3">
                                    <?php if ($subject['teacher_name']): ?>
                                        <?= htmlspecialchars($subject['teacher_name']) ?> 
                                        <small>(<?= htmlspecialchars($subject['teacher_code']) ?>)</small>
                                    <?php else: ?>
                                        <span class="no-teacher">No teacher assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 actions">
                                    <button class="btn-edit" onclick="editSubject(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['subject_code']) ?>', '<?= htmlspecialchars($subject['subject_name']) ?>', '<?= $subject['teacher_id'] ?? '' ?>')">Edit</button>
                                    <a href="?delete=<?= $subject['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">No subjects found. <a href="add_subject.php">Add your first subject</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Subject</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" class="edit-form">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="edit_subject" value="1">
            
            <div class="form-group">
                <label for="edit_subject_code">Subject Code *</label>
                <input type="text" id="edit_subject_code" name="subject_code" required>
            </div>

            <div class="form-group">
                <label for="edit_subject_name">Subject Name *</label>
                <input type="text" id="edit_subject_name" name="subject_name" required>
            </div>

            <div class="form-group">
                <label for="edit_teacher_id">Assigned Teacher</label>
                <select id="edit_teacher_id" name="teacher_id">
                    <option value="">Select Teacher (Optional)</option>
                    <?php 
                    $teachers->data_seek(0); // Reset pointer to beginning
                    while ($teacher = $teachers->fetch_assoc()): 
                    ?>
                        <option value="<?= $teacher['id'] ?>">
                            <?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacher['teacher_id']) ?>) - <?= htmlspecialchars($teacher['department']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(to right, #74ebd5, #ACB6E5);
        color: #333;
    }

    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .manage-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
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
    }

    .header-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn-add,
    .btn-back {
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
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

    .no-teacher {
        color: #6c757d;
        font-style: italic;
    }

    .actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .btn-edit,
    .btn-delete {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
        text-align: center;
        width: 100%;
    }

    .btn-edit {
        background: #ffc107;
        color: #212529;
    }

    .btn-edit:hover {
        background: #e0a800;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: #dc3545;
        color: white;
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

    .no-data a {
        color: #0074D9;
        text-decoration: none;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        border-bottom: 2px solid #f0f0f0;
    }

    .modal-header h3 {
        margin: 0;
        color: #003366;
    }

    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #000;
    }

    .edit-form {
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #0074D9;
        box-shadow: 0 0 0 3px rgba(0, 116, 217, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-primary,
    .btn-secondary {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        flex: 1;
    }

    .btn-primary {
        background: #0074D9;
        color: white;
    }

    .btn-primary:hover {
        background: #005fa3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #545b62;
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

        .actions {
            flex-direction: column;
        }

        .modal-content {
            margin: 10% auto;
            width: 95%;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<script>
    function editSubject(id, subjectCode, subjectName, teacherId) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_subject_code').value = subjectCode;
        document.getElementById('edit_subject_name').value = subjectName;
        document.getElementById('edit_teacher_id').value = teacherId;
        
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>