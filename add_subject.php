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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $teacher_id = trim($_POST['teacher_id']);
    
    // Validation
    if (empty($subject_code) || empty($subject_name)) {
        $error = "Subject Code and Subject Name are required!";
    } else {
        // Check if subject_code already exists
        $check = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
        $check->bind_param("s", $subject_code);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Subject Code already exists!";
        } else {
            // Insert new subject
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, teacher_id) VALUES (?, ?, ?)");
            $teacher_id = empty($teacher_id) ? null : $teacher_id;
            $stmt->bind_param("ssi", $subject_code, $subject_name, $teacher_id);
            
            if ($stmt->execute()) {
                $message = "Subject added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error = "Error adding subject: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Get all teachers for dropdown
$teachers = $conn->query("SELECT id, teacher_id, name, department FROM teachers ORDER BY name");
?>

<style>
    .app-content { margin-left: 20rem; padding: 3rem 1.5rem; min-height:100vh; box-sizing:border-box; background: linear-gradient(90deg,#eaf6ff 0%, #f8f4ff 100%); }
    .app-container { max-width: 960px; margin: 0 auto; background: #fff; border-radius: 1rem; padding: 2.5rem; box-shadow: 0 12px 40px rgba(2,6,23,0.06); }
    @media (max-width:900px){ .app-content{margin-left:0;padding:1.25rem;} .app-container{padding:1rem;} }
</style>
<div class="app-content">
    <div class="app-container">
        <div class="flex flex-col sm:flex-row justify-between items-center border-b-2 border-blue-100 pb-5 mb-10 gap-5">
            <h2 class="text-3xl font-bold text-blue-900 flex items-center gap-3">
                <span>📚</span> Add New Subject
            </h2>
            <a href="dashboard.php" class="text-blue-600 font-semibold px-5 py-3 text-lg rounded-lg border border-blue-600 hover:bg-blue-600 hover:text-white transition">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 bg-green-50 border border-green-300 text-green-800 rounded-lg px-6 py-4 font-semibold text-center text-lg">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-300 text-red-800 rounded-lg px-6 py-4 font-semibold text-center text-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-7">
            <div>
                <label for="subject_code" class="block font-bold text-blue-800 mb-2 text-lg">Subject Code *</label>
                <input type="text" id="subject_code" name="subject_code" value="<?= htmlspecialchars($_POST['subject_code'] ?? '') ?>" required
                    class="w-full p-5 border-2 border-blue-200 rounded-xl text-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
            </div>

            <div>
                <label for="subject_name" class="block font-bold text-blue-800 mb-2 text-lg">Subject Name *</label>
                <input type="text" id="subject_name" name="subject_name" value="<?= htmlspecialchars($_POST['subject_name'] ?? '') ?>" required
                    class="w-full p-5 border-2 border-blue-200 rounded-xl text-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
            </div>

            <div>
                <label for="teacher_id" class="block font-bold text-blue-800 mb-2 text-lg">Assigned Teacher</label>
                <select id="teacher_id" name="teacher_id"
                    class="w-full p-5 border-2 border-blue-200 rounded-xl text-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    <option value="">Select Teacher (Optional)</option>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                        <option value="<?= $teacher['id'] ?>" <?= ($_POST['teacher_id'] ?? '') == $teacher['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacher['teacher_id']) ?>) - <?= htmlspecialchars($teacher['department']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-4 text-lg rounded-xl shadow-lg hover:bg-blue-700 transition">➕ Add Subject</button>
                <button type="reset" class="flex-1 bg-gray-400 text-white font-bold py-4 text-lg rounded-xl shadow-lg hover:bg-gray-500 transition">🔄 Reset</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>