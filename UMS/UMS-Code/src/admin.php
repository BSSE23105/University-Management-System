<?php
// admin.php
session_start();
require_once 'config.php';  // Include database connection

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

// Safely get admin ID
$admin_id = (int)$_SESSION['admin']['id'];

// Query for new messages
$result = $conn->query("
    SELECT COUNT(*) AS count 
    FROM messages 
    WHERE receiver_id = $admin_id 
      AND receiver_type = 'admin'
      AND is_read = 0
");

// Handle query results
$new_messages = 0;
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_messages = $row['count'];
}

// Get total users count
$total_users = 0;
$result = $conn->query("
    SELECT (SELECT COUNT(*) FROM students) + 
           (SELECT COUNT(*) FROM faculty) AS total
");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_users = $row['total'];
}

// Get active courses count
$active_courses = 0;
$result = $conn->query("
    SELECT COUNT(*) AS count 
    FROM courses 
    WHERE status = 'active'
");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $active_courses = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ITU Admin Dashboard</title>
    <link
        rel="shortcut icon"
        href="../assets/ITU-Lahore-Punjab.jpg"
        type="image/x-icon"
    />
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ITU Brand Colors & Animations */
        :root {
            --itu-primary: #0195c3;
            --itu-accent: #f7941d;
            --itu-dark: #2c3e50;
        }
        .itu-primary {
            color: var(--itu-primary);
        }
        .bg-itu-primary {
            background-color: var(--itu-primary);
        }
        .bg-itu-accent {
            background-color: var(--itu-accent);
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
        .sidebar-link {
            transition: all 0.3s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(1, 149, 195, 0.1);
            border-left: 4px solid var(--itu-primary);
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
                    <a href="admin.php" class="flex items-center p-3 rounded sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        <span class="ml-1">Dashboard</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="users.php" class="flex items-center p-3 rounded sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="ml-1">User Management</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="courses.php" class="flex items-center p-3 rounded sidebar-link">
                        <i class="fas fa-book mr-3"></i>
                        <span class="ml-1">Courses</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="schedule.php" class="flex items-center p-3 rounded sidebar-link">
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
                <h1 class="text-xl font-semibold itu-primary">Admin Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input
                        type="search"
                        placeholder="Search…"
                        class="px-4 py-2 border rounded-lg focus:outline-none w-64"
                    />
                    <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
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

        <!-- Hero Section -->
        <section
            class="relative bg-cover bg-center min-h-[15rem] text-white slide-up"
            style="
                background-image: url('https://images.pexels.com/photos/30137358/pexels-photo-30137358.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260');
            "
        >
            <div class="absolute inset-0 bg-black opacity-50"></div>
            <div class="relative z-10 flex items-center justify-center h-full">
                <h2 class="text-4xl font-bold">Welcome, <?= $_SESSION['admin']['name'] ?>!</h2>
            </div>
        </section>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-600">Total Users</h3>
                        <p class="text-2xl font-bold mt-1"><?= $total_users ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100">
                        <i class="fas fa-book text-orange-500 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-600">Active Courses</h3>
                        <p class="text-2xl font-bold mt-1"><?= $active_courses ?></p>
                    </div>
                </div>
            </div>
          
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <i class="fas fa-envelope text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-600">New Messages</h3>
                        <p class="text-2xl font-bold mt-1"><?= $new_messages ?></p>
                    </div>
                </div>
            </div>
        </div>

         <!-- Admin Responsibilities Section -->
        <section class="p-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-xl font-semibold itu-primary">Admin Responsibilities</h3>
                </div>
                <ul class="divide-y">
                    <li class="flex items-center p-6 fade-in hover:bg-gray-50">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-user-plus text-blue-500"></i>
                        </div>
                        <div class="ml-4">
                            <p>
                                <span class="font-medium">User Management</span> - Register and manage student and faculty accounts.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center p-6 fade-in hover:bg-gray-50">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-book text-purple-500"></i>
                        </div>
                        <div class="ml-4">
                            <p>
                                <span class="font-medium">Course Administration</span> - Create, update, and deactivate courses.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center p-6 fade-in hover:bg-gray-50">
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar-alt text-green-500"></i>
                        </div>
                        <div class="ml-4">
                            <p>
                                <span class="font-medium">Schedule Coordination</span> - Manage class schedules and faculty assignments.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center p-6 fade-in hover:bg-gray-50">
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-cog text-yellow-500"></i>
                        </div>
                        <div class="ml-4">
                            <p>
                                <span class="font-medium">System Oversight</span> - Monitor system usage and resolve technical issues.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center p-6 fade-in hover:bg-gray-50">
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-comments text-red-500"></i>
                        </div>
                        <div class="ml-4">
                            <p>
                                <span class="font-medium">Communication Hub</span> - Handle inquiries and messages from all users.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-auto bg-white shadow-inner p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-semibold mb-2">About ITU</h4>
                    <p class="text-sm text-gray-600">
                        IT University is committed to excellence in education and research.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold mb-2">Quick Links</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><a href="#" class="hover:itu-primary"><i class="fas fa-question-circle mr-2"></i>Help Center</a></li>
                        <li><a href="#" class="hover:itu-primary"><i class="fas fa-shield-alt mr-2"></i>Privacy Policy</a></li>
                        <li><a href="#" class="hover:itu-primary"><i class="fas fa-file-contract mr-2"></i>Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-2">Contact</h4>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i>123 University Ave, City, Country
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fas fa-phone mr-2"></i>+1 234 567 890
                    </p>
                </div>
            </div>
            <div class="text-center text-xs text-gray-500 mt-6">
                &copy; <?= date('Y') ?> ITU. All rights reserved.
            </div>
        </footer>
    </div>

    <!-- JavaScript for Interactivity -->
    <script>
        // Sidebar Toggle
        document.getElementById("sidebarToggle").addEventListener("click", () => {
            document.getElementById("sidebar").classList.toggle("hidden");
        });
        
        // Profile Dropdown
        function toggleProfileMenu() {
            document.getElementById("profileMenu").classList.toggle("hidden");
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