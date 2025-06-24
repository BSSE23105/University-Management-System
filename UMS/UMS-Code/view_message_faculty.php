<?php
// view_message_faculty.php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/src/config.php';
$faculty_id = (int)$_SESSION['faculty']['id'];

// Get message ID from query string
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';

// Fetch the message
$message = null;
$stmt = $conn->prepare("
    SELECT m.*, 
           a.name AS admin_name,
           s.name AS student_name
    FROM messages m
    LEFT JOIN admins a ON (m.sender_id = a.id AND m.sender_type = 'admin') OR (m.receiver_id = a.id AND m.receiver_type = 'admin')
    LEFT JOIN students s ON (m.sender_id = s.id AND m.sender_type = 'student') OR (m.receiver_id = s.id AND m.receiver_type = 'student')
    WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
");
$stmt->bind_param("iii", $message_id, $faculty_id, $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $message = $result->fetch_assoc();
    
    // Mark as read if faculty is receiver
    if ($message['receiver_id'] == $faculty_id && $message['receiver_type'] == 'faculty' && !$message['is_read']) {
        $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $message_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
}
$stmt->close();

// If message not found, redirect
if (!$message) {
    header("Location: faculty_messages.php");
    exit;
}

// Determine sender/receiver details
$sender_name = '';
$receiver_name = '';

if ($message['sender_type'] === 'faculty') {
    $sender_name = $_SESSION['faculty']['name'];
} else if ($message['sender_type'] === 'admin') {
    $sender_name = $message['admin_name'] ? $message['admin_name'] : 'Administrator';
} else {
    $sender_name = $message['student_name'] ? $message['student_name'] : 'Student';
}

if ($message['receiver_type'] === 'faculty') {
    $receiver_name = $_SESSION['faculty']['name'];
} else if ($message['receiver_type'] === 'admin') {
    $receiver_name = $message['admin_name'] ? $message['admin_name'] : 'Administrator';
} else {
    $receiver_name = $message['student_name'] ? $message['student_name'] : 'Student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message | UMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'itu-primary': '#1a365d',
                        'itu-secondary': '#2c5282',
                        'itu-accent': '#4299e1',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }
        
        .message-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .message-header {
            border-bottom: 2px solid #e5e7eb;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
        }
        
        .admin-avatar {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }
        
        .student-avatar {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
        }
        
        .faculty-avatar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <div class="sidebar w-64 bg-itu-primary text-white flex flex-col">
        <div class="p-4 flex items-center justify-center border-b border-itu-secondary">
            <div class="text-xl font-bold">University MS</div>
        </div>
        <div class="flex-1 py-4">
            <nav>
                <a href="faculty_dashboard.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="faculty_courses.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-book mr-3"></i>My Courses
                </a>
                <a href="faculty_schedule.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-calendar-alt mr-3"></i>Schedule
                </a>
                <a href="faculty_students.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-users mr-3"></i>Students
                </a>
                <a href="faculty_messages.php" class="block py-3 px-6 bg-itu-secondary">
                    <i class="fas fa-envelope mr-3"></i>Messages
                </a>
                <a href="faculty_profile.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-user-cog mr-3"></i>Profile
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-itu-secondary">
            <a href="logout.php" class="flex items-center text-red-300 hover:text-red-100">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top Navbar -->
        <header class="bg-white shadow">
            <div class="flex justify-between items-center px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">View Message</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-2 text-gray-600 hover:text-itu-primary">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-3 text-right">
                            <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['faculty']['name']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['faculty']['email']) ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold">
                            <?= strtoupper(substr($_SESSION['faculty']['name'], 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 flex-1">
            <div class="bg-white rounded-lg shadow max-w-4xl mx-auto">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div>
                        <h3 class="text-xl font-semibold itu-primary">Message Details</h3>
                    </div>
                    <div>
                        <a href="faculty_messages.php?tab=<?= $tab ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Messages
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    <?php if ($message): ?>
                        <div class="message-header pb-6 mb-6">
                            <div class="flex justify-between items-start">
                                <div class="flex items-start">
                                    <?php if ($message['sender_type'] === 'faculty'): ?>
                                        <div class="avatar faculty-avatar"><?= strtoupper(substr($sender_name, 0, 1)) ?></div>
                                    <?php elseif ($message['sender_type'] === 'admin'): ?>
                                        <div class="avatar admin-avatar">A</div>
                                    <?php else: ?>
                                        <div class="avatar student-avatar"><?= strtoupper(substr($sender_name, 0, 1)) ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold"><?= htmlspecialchars($message['subject']) ?></h4>
                                        <p class="text-sm text-gray-600">
                                            From: <?= htmlspecialchars($sender_name) ?> (<?= ucfirst($message['sender_type']) ?>)
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($message['sent_at'])) ?></p>
                                    <?php if ($message['is_read'] && $message['receiver_type'] === 'faculty'): ?>
                                        <p class="text-xs text-green-600 mt-1">
                                            <i class="fas fa-check-circle"></i> Read
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex">
                                <div class="ml-14">
                                    <p class="text-sm text-gray-600">
                                        To: <?= htmlspecialchars($receiver_name) ?> (<?= ucfirst($message['receiver_type']) ?>)
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-content mb-8">
                            <div class="prose max-w-none">
                                <?= nl2br(htmlspecialchars($message['content'])) ?>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center border-t pt-4">
                            <div class="text-sm text-gray-500">
                                Message ID: <?= $message['id'] ?>
                            </div>
                            <div>
                                <form method="POST" action="faculty_messages.php" class="inline">
                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= $tab ?>">
                                    <button type="submit" name="delete_message" 
        class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition flex items-center"
        onclick="return confirm('Are you sure you want to delete this message?');">
                                        <i class="fas fa-trash mr-2"></i> Delete
                                    </button>
                                </form>
                                
                                <?php if ($message['sender_id'] != $faculty_id): ?>
                                    <a href="compose_message_faculty.php?reply=<?= $message['id'] ?>" 
                                       class="bg-itu-primary text-white px-4 py-2 rounded-lg hover:bg-itu-secondary transition flex items-center ml-3">
                                        <i class="fas fa-reply mr-2"></i> Reply
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-5xl text-gray-300 mb-4">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <h3 class="text-xl font-medium text-gray-700">Message Not Found</h3>
                            <p class="mt-2 text-gray-500">The requested message could not be found or you don't have permission to view it.</p>
                            <a href="faculty_messages.php" class="inline-block mt-6 bg-itu-primary text-white px-6 py-2 rounded-lg">
                                Back to Messages
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="bg-white border-t py-4 px-6 mt-auto">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">© 2023 University Management System. All rights reserved.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-itu-primary">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>