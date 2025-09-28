<?php
include 'includes/db.php';
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['verified'] != 1) {
            $error = "Your email is not yet verified. Please check your inbox.";
        } else if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row;
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid credentials!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
        /* Shared site utilities copied from index.php for consistent UI */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up { animation: fadeUp 700ms cubic-bezier(.2,.8,.2,1) both; }
        .animate-fade-up-delay { animation: fadeUp 900ms cubic-bezier(.2,.8,.2,1) 120ms both; }
        @media (prefers-reduced-motion: reduce) {
            .animate-fade-up, .animate-fade-up-delay { animation: none !important; }
        }
        .hero-card { width: 100%; max-width: 720px; margin-left: auto; margin-right: auto; }
        @media (max-width: 420px) {
            .hero-card { padding-left: 1rem; padding-right: 1rem; }
        }
        </style>
</head>
<body class="bg-gradient-to-br from-sky-100 via-sky-200 to-white min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md md:max-w-lg lg:max-w-xl border border-gray-100">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-r from-sky-500 to-sky-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md">
                <i class="fas fa-user-shield text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">MIS Admin</h1>
            <p class="text-gray-600 mt-2">Sign in to manage the system</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" id="email" name="email" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-transparent"
                       placeholder="Enter your email" required autofocus>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" id="password" name="password" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-transparent"
                       placeholder="Enter your password" required>
            </div>

            <button name="login" type="submit" 
                    class="w-full bg-gradient-to-r from-sky-500 to-sky-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-sky-600 hover:to-sky-700 transition duration-300 transform hover:scale-105 shadow-sm">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Use your email to access the dashboard
            </p>
        </div>

        <div class="mt-8 text-center">
            <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Main Page
            </a>
        </div>
    </div>
    <script>
        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html>