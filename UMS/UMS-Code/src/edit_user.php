<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Validate type
if (!in_array($type, ['students', 'faculty'])) {
    die("Invalid user type");
}

// Get user data
$table = ($type === 'faculty') ? 'faculty' : 'students';
$stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
    
    // Common fields
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    
    // Type-specific fields
    if ($type === 'students') {
        $fsc_marks = $_POST['fsc_marks'];
        $matric_marks = $_POST['matric_marks'];
        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, password=?, fsc_marks=?, matric_marks=?, age=?, gender=? WHERE id=?");
        $stmt->bind_param("sssddisi", $name, $email, $password, $fsc_marks, $matric_marks, $age, $gender, $id);
    } else {
        $qualification = $_POST['qualification'];
        $experience = $_POST['experience'];
        $stmt = $conn->prepare("UPDATE faculty SET name=?, email=?, password=?, qualification=?, experience=?, age=?, gender=? WHERE id=?");
        $stmt->bind_param("ssssiisi", $name, $email, $password, $qualification, $experience, $age, $gender, $id);
    }

    if ($stmt->execute()) {
        $success = 'User updated successfully!';
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Error: ' . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= ucfirst($type) ?> - UMS</title>
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
        .focus\:ring-itu-primary:focus { 
            --tw-ring-color: var(--itu-primary);
            box-shadow: 0 0 0 3px rgba(1, 149, 195, 0.3);
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navbar -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center">
                <a href="users.php?type=<?= $type ?>" class="flex items-center text-gray-600 hover:text-itu-primary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Back to <?= $type === 'students' ? 'Students' : 'Faculty' ?></span>
                </a>
            </div>
            <div>
                <h1 class="text-xl font-bold itu-primary">Edit <?= $type === 'students' ? 'Student' : 'Faculty' ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bell text-xl"></i>
                    </button>
                </div>
                <div class="relative">
                    <button onclick="toggleProfileMenu()" class="flex items-center focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-itu-primary flex items-center justify-center text-white font-bold">
                            <?= substr($_SESSION['admin']['name'], 0, 1) ?>
                        </div>
                    </button>
                    <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> My Profile
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        <div class="card bg-white rounded-xl p-6">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= $error ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= $success ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="flex items-center mb-8">
                <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="text-gray-600">ID: <?= $user['id'] ?></p>
                </div>
            </div>
            
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Full Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            value="<?= htmlspecialchars($user['name']) ?>" 
                            class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            value="<?= htmlspecialchars($user['email']) ?>" 
                            class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                placeholder="Leave blank to keep current password"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                <i class="fas fa-key"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                    </div>
                    
                    <?php if ($type === 'students'): ?>
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">FSC Marks (%)</label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    name="fsc_marks" 
                                    value="<?= $user['fsc_marks'] ?>" 
                                    min="0" max="100" step="0.01"
                                    class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                    required
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                    <i class="fas fa-percent"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Matric Marks (%)</label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    name="matric_marks" 
                                    value="<?= $user['matric_marks'] ?>" 
                                    min="0" max="100" step="0.01"
                                    class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                    required
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                    <i class="fas fa-percent"></i>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Qualification</label>
                            <input 
                                type="text" 
                                name="qualification" 
                                value="<?= htmlspecialchars($user['qualification']) ?>" 
                                class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Years of Experience</label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    name="experience" 
                                    value="<?= $user['experience'] ?>" 
                                    min="0"
                                    class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                    required
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Age</label>
                        <div class="relative">
                            <input 
                                type="number" 
                                name="age" 
                                value="<?= $user['age'] ?>" 
                                min="1"
                                class="w-full px-4 py-3 form-input rounded-lg focus:outline-none focus:ring-itu-primary"
                                required
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Gender</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center border rounded-lg p-3 cursor-pointer hover:bg-gray-50 <?= $user['gender'] === 'Male' ? 'border-itu-primary bg-blue-50' : '' ?>">
                                <input 
                                    type="radio" 
                                    name="gender" 
                                    value="Male" 
                                    class="form-radio text-itu-primary focus:ring-itu-primary"
                                    <?= $user['gender'] === 'Male' ? 'checked' : '' ?>
                                >
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="flex items-center border rounded-lg p-3 cursor-pointer hover:bg-gray-50 <?= $user['gender'] === 'Female' ? 'border-itu-primary bg-blue-50' : '' ?>">
                                <input 
                                    type="radio" 
                                    name="gender" 
                                    value="Female" 
                                    class="form-radio text-itu-primary focus:ring-itu-primary"
                                    <?= $user['gender'] === 'Female' ? 'checked' : '' ?>
                                >
                                <span class="ml-2">Female</span>
                            </label>
                            <label class="flex items-center border rounded-lg p-3 cursor-pointer hover:bg-gray-50 <?= $user['gender'] === 'Other' ? 'border-itu-primary bg-blue-50' : '' ?>">
                                <input 
                                    type="radio" 
                                    name="gender" 
                                    value="Other" 
                                    class="form-radio text-itu-primary focus:ring-itu-primary"
                                    <?= $user['gender'] === 'Other' ? 'checked' : '' ?>
                                >
                                <span class="ml-2">Other</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="users.php?type=<?= $type ?>" class="btn-secondary px-6 py-3 rounded-lg font-medium">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Toggle profile menu
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }
        
        // Close profile menu when clicking outside
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