<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: index.php");
    exit;
}

$student = $_SESSION['student'];
require 'src/config.php'; // Use existing config file

// Fetch enrolled courses for the student
$enrolled_courses = [];
$student_id = $student['id'];
$sql = "
    SELECT c.id, c.course_code, c.course_name, f.name AS faculty_name 
    FROM student_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN faculty f ON c.faculty_id = f.id
    WHERE sc.student_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $enrolled_courses = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ITU Student Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      /* ITU Brand Colors & Animations */
      .itu-primary {
        color: #0195c3;
      }
      .itu-accent {
        background-color: #f7941d;
      }
      .fade-in {
        animation: fadeIn 0.8s ease-in forwards;
      }
      .slide-up {
        animation: slideUp 0.8s ease-out forwards;
      }
      @keyframes fadeIn {
        from {
          opacity: 0;
        }
        to {
          opacity: 1;
        }
      }
      @keyframes slideUp {
        from {
          transform: translateY(20px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
    </style>
  </head>
  <body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside
      id="sidebar"
      class="w-64 bg-white shadow-lg md:block hidden flex flex-col"
    >
      <div class="relative h-32">
        <img
          src="https://images.pexels.com/photos/4144177/pexels-photo-4144177.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=200&w=264"
          alt="Banner"
          class="w-full h-full"
        />
        <div class="absolute bottom-0 left-0 p-4 flex items-center">
          <img
            src="https://images.pexels.com/photos/8473935/pexels-photo-8473935.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=64&w=64"
            alt="Avatar"
            class="h-12 w-12 rounded-full border-2 border-white"
          />
          <div class="ml-3">
            <p class="text-white font-semibold"><?= htmlspecialchars($student['name']) ?></p>
            <p class="text-xs text-gray-200">Student</p>
          </div>
        </div>
      </div>
      <nav class="flex-1 overflow-y-auto">
        <ul class="mt-4">
          <li class="mb-2">
            <a
              href="student_dashboard.php"
              class="flex items-center px-4 py-2 hover:bg-gray-200 rounded"
              ><svg
                class="h-5 w-5 itu-primary"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M3 7h18M3 12h18M3 17h18"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                /></svg
              ><span class="ml-3">Dashboard</span></a
            >
          </li>
          <li class="mb-2">
            <a
              href="enroll_courses.php"
              class="flex items-center px-4 py-2 hover:bg-gray-200 rounded"
              ><svg
                class="h-5 w-5 itu-primary"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                ></path>
              </svg>
              <span class="ml-3">Enroll in Courses</span></a
            >
          </li>
          <li class="mb-2">
    <a href="student_messages.php" class="flex items-center px-4 py-2 hover:bg-gray-200 rounded">
        <i class="fas fa-envelope mr-3"></i>
        <span class="ml-3">Messages</span>
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
            <!-- SVG remains -->
          </button>
          <h1 class="text-2xl font-semibold itu-primary">Student Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
          <!-- Search and notifications remain -->
          <div class="relative">
            <button onclick="toggleProfileMenu()">
              <img
                src="https://images.pexels.com/photos/8473935/pexels-photo-8473935.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=40&w=40"
                alt="Avatar"
                class="h-8 w-8 rounded-full"
              />
            </button>
            <div
              id="profileMenu"
              class="hidden absolute right-0 mt-2 bg-white border rounded shadow-lg"
            >
              <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
            </div>
          </div>
        </div>
      </header>

      <!-- Hero Section -->
      <section class="relative bg-cover bg-center min-h-[20rem] text-white slide-up"
        style="background-image: url('https://images.pexels.com/photos/3184293/pexels-photo-3184293.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260');">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="relative z-10 flex items-center justify-center h-full">
          <div class="text-2xl md:text-4xl font-bold">Welcome back, <?= htmlspecialchars($student['name']) ?>!</div>
        </div>
      </section>
       <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
        <div class="bg-white rounded-lg shadow p-4 fade-in">
          <h3 class="text-lg font-semibold">Enrolled Courses</h3>
          <p class="text-2xl itu-accent font-bold mt-2">
            <?= count($enrolled_courses) ?>
          </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 fade-in">
          <h3 class="text-lg font-semibold">Completed Credits</h3>
          <p class="text-2xl itu-accent font-bold mt-2">72</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 fade-in">
          <h3 class="text-lg font-semibold">Current GPA</h3>
          <p class="text-2xl itu-accent font-bold mt-2">3.85</p>
        </div>
      </div>


      <section id="courses" class="p-6 fade-in">
        <h3 class="text-xl font-semibold mb-4 itu-primary">My Courses</h3>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
          <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Course ID
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Course Name
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Section
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Instructor
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Schedule
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach($enrolled_courses as $course): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($course['course_code']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($course['course_name']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap">BSSE-B</td>
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($course['faculty_name']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap">To be scheduled</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

    </div>

    <script>
      document.getElementById("sidebarToggle").addEventListener("click", () => {
        document.getElementById("sidebar").classList.toggle("hidden");
        document.getElementById("sidebar").classList.toggle("md:block");
      });
      
      function toggleProfileMenu() {
        document.getElementById("profileMenu").classList.toggle("hidden");
      }
    </script>
  </body>
</html>