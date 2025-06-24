<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: index.php");
    exit;
}

$student = $_SESSION['student'];
require 'src/config.php';

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = (int)$_POST['course_id'];
    $student_id = $student['id'];
    
    // Check if already enrolled
    $check_sql = "SELECT id FROM student_courses WHERE student_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "You are already enrolled in this course!";
    } else {
        // Enroll the student
        $enroll_sql = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
        $enroll_stmt = $conn->prepare($enroll_sql);
        $enroll_stmt->bind_param("ii", $student_id, $course_id);
        
        if ($enroll_stmt->execute()) {
            $success = "Successfully enrolled in the course!";
        } else {
            $error = "Error enrolling in course: " . $conn->error;
        }
    }
}

// Fetch available courses
$available_courses = [];
$sql = "SELECT c.id, c.course_code, c.course_name, f.name AS faculty_name 
        FROM courses c
        JOIN faculty f ON c.faculty_id = f.id
        WHERE c.status = 'active'
        ORDER BY c.course_code";
$result = $conn->query($sql);
if ($result) {
    $available_courses = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch enrolled courses
$enrolled_courses = [];
$student_id = $student['id'];
$sql = "SELECT c.id, c.course_code, c.course_name, f.name AS faculty_name 
        FROM student_courses sc
        JOIN courses c ON sc.course_id = c.id
        JOIN faculty f ON c.faculty_id = f.id
        WHERE sc.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $enrolled_courses = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .itu-primary { color: #0195c3; }
        .itu-accent { background-color: #f7941d; }
        .fade-in { animation: fadeIn 0.8s ease-in forwards; }
        .slide-up { animation: slideUp 0.8s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .course-card { transition: all 0.3s ease; }
        .course-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-lg md:block hidden flex flex-col">
        <div class="relative h-32">
            <img src="https://images.pexels.com/photos/4144177/pexels-photo-4144177.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=200&w=264" 
                 alt="Banner" class="w-full h-full">
            <div class="absolute bottom-0 left-0 p-4 flex items-center">
                <img src="https://images.pexels.com/photos/8473935/pexels-photo-8473935.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=64&w=64" 
                     alt="Avatar" class="h-12 w-12 rounded-full border-2 border-white">
                <div class="ml-3">
                    <p class="text-white font-semibold"><?= htmlspecialchars($student['name']) ?></p>
                    <p class="text-xs text-gray-200">Student</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto">
            <ul class="mt-4">
                <li class="mb-2">
                    <a href="student_dashboard.php" class="flex items-center px-4 py-2 hover:bg-gray-200 rounded">
                        <i class="fas fa-home itu-primary mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="enroll_courses.php" class="flex items-center px-4 py-2 bg-gray-200 rounded">
                        <i class="fas fa-book-open itu-primary mr-3"></i>
                        <span>Enroll in Courses</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="student_messages.php" class="flex items-center px-4 py-2 hover:bg-gray-200 rounded">
                        <i class="fas fa-envelope mr-3"></i>
                        <span>Messages</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t">
            <a href="logout.php" class="block w-full text-left text-red-600 hover:bg-gray-200 px-4 py-2 rounded">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top Navbar -->
        <header class="flex items-center justify-between bg-white shadow px-6 py-4">
            <div class="flex items-center">
                <button id="sidebarToggle" class="text-gray-500 md:hidden mr-4">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-2xl font-semibold itu-primary">Course Enrollment</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button onclick="toggleProfileMenu()">
                        <img src="https://images.pexels.com/photos/8473935/pexels-photo-8473935.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=40&w=40" 
                             alt="Avatar" class="h-8 w-8 rounded-full">
                    </button>
                    <div id="profileMenu" class="hidden absolute right-0 mt-2 bg-white border rounded shadow-lg">
                        <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 overflow-y-auto">
            <?php if(isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 fade-in">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 fade-in">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Available Courses Section -->
            <section class="mb-12 slide-up">
                <h2 class="text-2xl font-semibold mb-6 itu-primary border-b pb-2">
                    <i class="fas fa-book mr-2"></i>Available Courses
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($available_courses as $course): ?>
                        <div class="course-card bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl">
                            <div class="p-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="inline-block px-3 py-1 text-xs font-semibold text-white itu-accent rounded-full mb-2">
                                            <?= htmlspecialchars($course['course_code']) ?>
                                        </span>
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </h3>
                                    </div>
                                    <i class="fas fa-book text-itu-primary text-2xl"></i>
                                </div>
                                
                                <div class="flex items-center mt-4">
                                    <i class="fas fa-chalkboard-teacher text-gray-500 mr-2"></i>
                                    <p class="text-gray-700"><?= htmlspecialchars($course['faculty_name']) ?></p>
                                </div>
                                
                                <form method="POST" class="mt-6">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <button type="submit" class="w-full itu-accent text-white py-2 px-4 rounded-lg font-medium hover:bg-orange-600 transition">
                                        <i class="fas fa-user-plus mr-2"></i>Enroll Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($available_courses)): ?>
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-book-open text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500 text-xl">No courses available for enrollment</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Enrolled Courses Section -->
            <section class="slide-up">
                <h2 class="text-2xl font-semibold mb-6 itu-primary border-b pb-2">
                    <i class="fas fa-clipboard-list mr-2"></i>My Enrolled Courses
                </h2>
                
                <?php if(!empty($enrolled_courses)): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Course Code
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Course Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Instructor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($enrolled_courses as $course): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($course['course_code']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($course['course_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($course['faculty_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Enrolled
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow p-12 text-center">
                        <i class="fas fa-book text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700 mb-2">No enrolled courses</h3>
                        <p class="text-gray-500">Enroll in courses to see them listed here</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById("sidebarToggle").addEventListener("click", () => {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("hidden");
            sidebar.classList.toggle("md:block");
        });

        // Toggle profile menu
        function toggleProfileMenu() {
            document.getElementById("profileMenu").classList.toggle("hidden");
        }

        // Close profile menu when clicking elsewhere
        document.addEventListener('click', function(event) {
            const profileMenu = document.getElementById('profileMenu');
            const profileButton = document.querySelector('header button');
            
            if (!profileMenu.contains(event.target) && !profileButton.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>