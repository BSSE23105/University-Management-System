<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';

$activeTab = isset($_GET['type']) ? $_GET['type'] : 'students';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination parameters
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Function to fetch users with pagination
function getUsers($conn, $type, $search, $limit, $offset) {
    $table = ($type === 'faculty') ? 'faculty' : 'students';
    $sql = "SELECT * FROM $table";
    $count_sql = "SELECT COUNT(*) AS total FROM $table";
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $sql .= " WHERE name LIKE ? OR email LIKE ?";
        $count_sql .= " WHERE name LIKE ? OR email LIKE ?";
    }
    
    $sql .= " LIMIT $limit OFFSET $offset";
    
    // Get total count
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($search)) {
        $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
    }
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total = $total_result['total'];
    
    // Get paginated results
    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    return [
        'result' => $result,
        'total' => $total
    ];
}

$users_data = getUsers($conn, $activeTab, $search, $limit, $offset);
$users = $users_data['result'];
$total_users = $users_data['total'];
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ITU Portal</title>
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
        .btn-primary:hover {
            background-color: #017ea5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(1, 149, 195, 0.3);
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: var(--itu-dark);
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }
        .form-input {
            transition: all 0.3s;
            border: 1px solid #cbd5e1;
        }
        .form-input:focus {
            border-color: var(--itu-primary);
            box-shadow: 0 0 0 3px rgba(1, 149, 195, 0.2);
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .action-edit {
            background-color: rgba(1, 149, 195, 0.1);
            color: var(--itu-primary);
        }
        .action-edit:hover {
            background-color: rgba(1, 149, 195, 0.2);
        }
        .action-delete {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .action-delete:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
        .pagination-link {
            padding: 0.25rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.25rem;
        }
        .pagination-link:hover {
            background-color: #e2e8f0;
        }
        .pagination-active {
            background-color: var(--itu-primary);
            color: white;
            border-color: var(--itu-primary);
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
                    <a href="users.php" class="flex items-center p-3 rounded sidebar-link active">
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
                <h1 class="text-xl font-semibold itu-primary">User Management</h1>
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

        <!-- Content Header -->
        <div class="p-6 flex-1">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl font-bold itu-primary mb-4 md:mb-0">
                    Manage Users 
                    <span class="text-sm font-normal text-gray-500">
                        (<?= $total_users ?> <?= $activeTab === 'students' ? 'Students' : 'Faculty Members' ?>)
                    </span>
                </h1>
                
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 w-full md:w-auto">
                    <form method="GET" class="flex w-full">
                        <input type="hidden" name="type" value="<?= $activeTab ?>">
                        <div class="relative w-full">
                            <input
                                type="search"
                                name="search"
                                placeholder="Search by name or email..."
                                value="<?= htmlspecialchars($search) ?>"
                                class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary pr-16"
                            >
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex space-x-2">
                                <?php if (!empty($search)): ?>
                                <a href="?type=<?= $activeTab ?>" class="text-gray-400 hover:text-itu-primary">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                                <button type="submit" class="text-gray-400 hover:text-itu-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="flex space-x-2">
                        <a href="register_student_admin.php" class="btn-primary px-4 py-3 rounded-lg font-medium flex items-center">
                            <i class="fas fa-user-plus mr-2"></i> Add Student
                        </a>
                        <a href="register_faculty_admin.php" class="btn-primary px-4 py-3 rounded-lg font-medium flex items-center">
                            <i class="fas fa-user-plus mr-2"></i> Add Faculty
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b mb-6">
                <a 
                    href="?type=students&search=" 
                    class="px-6 py-3 mr-4 flex items-center <?= $activeTab === 'students' ? 'tab-active' : 'text-gray-600 hover:text-itu-primary' ?>"
                >
                    <i class="fas fa-user-graduate mr-2"></i>Students
                </a>
                <a 
                    href="?type=faculty&search=" 
                    class="px-6 py-3 flex items-center <?= $activeTab === 'faculty' ? 'tab-active' : 'text-gray-600 hover:text-itu-primary' ?>"
                >
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Faculty
                </a>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?= $activeTab === 'students' ? 'FSC Marks' : 'Qualification' ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?= $activeTab === 'students' ? 'Matric Marks' : 'Experience' ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $user['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="bg-gray-200 border-2 border-dashed rounded-xl w-8 h-8 flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($activeTab === 'students'): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?= $user['fsc_marks'] ?>%</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($user['qualification']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($activeTab === 'students'): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?= $user['matric_marks'] ?>%</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full"><?= $user['experience'] ?> years</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $user['age'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 py-1 rounded-full <?= 
                                        $user['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 
                                        ($user['gender'] === 'Female' ? 'bg-pink-100 text-pink-800' : 'bg-purple-100 text-purple-800') 
                                    ?>">
                                        <?= $user['gender'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex space-x-2">
                                    <a 
                                        href="edit_user.php?type=<?= $activeTab ?>&id=<?= $user['id'] ?>" 
                                        class="action-btn action-edit flex items-center"
                                    >
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <a 
                                        href="delete_user.php?type=<?= $activeTab ?>&id=<?= $user['id'] ?>" 
                                        class="action-btn action-delete flex items-center"
                                        onclick="return confirm('Are you sure you want to delete this user?');"
                                    >
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($users->num_rows === 0): ?>
                <div class="text-center py-12">
                    <div class="mb-4 text-gray-400">
                        <i class="fas fa-user-slash text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600">No <?= $activeTab ?> found</h3>
                    <p class="mt-1 text-gray-500">
                        Try adjusting your search or add new <?= $activeTab === 'students' ? 'students' : 'faculty' ?>
                    </p>
                    <div class="mt-6">
                        <a href="<?= $activeTab === 'students' ? 'register_student_admin.php' : 'register_faculty_admin.php' ?>" class="btn-primary px-6 py-3 rounded-lg font-medium inline-flex items-center">
                            <i class="fas fa-user-plus mr-2"></i> 
                            Add New <?= $activeTab === 'students' ? 'Student' : 'Faculty' ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex items-center justify-between px-6 py-4 border-t">
                    <div class="text-sm text-gray-700">
                        Showing 
                        <span class="font-medium"><?= ($offset + 1) ?></span> - 
                        <span class="font-medium"><?= min($offset + $limit, $total_users) ?></span> of 
                        <span class="font-medium"><?= $total_users ?></span> results
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?type=<?= $activeTab ?>&search=<?= urlencode($search) ?>&page=<?= $page-1 ?>" 
                               class="pagination-link hover:bg-gray-100">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?type=<?= $activeTab ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" 
                               class="pagination-link <?= $i == $page ? 'pagination-active' : 'hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?type=<?= $activeTab ?>&search=<?= urlencode($search) ?>&page=<?= $page+1 ?>" 
                               class="pagination-link hover:bg-gray-100">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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