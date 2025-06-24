<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';

// Increase group_concat max length to handle long schedules
$conn->query("SET SESSION group_concat_max_len = 1000000;");

// Fetch all faculty with their scheduled courses and time slots
$scheduleQuery = $conn->query("
    SELECT 
        f.id AS faculty_id, 
        f.name AS faculty_name, 
        c.course_code, 
        c.course_name,
        COALESCE(
            GROUP_CONCAT(
                CONCAT(
                    s.day, 
                    ' (', 
                    TIME_FORMAT(s.start_time, '%h:%i %p'), 
                    '-', 
                    TIME_FORMAT(s.end_time, '%h:%i %p'), 
                    ')'
                ) 
                ORDER BY FIELD(s.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), 
                s.start_time
                SEPARATOR '<br>'
            ),
            'Not scheduled'
        ) AS schedule
    FROM faculty_schedule fs
    JOIN faculty f ON fs.faculty_id = f.id
    JOIN courses c ON fs.course_id = c.id
    LEFT JOIN schedule_slots s ON fs.id = s.faculty_schedule_id
    GROUP BY f.id, c.id, c.course_code, c.course_name
    ORDER BY f.name, c.course_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Schedule - ITU Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --itu-primary: #0195c3;
            --itu-accent: #f7941d;
            --itu-dark: #2c3e50;
        }
        .itu-primary { color: var(--itu-primary); }
        .bg-itu-primary { background-color: var(--itu-primary); }
        .bg-itu-accent { background-color: var(--itu-accent); }
        .bg-itu-dark { background-color: var(--itu-dark); }
        .border-itu-primary { border-color: var(--itu-primary); }
        .tab-active { 
            border-bottom: 3px solid var(--itu-primary);
            color: var(--itu-primary);
            font-weight: bold;
        }
        .sidebar-link {
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(1, 149, 195, 0.1);
            border-left: 4px solid var(--itu-primary);
        }
        .btn-primary {
            background-color: var(--itu-primary);
            color: white;
            transition: all 0.3s;
        }
        .schedule-card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .day-header {
            background-color: rgba(1, 149, 195, 0.1);
            font-weight: 600;
            padding: 12px 16px;
        }
        .lecture-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            transition: background-color 0.2s;
        }
        .lecture-item:hover {
            background-color: #f8fafc;
        }
        .lecture-time {
            font-weight: 500;
            color: var(--itu-dark);
        }
        .lecture-course {
            color: #4a5568;
        }
        .no-lectures {
            color: #718096;
            font-style: italic;
            padding: 16px;
        }
        .schedule-cell {
            vertical-align: top;
            min-width: 200px;
        }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-lg md:block hidden flex flex-col">
        <div class="p-6 flex flex-col items-center">
            <img
                src="../assets/ITU-Lahore-Punjab.jpg"
                alt="ITU Logo"
                class="h-15"
            />
            <h2 class="itu-primary text-4xl font-bold">ITU Portal</h2>
        </div>
        <nav class="px-4 flex-1">
            <ul>
                <li class="mb-2">
                    <a href="admin.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        <span class="ml-1">Dashboard</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="users.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-users mr-3"></i>
                        <span class="ml-1">User Management</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="register_student_admin.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-user-plus mr-3"></i>
                        <span class="ml-1">Student Registration</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="register_faculty_admin.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-user-plus mr-3"></i>
                        <span class="ml-1">Faculty Registration</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="courses.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-book mr-3"></i>
                        <span class="ml-1">Courses</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="schedule.php" class="flex items-center p-3 rounded sidebar-link active">
                        <i class="fas fa-calendar-alt mr-3"></i>
                        <span class="ml-1">Schedule</span>
                    </a>
                </li>
                <li class="mb-2">
    <a href="admin_messages.php" class="flex items-center p-3 rounded sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'admin_messages.php' ? 'active' : '' ?>">
        <i class="fas fa-envelope mr-3"></i>
        <span class="ml-1">Messages</span>
    </a>
</li>
            </ul>
        </nav>
        <div class="mt-auto mb-6 px-4">
            <div class="flex items-center p-3 rounded bg-gray-50">
                <div class="bg-itu-primary w-10 h-10 rounded-full flex items-center justify-center text-white font-bold">
                    <?= substr($_SESSION['admin']['name'], 0, 1) ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?= $_SESSION['admin']['name'] ?></p>
                    <button
                        onclick="toggleProfileMenu()"
                        class="text-xs text-gray-500 hover:text-itu-primary"
                    >
                        Settings <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                </div>
            </div>
            <div id="profileMenu" class="hidden mt-2 bg-white border rounded shadow-lg">
                <a href="admin_profile.php" class="block px-4 py-2 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
                <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top Navbar -->
        <header class="flex items-center justify-between bg-white shadow px-6 py-4">
            <div class="flex items-center">
                <button id="sidebarToggle" class="text-gray-500 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold itu-primary">Faculty Schedule</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bell text-xl"></i>
                    </button>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                </div>
                <div class="relative">
                    <button onclick="toggleProfileMenu()" class="flex items-center focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-itu-primary flex items-center justify-center text-white font-bold">
                            <?= substr($_SESSION['admin']['name'], 0, 1) ?>
                        </div>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 flex-1">
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($scheduleQuery->num_rows > 0): ?>
                            <?php while ($schedule = $scheduleQuery->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4"><?= htmlspecialchars($schedule['faculty_id']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($schedule['faculty_name']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($schedule['course_code']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($schedule['course_name']) ?></td>
                                    <td class="px-6 py-4 schedule-cell"><?= $schedule['schedule'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No schedules have been set yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
      
    <script>
        // Sidebar Toggle
        document.getElementById("sidebarToggle").addEventListener("click", () => {
            document.getElementById("sidebar").classList.toggle("hidden");
        });
        
        // Profile Dropdown
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const profileMenu = document.getElementById('profileMenu');
            const profileButton = document.querySelector('[onclick="toggleProfileMenu()"]');
            
            if (!profileButton.contains(event.target) && !profileMenu.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>