<?php
session_start();

// Check if faculty session exists
if (!isset($_SESSION['faculty'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/database_setup.php';
$faculty = $_SESSION['faculty'];
$faculty_id = $faculty['id'];

// Fetch faculty's courses with schedules
$courses = $conn->query("
    SELECT c.course_code, c.course_name, 
           GROUP_CONCAT(
             CONCAT(s.day, ' ', TIME_FORMAT(s.start_time, '%H:%i'), '-', TIME_FORMAT(s.end_time, '%H:%i'))
             ORDER BY FIELD(s.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time
             SEPARATOR ' / '
           ) AS schedule
    FROM courses c
    LEFT JOIN faculty_schedule fs ON c.id = fs.course_id AND fs.faculty_id = $faculty_id
    LEFT JOIN schedule_slots s ON fs.id = s.faculty_schedule_id
    WHERE c.faculty_id = $faculty_id
    GROUP BY c.id
");

// Fetch today's schedule
$today = date('l'); // Full day name (e.g., Monday)
$scheduleQuery = $conn->query("
    SELECT c.course_name, 
           TIME_FORMAT(s.start_time, '%H:%i') as start_time, 
           TIME_FORMAT(s.end_time, '%H:%i') as end_time
    FROM schedule_slots s
    JOIN faculty_schedule fs ON s.faculty_schedule_id = fs.id
    JOIN courses c ON fs.course_id = c.id
    WHERE fs.faculty_id = $faculty_id
      AND s.day = '$today'
    ORDER BY s.start_time
");

// Count courses
$courseCount = $courses->num_rows;

// Query for new messages
$newMessagesQuery = $conn->query("
    SELECT COUNT(*) AS count 
    FROM messages 
    WHERE receiver_id = $faculty_id 
      AND receiver_type = 'faculty'
      AND is_read = 0
");

$new_messages = 0;
if ($newMessagesQuery && $newMessagesQuery->num_rows > 0) {
    $row = $newMessagesQuery->fetch_assoc();
    $new_messages = $row['count'];
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ITU Faculty Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      :root {
        --itu-primary: #0195c3;
        --itu-accent: #f7941d;
        --itu-light: #ebf8ff;
        --itu-dark: #2c3e50;
      }
      .itu-primary { color: var(--itu-primary); }
      .bg-itu-primary { background-color: var(--itu-primary); }
      .bg-itu-accent { background-color: var(--itu-accent); }
      .bg-itu-light { background-color: var(--itu-light); }
      .fade-in {
        animation: fadeIn 0.8s ease-in forwards;
      }
      .slide-up {
        animation: slideUp 0.8s ease-out forwards;
      }
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }
      .card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border-top: 4px solid var(--itu-primary);
      }
      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
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
          src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=200&w=264"
          alt="Banner"
          class="object-cover w-full h-full"
        />
        <div class="absolute bottom-0 left-0 p-4 flex items-center">
          <img
            src="https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=64&w=64"
            alt="Avatar"
            class="h-12 w-12 rounded-full border-2 border-white"
          />
          <div class="ml-3">
            <p class="text-white font-semibold"><?= htmlspecialchars($faculty['name']) ?></p>
            <p class="text-xs text-gray-200">Faculty</p>
          </div>
        </div>
      </div>
      <nav class="flex-1 overflow-y-auto mt-4">
        <ul>
          <li class="mb-2">
            <a
              href="#"
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
              href="#courses"
              class="flex items-center px-4 py-2 hover:bg-gray-200 rounded"
              ><svg
                class="h-5 w-5 itu-primary"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                /></svg
              ><span class="ml-3">My Courses</span></a
            >
          </li>
        
          <li class="mb-2">
            <a href="src/faculty_schedule.php" class="flex items-center px-4 py-2 hover:bg-gray-200 rounded">
                <svg class="h-5 w-5 itu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
                          stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="ml-3">Set Schedule</span>
            </a>
        </li>
        
        <li class="mb-2">
            <a href="faculty_messages.php" class="flex items-center px-4 py-2 hover:bg-gray-200 rounded relative">
                <i class="fas fa-envelope mr-3"></i>
                <span class="ml-3">Messages</span>
                <?php if ($new_messages > 0): ?>
                    <span class="absolute right-4 top-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?= $new_messages ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
         
       
        </ul>
      </nav>
      <div class="p-4 border-t">
        <a href="logout.php" class="w-full text-left text-red-600 hover:bg-gray-200 px-4 py-2 rounded">
          Logout
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
      <!-- Navbar -->
      <header
        class="flex items-center justify-between bg-white shadow px-6 py-4"
      >
        <div class="flex items-center">
          <button id="sidebarToggle" class="text-gray-500 md:hidden mr-4">
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                d="M4 6h16M4 12h16M4 18h16"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
          <h1 class="text-2xl font-semibold itu-primary">Faculty Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
          <div class="relative">
            <input
              type="search"
              placeholder="Search classes..."
              class="px-3 py-2 border rounded-lg focus:outline-none"
            />
            <div
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M21 21l-4.35-4.35"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
          <button class="relative">
            <svg
              class="h-6 w-6 text-gray-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              /></svg
            ><span
              class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full"
            ></span>
          </button>
          <div class="relative">
            <button onclick="toggleProfileMenu()">
              <img
                src="https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=40&w=40"
                alt="Avatar"
                class="h-8 w-8 rounded-full"
              />
            </button>
            <div
              id="profileMenu"
              class="hidden absolute right-0 mt-2 bg-white border rounded shadow-lg"
            >
              <a href="#profile" class="block px-4 py-2 hover:bg-gray-100"
                >Profile</a
              >
              <button
                onclick="logout()"
                class="w-full text-left px-4 py-2 hover:bg-gray-100"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Hero Section -->
      <section
        class="relative bg-cover bg-center h-60 text-white slide-up"
        style="background-image: url('https://images.pexels.com/photos/3184301/pexels-photo-3184301.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260');"
      >
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="relative z-10 flex items-center justify-center h-full">
          <div class="text-2xl md:text-4xl font-bold">Hello, <?= htmlspecialchars($faculty['name']) ?>!</div>
        </div>
      </section>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
        <div class="bg-white rounded-lg shadow p-4 fade-in">
          <h3 class="text-lg font-semibold">Courses Taught</h3>
          <p class="text-2xl itu-primary font-bold mt-2"><?= $courseCount ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 fade-in">
          <h3 class="text-lg font-semibold">New Messages</h3>
          <p class="text-2xl itu-primary font-bold mt-2"><?= $new_messages ?></p>
        </div>
      </div>

      <!-- My Courses Table -->
        <section id="courses" class="p-6 fade-in">
        <h3 class="text-xl font-semibold mb-4 itu-primary">My Courses</h3>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
          <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php while($course = $courses->fetch_assoc()): ?>
              <tr>
                <td class="px-6 py-4"><?= htmlspecialchars($course['course_code']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($course['course_name']) ?></td>
                <td class="px-6 py-4"><?= $course['schedule'] ? htmlspecialchars($course['schedule']) : 'Not scheduled' ?></td>
              </tr>
              <?php endwhile; ?>
              
              <?php if ($courseCount === 0): ?>
                <tr>
                  <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                    No courses assigned
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>


      <!-- Schedule Section -->
      <section id="schedule" class="p-6 fade-in">
        <h3 class="text-xl font-semibold mb-4 itu-primary">Today's Schedule (<?= $today ?>)</h3>
        <ul class="space-y-3">
          <?php if ($scheduleQuery->num_rows > 0): ?>
            <?php while ($schedule = $scheduleQuery->fetch_assoc()): ?>
              <li class="bg-itu-light p-4 rounded-lg flex items-center justify-between">
                <span class="font-medium"><?= htmlspecialchars($schedule['course_name']) ?></span>
                <span class="bg-itu-primary text-white px-3 py-1 rounded-lg">
                  <?= htmlspecialchars($schedule['start_time']) ?> - <?= htmlspecialchars($schedule['end_time']) ?>
                </span>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li class="bg-itu-light p-4 rounded-lg">
              <span>No classes scheduled for today</span>
            </li>
          <?php endif; ?>
        </ul>
      </section>

      <!-- Footer -->
      <footer class="mt-auto bg-white shadow-inner p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h4 class="font-semibold mb-2">About ITU</h4>
            <p class="text-sm text-gray-600">
              Committed to academic excellence and research innovation.
            </p>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Quick Links</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li><a href="#" class="hover:itu-primary">Support</a></li>
              <li><a href="#" class="hover:itu-primary">Privacy Policy</a></li>
              <li><a href="#" class="hover:itu-primary">Terms</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Contact</h4>
            <p class="text-sm text-gray-600">123 University Ave, City</p>
            <p class="text-sm text-gray-600">+1 234 567 890</p>
          </div>
        </div>
        <div class="text-center text-xs text-gray-500 mt-4">
          &copy; 2025 ITU. All rights reserved.
        </div>
      </footer>
    </div>

    <script>
      document
        .getElementById("sidebarToggle")
        .addEventListener("click", () =>
          document.getElementById("sidebar").classList.toggle("hidden")
        );
      function toggleProfileMenu() {
        document.getElementById("profileMenu").classList.toggle("hidden");
      }
      function logout() {
        window.location.href = 'logout.php';
      }
    </script>
  </body>
</html>