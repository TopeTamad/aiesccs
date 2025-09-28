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
    
    // Check if teacher has assigned subjects
    $check_subjects = $conn->prepare("SELECT COUNT(*) as count FROM subjects WHERE teacher_id = ?");
    $check_subjects->bind_param("i", $delete_id);
    $check_subjects->execute();
    $subject_count = $check_subjects->get_result()->fetch_assoc()['count'];
    
    if ($subject_count > 0) {
        $error = "Cannot delete teacher. They have assigned subjects. Please reassign or delete the subjects first.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = "Teacher deleted successfully!";
        } else {
            $error = "Error deleting teacher: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $check_subjects->close();
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_teacher'])) {
    $edit_id = $_POST['edit_id'];
    $teacher_id = trim($_POST['teacher_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = 'College of Computing Studies'; // Always fixed
    $new_password = trim($_POST['new_password']);
    // Profile pic handling: may be an existing filename or uploaded file
    $existing_profile_pic = isset($_POST['existing_profile_pic']) ? trim($_POST['existing_profile_pic']) : '';
    
    if (empty($email) || empty($name)) {
        $error = "Email and Name are required!";
    } else {
        // Get current teacher email before making changes
        $old_email = '';
        $old_email_stmt = $conn->prepare("SELECT email FROM teachers WHERE id = ?");
        $old_email_stmt->bind_param("i", $edit_id);
        $old_email_stmt->execute();
        $row = $old_email_stmt->get_result()->fetch_assoc();
        if ($row) {
            $old_email = $row['email'];
        }
        $old_email_stmt->close();

        // Check if email already exists for other users (exclude this teacher's current user row)
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'teacher' AND email != ?");
        $check->bind_param("ss", $email, $old_email);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Update teacher info
                    // Handle profile picture upload if provided
                    $profile_pic_to_save = $existing_profile_pic;
                    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                        // fetch current pic to delete
                        $cur = $conn->prepare("SELECT profile_pic FROM teachers WHERE id = ?");
                        $cur->bind_param("i", $edit_id);
                        $cur->execute();
                        $cur_row = $cur->get_result()->fetch_assoc();
                        $cur->close();
                        if ($cur_row && !empty($cur_row['profile_pic']) && file_exists(__DIR__ . '/assets/img/' . $cur_row['profile_pic'])) {
                            @unlink(__DIR__ . '/assets/img/' . $cur_row['profile_pic']);
                        }
                        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                        $profile_pic_to_save = uniqid('teacher_', true) . '.' . $ext;
                        move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__ . '/assets/img/' . $profile_pic_to_save);
                    }

                    $update_stmt = $conn->prepare("UPDATE teachers SET teacher_id = ?, name = ?, email = ?, phone = ?, department = ?, profile_pic = ? WHERE id = ?");
                    $update_stmt->bind_param("ssssssi", $teacher_id, $name, $email, $phone, $department, $profile_pic_to_save, $edit_id);
                    $update_stmt->execute();
                // Update user account (use email)
                    // Update users table: use previously fetched $old_email
                    if (!empty($old_email)) {
                        $user_update_stmt = $conn->prepare("UPDATE users SET email = ?, name = ? WHERE email = ? AND role = 'teacher'");
                        $user_update_stmt->bind_param("sss", $email, $name, $old_email);
                        $user_update_stmt->execute();
                        $user_update_stmt->close();
                    }
                // Update password if provided
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'teacher'");
                        $password_update_stmt->bind_param("ss", $hashed_password, $email);
                        $password_update_stmt->execute();
                        $password_update_stmt->close();
                }
                // Commit transaction
                $conn->commit();
                $message = "Teacher updated successfully!" . (!empty($new_password) ? " Password has been updated." : "");
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = "Error updating teacher: " . $e->getMessage();
            }
            $update_stmt->close();
        }
        $check->close();
    }
}

// Handle reset password action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_teacher_id']) && isset($_POST['reset_new_password'])) {
    $reset_id = intval($_POST['reset_teacher_id']);
    $reset_new_password = trim($_POST['reset_new_password']);
    if (!empty($reset_new_password)) {
        // Get email
        $res = $conn->query("SELECT email FROM teachers WHERE id = $reset_id");
        if ($row = $res->fetch_assoc()) {
            $reset_email = $row['email'];
            $hashed_password = password_hash($reset_new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'teacher'");
            $update->bind_param("ss", $hashed_password, $reset_email);
            if ($update->execute()) {
                $message = "Password reset successfully for Email: <strong>" . htmlspecialchars($reset_email) . "</strong>.";
            } else {
                $error = "Failed to reset password.";
            }
            $update->close();
        } else {
            $error = "Teacher not found.";
        }
    } else {
        $error = "Please enter a new password.";
    }
}

// Get all teachers
$teachers = $conn->query("SELECT * FROM teachers ORDER BY name");
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
                Manage Teachers
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
            <!-- Example Table -->
            <table class="w-full border border-blue-200 rounded-lg overflow-hidden">
                <thead class="bg-blue-600 text-white">
            <tr>
                <th class="px-5 py-3 text-lg">Photo</th>
                <th class="px-5 py-3 text-lg">Teacher ID</th>
                        <th class="px-5 py-3 text-lg">Name</th>
                        <th class="px-5 py-3 text-lg">Email</th>
    
                        <th class="px-5 py-3 text-lg">Department</th>
                        <th class="px-5 py-3 text-lg">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teachers->num_rows > 0): ?>
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                            <tr class="border-b border-blue-100 hover:bg-blue-50 text-lg">
                                <td class="px-5 py-3">
                                    <?php if (!empty($teacher['profile_pic']) && file_exists(__DIR__ . '/assets/img/' . $teacher['profile_pic'])): ?>
                                        <img src="assets/img/<?= htmlspecialchars($teacher['profile_pic']) ?>" alt="thumb" style="width:48px;height:48px;object-fit:cover;border-radius:8px;" />
                                    <?php else: ?>
                                        <div style="width:48px;height:48px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#888">N/A</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3"><?= htmlspecialchars($teacher['teacher_id']) ?></td>
                                <td class="px-5 py-3"><?= htmlspecialchars($teacher['name']) ?></td>
                                <td class="px-5 py-3"><?= htmlspecialchars($teacher['email'] ?? '-') ?></td>
                                <td class="px-5 py-3"><?= htmlspecialchars($teacher['phone'] ?? '-') ?></td>
                                <td class="px-5 py-3"><?= htmlspecialchars($teacher['department'] ?? '-') ?></td>
                                <td class="px-5 py-3 actions">
                                    <button class="btn-edit" onclick="editTeacher(<?= $teacher['id'] ?>, '<?= htmlspecialchars($teacher['teacher_id']) ?>', '<?= htmlspecialchars($teacher['name']) ?>', '<?= htmlspecialchars($teacher['email'] ?? '') ?>', '<?= htmlspecialchars($teacher['phone'] ?? '') ?>', '<?= htmlspecialchars($teacher['department'] ?? '') ?>', '<?= htmlspecialchars($teacher['profile_pic'] ?? '') ?>')">Edit</button>
                                    <button class="btn-reset" onclick="openResetPasswordModal(<?= $teacher['id'] ?>, '<?= htmlspecialchars($teacher['teacher_id']) ?>', '<?= htmlspecialchars($teacher['name']) ?>')">Reset Password</button>
                                    <a href="?delete=<?= $teacher['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No teachers found. <a href="add_teacher.php">Add your first teacher</a></td>
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
            <h3>Edit Teacher</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data" class="edit-form">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="edit_teacher" value="1">
            <input type="hidden" name="existing_profile_pic" id="existing_profile_pic">
            
            <div class="form-group">
                <label for="edit_teacher_id">Teacher ID *</label>
                <input type="text" id="edit_teacher_id" name="teacher_id" required>
            </div>

            <div class="form-group">
                <label for="edit_name">Full Name *</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_email">Email *</label>
                <input type="email" id="edit_email" name="email" required>
                <small class="form-help">This will be used as the login email</small>
            </div>

            <div class="form-group">
                <label for="edit_phone">Phone Number</label>
                <input type="tel" id="edit_phone" name="phone">
            </div>

            <div class="form-group">
                <label for="edit_department">Department</label>
                <input type="text" id="edit_department" name="department" value="College of Computing Studies" readonly class="bg-gray-100">
                <input type="hidden" name="department" value="College of Computing Studies">
                <small class="form-help">Department is fixed to College of Computing Studies</small>
            </div>

            <div class="form-group">
                <label for="edit_profile_pic">Profile Picture</label>
                <input type="file" id="edit_profile_pic" name="profile_pic" accept="image/*">
                <small class="form-help">Upload a new picture to replace current one. Leave blank to keep existing.</small>
                <div id="currentPicPreview" style="margin-top:8px"></div>
            </div>

            <div class="form-group">
                <label for="edit_new_password">New Password (leave blank to keep current)</label>
                <input type="password" id="edit_new_password" name="new_password" placeholder="Enter new password">
                <small class="form-help">Only fill this if you want to change the password</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Teacher Password</h3>
            <span class="close" onclick="closeResetPasswordModal()">&times;</span>
        </div>
        <form method="POST" class="edit-form">
            <input type="hidden" name="reset_teacher_id" id="reset_teacher_id">
            <div class="form-group">
                <label for="reset_teacher_name">Teacher</label>
                <input type="text" id="reset_teacher_name" name="reset_teacher_name" readonly>
            </div>
            <div class="form-group">
                <label for="reset_new_password">New Password *</label>
                <input type="password" id="reset_new_password" name="reset_new_password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Reset Password</button>
                <button type="button" class="btn-secondary" onclick="closeResetPasswordModal()">Cancel</button>
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

    .actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn-edit,
    .btn-delete,
    .btn-reset {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s ease;
        text-align: center;
        min-width:88px;
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
        padding: 14px 26px;
        border: none;
        border-radius: 8px;
        font-size: 18px;
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

    /* Add style for btn-reset */
    .btn-reset {
        background: #17a2b8;
        color: white;
        border: 1px solid #17a2b8;
    }
    .btn-reset:hover {
        background: #138496;
        transform: translateY(-1px);
    }
</style>

<script>
    function editTeacher(id, teacherId, name, email, phone, department, profilePic) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_teacher_id').value = teacherId;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_department').value = department;
        document.getElementById('existing_profile_pic').value = profilePic || '';
        const preview = document.getElementById('currentPicPreview');
        if (profilePic) {
            preview.innerHTML = '<img src="assets/img/' + profilePic + '" style="width:96px;height:96px;object-fit:cover;border-radius:8px;" />';
        } else {
            preview.innerHTML = '<div style="width:96px;height:96px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#888">N/A</div>';
        }
        
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function openResetPasswordModal(id, teacherId, name) {
        document.getElementById('reset_teacher_id').value = id;
        document.getElementById('reset_teacher_name').value = teacherId + ' - ' + name;
        document.getElementById('reset_new_password').value = '';
        document.getElementById('resetPasswordModal').style.display = 'block';
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        const resetModal = document.getElementById('resetPasswordModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
        if (event.target == resetModal) {
            resetModal.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>