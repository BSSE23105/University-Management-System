<?php

require_once __DIR__ . '/database_setup.php';
session_start();
if (!isset($error)) {
    $error = '';
}

$showSelection = true;
$showAdminForm = false;
$showFacultyForm = false;
$showStudentForm = false;



if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'faculty') {
    $name = $_POST['name'];
    $faculty_id = $_POST['faculty_id'];
    $password = $_POST['password'];

    // Check if input is numeric (ID) or email
    $field = is_numeric($faculty_id) ? 'id' : 'email';
    
    if (!isset($conn) || $conn->connect_error) {
        die("Database connection error: " . ($conn->connect_error ?? "Connection not established"));
    }

    $sql = "SELECT id, name, email 
            FROM faculty 
            WHERE name = ? 
            AND $field = ? 
            AND password = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Database error. Please try again later.");
    }

    $stmt->bind_param("sss", $name, $faculty_id, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $_SESSION['faculty'] = $res->fetch_assoc();
        header("Location: faculty_dashboard.php"); 
        exit;
    } else {
        $error = 'Invalid name, faculty ID/email or password.';
        $showFacultyForm = true;
        $showSelection = false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'student') {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];

    // Check if input is numeric (ID) or email
    $field = is_numeric($student_id) ? 'id' : 'email';
    
    if (!isset($conn) || $conn->connect_error) {
    die("Database connection error: " . ($conn->connect_error ?? "Connection not established"));
}

    $sql = "SELECT id, name, email, fsc_marks, matric_marks 
        FROM students 
        WHERE name = ? 
        AND $field = ? 
        AND password = ?";
        
$stmt = $conn->prepare($sql);

// Add error check for prepare
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Database error. Please try again later.");
}


    $stmt->bind_param("sss", $name, $student_id, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $_SESSION['student'] = $res->fetch_assoc();
        header("Location: student_dashboard.php");
        exit;
    } else {
    $error = 'Invalid name, student ID/email or password.';
    $showStudentForm = true; // Use the new flag
    $showSelection = false;
    }
}






// bring in your DB connection/schema-setup (make sure it no longer closes $conn)


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'admin') {
    $name     = $_POST['name'];
    $email    = $_POST['username'];
    $password = $_POST['password'];

    // prepare & execute (now matching name+email+password)
    $stmt = $conn->prepare(
      "SELECT id, name
         FROM admins
        WHERE name = ?
          AND email = ?
          AND password = ?"
    );
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $_SESSION['admin'] = $res->fetch_assoc();
        header("Location: src/admin.php");
        exit;
    } else {
        $error = 'Invalid name, email or password.';
    }

        // After processing, set visibility flags
    $showAdminForm = true; // Always show admin form after submission
    $showSelection = false; // Hide role selection
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ITU Portal Login</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      /* ITU Branding */
      .itu-primary {
        color: #0195c3;
      }
      .itu-accent {
        background-color: #f7941d;
      }
      /* Animations */
      .fade-in {
        animation: fadeIn 0.6s ease-in forwards;
      }
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    </style>
  </head>
  <body
    class="min-h-screen bg-gray-100 flex items-center justify-center"
    style="
      background-image: url('data:image/jpeg;base64,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');
      background-size: 100px;
    "
  >
    <!-- Container -->
    <div id="container" class="w-full max-w-sm px-4">

    
      <!-- 1. Role Selection Card -->
    
<div id="selection" class="bg-white bg-opacity-90 backdrop-blur-md rounded-2xl shadow-xl p-8 w-full text-center mt-12 fade-in <?= $showSelection ? '' : 'hidden' ?>">
        <img
          src="./assets/ITU-Lahore-Punjab.jpg"
          alt="ITU Logo"
          class="mx-auto h-16 mb-6"
        />
        <h1 class="text-2xl font-bold itu-primary mb-4">Welcome to ITU Portal</h1>
        <p class="text-gray-700 mb-8">Please select your role to continue</p>
        <div class="space-y-4">
          <button
            onclick="showForm('admin')"
            class="block w-full itu-accent text-white py-2 rounded-lg hover:opacity-90 transition"
          >
            Login as Admin
          </button>
          <button
            onclick="showForm('faculty')"
            class="block w-full itu-primary border-2 border-itu-primary py-2 rounded-lg hover:bg-itu-primary hover:text-white transition"
          >
            Login as Faculty
          </button>
          <button
            onclick="showForm('student')"
            class="block w-full text-gray-800 bg-gray-200 py-2 rounded-lg hover:bg-gray-300 transition"
          >
            Login as Student
          </button>
        </div>
      </div>

      <!-- 2. Admin Login Form -->
    <div id="form-admin" class="<?= $showAdminForm ? 'fade-in' : 'hidden' ?> bg-white bg-opacity-90 backdrop-blur-md rounded-2xl shadow-xl p-8 w-full text-center mt-12">
        <img
          src="./assets/ITU-Lahore-Punjab.jpg"
          alt="ITU Logo"
          class="mx-auto h-16 mb-6"
        />
        <h1 class="text-2xl font-bold itu-primary mb-4">Admin Login</h1>
        <p class="text-gray-700 mb-6">Please enter your admin credentials</p>

        <?php if($error): ?>
          <div class="text-red-600 mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5 text-left">
          <input type="hidden" name="role" value="admin">

          <div class="flex flex-col">
            <label for="admin-name" class="mb-1 text-gray-600">Name</label>
            <input
              type="text"
              id="admin-name"
              name="name"
              required
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary"
              placeholder="e.g. John Doe"
            />
          </div>

          <div class="flex flex-col">
            <label for="admin-username" class="mb-1 text-gray-600">Email</label>
            <input
              type="email"
              id="admin-username"
              name="username"
              required
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary"
              placeholder="e.g. admin@itu.edu.pk"
            />
          </div>

          <div class="flex flex-col relative">
            <label for="admin-password" class="mb-1 text-gray-600">Password</label>
            <input
              type="password"
              id="admin-password"
              name="password"
              required
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary pr-10"
              placeholder="••••••••"
            />
            <button
              type="button"
              class="absolute right-3 top-9 text-gray-500 hover:text-gray-700"
              onclick="togglePassword('admin-password', this)"
              aria-label="Show or hide password"
            >
              👁
            </button>
          </div>

          <button
            type="submit"
            class="w-full itu-accent text-white py-2 rounded-lg hover:opacity-90 transition font-medium"
          >
            Sign In as Admin
          </button>
        </form>

        <p class="mt-6 text-center">
          <button
            onclick="goBack()"
            class="text-sm itu-primary hover:underline"
          >
            ← Back to Role Selection
          </button>
        </p>
      </div>


      <!-- 3. Faculty Login Form -->
      <div
        id="form-faculty"
        class="hidden bg-white bg-opacity-90 backdrop-blur-md rounded-2xl shadow-xl p-8 w-full text-center mt-12 fade-in"
      >
        <img
          src="./assets/ITU-Lahore-Punjab.jpg"
          alt="ITU Logo"
          class="mx-auto h-16 mb-6"
        />
        <h1 class="text-2xl font-bold itu-primary mb-4">Faculty Login</h1>
        <p class="text-gray-700 mb-6">Enter your faculty credentials</p>
       <form action="" method="POST" class="space-y-5 text-left">
  <input type="hidden" name="role" value="faculty">

          <!-- New: Name Field -->
           <div class="flex flex-col">
    <label for="faculty-name" class="mb-1 text-gray-600">Name</label>
    <input type="text" id="faculty-name" name="name" required class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary" placeholder="e.g. Jane Smith">
  </div>
  
  <!-- Change name to faculty_id -->
  <div class="flex flex-col">
    <label for="faculty-id" class="mb-1 text-gray-600">
      Faculty ID or Email
    </label>
    <input type="text" id="faculty-id" name="faculty_id" required class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary" placeholder="e.g. f12345 or you@itu.edu.pk">
  </div>
  
  <!-- Keep password field -->
  <div class="flex flex-col relative">
    <label for="faculty-password" class="mb-1 text-gray-600">
      Password
    </label>
    <input type="password" id="faculty-password" name="password" required class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary pr-10" placeholder="••••••••">

            <button
              type="button"
              class="absolute right-3 top-9 text-gray-500 hover:text-gray-700"
              onclick="togglePassword('faculty-password', this)"
              aria-label="Show or hide password"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 
                     5c4.478 0 8.268 2.943 9.542 7-1.274 
                     4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                />
              </svg>
            </button>
          </div>
          <button type="submit" class="w-full itu-accent text-white py-2 rounded-lg hover:opacity-90 transition font-medium">
    Sign In as Faculty
  </button>
        </form>
        <p class="mt-6 text-center">
          <button
            onclick="goBack()"
            class="text-sm itu-primary hover:underline"
          >
            ← Back to Role Selection
          </button>
        </p>
      </div>

      <!-- 4. Student Login Form -->
      <div id="form-student" class="<?= $showStudentForm ? 'fade-in' : 'hidden' ?> bg-white bg-opacity-90 backdrop-blur-md rounded-2xl shadow-xl p-8 w-full text-center mt-12">
        <img
          src="./assets/ITU-Lahore-Punjab.jpg"
          alt="ITU Logo"
          class="mx-auto h-16 mb-6"
        />
        <h1 class="text-2xl font-bold itu-primary mb-4">Student Login</h1>
        <p class="text-gray-700 mb-6">Enter your student credentials</p>



    <form action="" method="POST" class="space-y-5 text-left">
  <!-- Add this hidden input -->
  <input type="hidden" name="role" value="student">
  
  <!-- Keep ALL your existing styling and fields - they're perfect! -->
  <div class="flex flex-col">
    <label for="student-name" class="mb-1 text-gray-600">
      Name
    </label>
    <input
      type="text"
      id="student-name"
      name="name"
      required
      class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary"
      placeholder="e.g. Ali Khan"
    />
  </div>
  
  <div class="flex flex-col">
    <label for="student-id" class="mb-1 text-gray-600">
      Student ID or Email
    </label>
    <input
      type="text"
      id="student-id"
      name="student_id"
      required
      class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary"
      placeholder="e.g. s123456 or you@itu.edu.pk"
    />
  </div>
  
  <div class="flex flex-col relative">
    <label for="student-password" class="mb-1 text-gray-600">
      Password
    </label>
    <input
      type="password"
      id="student-password"
      name="password"
      required
      class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-itu-primary pr-10"
      placeholder="••••••••"
    />
    <button
      type="button"
      class="absolute right-3 top-9 text-gray-500 hover:text-gray-700"
      onclick="togglePassword('student-password', this)"
      aria-label="Show or hide password"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M2.458 12C3.732 7.943 7.523 5 12 
            5c4.478 0 8.268 2.943 9.542 7-1.274 
            4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
        />
      </svg>
    </button>
  </div>
  
  <button
    type="submit"
    class="w-full itu-accent text-white py-2 rounded-lg hover:opacity-90 transition font-medium"
  >
    Sign In as Student
  </button>
</form>
        <p class="mt-6 text-center">
          <button
            onclick="goBack()"
            class="text-sm itu-primary hover:underline"
          >
            ← Back to Role Selection
          </button>
        </p>
      </div>
    </div>

    <!-- JavaScript to toggle views and password visibility -->
    <script>
    function showForm(role) {
    // Hide role selection
    document.getElementById('selection').classList.add('hidden');
    
    // Hide all forms
    ['admin', 'faculty', 'student'].forEach(r => {
        const el = document.getElementById('form-' + r);
        if (el) el.classList.add('hidden');
    });
    
    // Show chosen form
    const chosen = document.getElementById('form-' + role);
    if (chosen) {
        chosen.classList.remove('hidden');
        chosen.classList.remove('fade-in');
        void chosen.offsetWidth;
        chosen.classList.add('fade-in');
    }
}

      function goBack() {
    // Show role selection
    const sel = document.getElementById('selection');
    sel.classList.remove('hidden');
    sel.classList.remove('fade-in');
    void sel.offsetWidth;
    sel.classList.add('fade-in');
    
    // Hide all forms
    ['admin', 'faculty', 'student'].forEach(r => {
        const el = document.getElementById('form-' + r);
        if (el) el.classList.add('hidden');
    });
}
      function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        if (!input) return;
        if (input.type === "password") {
          input.type = "text";
          btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.973
                   9.973 0 012.223-3.607m3.56-2.66A9.957 9.957 0 0112 5c4.478 
                   0 8.268 2.943 9.542 7a10.05 10.05 0 01-1.163 2.36M15 12a3 
                   3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18" />
            </svg>`;
        } else {
          input.type = "password";
          btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 
                   5c4.478 0 8.268 2.943 9.542 7-1.274 
                   4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>`;
        }
      }
    </script>
  </body>
</html>
