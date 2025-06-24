<?php
// student_messages.php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/src/config.php';
$student_id = (int)$_SESSION['student']['id'];

// Handle message deletion
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';

if (isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];
    $tab = isset($_POST['tab']) ? $_POST['tab'] : 'inbox';
    
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to the same tab
    header("Location: student_messages.php?tab=$tab");
    exit;
}

// Fetch inbox messages
$inbox = $conn->query("
    SELECT m.*, 
           a.name AS admin_name,
           f.name AS faculty_name
    FROM messages m
    LEFT JOIN admins a ON m.sender_id = a.id AND m.sender_type = 'admin'
    LEFT JOIN faculty f ON m.sender_id = f.id AND m.sender_type = 'faculty'
    WHERE m.receiver_id = $student_id AND m.receiver_type = 'student'
    ORDER BY m.sent_at DESC
");

// Fetch sent messages
$sent = $conn->query("
    SELECT m.*,
           a.name AS admin_name,
           f.name AS faculty_name
    FROM messages m
    LEFT JOIN admins a ON m.receiver_id = a.id AND m.receiver_type = 'admin'
    LEFT JOIN faculty f ON m.receiver_id = f.id AND m.receiver_type = 'faculty'
    WHERE m.sender_id = $student_id AND m.sender_type = 'student'
    ORDER BY m.sent_at DESC
");

// Count unread messages
$unread_result = $conn->query("
    SELECT COUNT(*) AS unread_count
    FROM messages 
    WHERE receiver_id = $student_id 
    AND receiver_type = 'student'
    AND is_read = 0
");
$unread_count = $unread_result->fetch_assoc()['unread_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Messages | UMS</title>
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
        
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .message-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .message-card.unread {
            border-left-color: #4299e1;
            background-color: #f0f9ff;
        }
        
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
        
        .admin-avatar {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }
        
        .faculty-avatar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .student-avatar {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
        }
        
        .tab-active {
            border-bottom: 3px solid #1a365d;
            color: #1a365d;
            font-weight: 600;
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .content-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .unread-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #4299e1;
            margin-left: 5px;
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
                 <a href="student_dashboard.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="enroll_courses.php" class="block py-3 px-6 hover:bg-itu-secondary">
                    <i class="fas fa-book mr-3"></i>Enroll In Course
                </a>
                <a href="student_messages.php" class="block py-3 px-6 bg-itu-secondary">
                    <i class="fas fa-envelope mr-3"></i>Messages
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
                    <h1 class="text-xl font-semibold text-gray-800">Student Dashboard</h1>
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
                            <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['student']['name']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['student']['email']) ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-teal-400 flex items-center justify-center text-white font-bold">
                            <?= strtoupper(substr($_SESSION['student']['name'], 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6">
            <div class="bg-white rounded-lg shadow fade-in">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-xl font-semibold itu-primary">Message Management</h3>
                    <p class="text-sm text-gray-600">Manage your inbox and sent messages</p>
                </div>
                
                <!-- Action Bar -->
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <div class="flex space-x-4">
                        <button id="inbox-tab" class="<?= $active_tab === 'inbox' ? 'tab-active text-itu-primary' : 'text-gray-600' ?> px-4 py-2">
                            Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="bg-itu-accent text-white text-xs rounded-full px-2 py-1 ml-2"><?= $unread_count ?> unread</span>
                            <?php endif; ?>
                        </button>
                        <button id="sent-tab" class="<?= $active_tab === 'sent' ? 'tab-active text-itu-primary' : 'text-gray-600' ?> px-4 py-2">Sent</button>
                    </div>
                    <div class="flex space-x-2">
                        <a href="compose_message_student.php" class="bg-itu-primary hover:bg-itu-secondary text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Compose
                        </a>
                    </div>
                </div>
                
                <!-- Messages Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From / To</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inbox-container" class="bg-white divide-y divide-gray-200 <?= $active_tab === 'inbox' ? '' : 'hidden' ?>">
                            <?php if ($inbox->num_rows > 0): ?>
                                <?php while ($message = $inbox->fetch_assoc()): 
                                    $is_unread = !$message['is_read'];
                                    $sender_type = $message['sender_type'];
                                    $name = $sender_type === 'admin' ? $message['admin_name'] : $message['faculty_name'];
                                    $initials = $sender_type === 'admin' ? 'A' : substr($name, 0, 1);
                                    $avatar_class = $sender_type === 'admin' ? 'admin-avatar' : 'faculty-avatar';
                                ?>
                                <tr class="message-card <?= $is_unread ? 'unread' : '' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="avatar <?= $avatar_class ?>">
                                                <?= strtoupper($initials) ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($name) ?></div>
                                                <div class="text-xs text-gray-500 capitalize"><?= $sender_type ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium <?= $is_unread ? 'text-itu-primary font-bold' : 'text-gray-900' ?>">
                                            <?= htmlspecialchars($message['subject']) ?>
                                            <?php if ($is_unread): ?>
                                                <span class="unread-badge"></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 content-preview max-w-md">
                                            <?= nl2br(htmlspecialchars(substr($message['content'], 0, 100))) ?>
                                            <?php if (strlen($message['content']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('M j', strtotime($message['sent_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_message_student.php?id=<?= $message['id'] ?>&tab=inbox" class="text-itu-primary hover:text-itu-secondary mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <input type="hidden" name="tab" value="inbox">
                                            <button type="submit" name="delete_message" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-lg">Your inbox is empty</p>
                                            <p class="mt-2">No messages have been received yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tbody id="sent-container" class="bg-white divide-y divide-gray-200 <?= $active_tab === 'sent' ? '' : 'hidden' ?>">
                            <?php if ($sent->num_rows > 0): ?>
                                <?php while ($message = $sent->fetch_assoc()): 
                                    $receiver_type = $message['receiver_type'];
                                    $name = $receiver_type === 'admin' ? $message['admin_name'] : $message['faculty_name'];
                                    $initials = $receiver_type === 'admin' ? 'A' : substr($name, 0, 1);
                                    $avatar_class = $receiver_type === 'admin' ? 'admin-avatar' : 'faculty-avatar';
                                ?>
                                <tr class="message-card">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="avatar <?= $avatar_class ?>">
                                                <?= strtoupper($initials) ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($name) ?></div>
                                                <div class="text-xs text-gray-500 capitalize"><?= $receiver_type ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($message['subject']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 content-preview max-w-md">
                                            <?= nl2br(htmlspecialchars(substr($message['content'], 0, 100))) ?>
                                            <?php if (strlen($message['content']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('M j', strtotime($message['sent_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_message_student.php?id=<?= $message['id'] ?>&tab=sent" class="text-itu-primary hover:text-itu-secondary mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <input type="hidden" name="tab" value="sent">
                                            <button type="submit" name="delete_message" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-paper-plane text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-lg">No sent messages</p>
                                            <p class="mt-2">You haven't sent any messages yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="bg-white border-t py-4 px-6">
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

    <script>
        // Tab switching functionality
        document.getElementById('inbox-tab').addEventListener('click', function() {
            this.classList.add('tab-active', 'text-itu-primary');
            this.classList.remove('text-gray-600');
            
            document.getElementById('sent-tab').classList.remove('tab-active', 'text-itu-primary');
            document.getElementById('sent-tab').classList.add('text-gray-600');
            
            document.getElementById('inbox-container').classList.remove('hidden');
            document.getElementById('sent-container').classList.add('hidden');
            
            // Update URL without reloading
            history.replaceState(null, null, 'student_messages.php?tab=inbox');
        });
        
        document.getElementById('sent-tab').addEventListener('click', function() {
            this.classList.add('tab-active', 'text-itu-primary');
            this.classList.remove('text-gray-600');
            
            document.getElementById('inbox-tab').classList.remove('tab-active', 'text-itu-primary');
            document.getElementById('inbox-tab').classList.add('text-gray-600');
            
            document.getElementById('sent-container').classList.remove('hidden');
            document.getElementById('inbox-container').classList.add('hidden');
            
            // Update URL without reloading
            history.replaceState(null, null, 'student_messages.php?tab=sent');
        });

        document.querySelectorAll('button[name="delete_message"]').forEach(button => {
            button.addEventListener('click', (e) => {
                if (!confirm('Are you sure you want to delete this message?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>