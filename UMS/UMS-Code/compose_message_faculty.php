<?php
// compose_message_faculty.php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/src/config.php';
$faculty_id = (int)$_SESSION['faculty']['id'];

// Handle form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_type = $_POST['receiver_type'];
    $receiver_id = ($receiver_type === 'admin') ? 1 : (int)$_POST['receiver_id']; // Admin ID fixed to 1
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    
    // Validate input
    if (empty($subject) || empty($content)) {
        $error = 'Subject and content are required';
    } else {
        // Insert message
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, content)
            VALUES (?, 'faculty', ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $faculty_id, $receiver_id, $receiver_type, $subject, $content);
        
        if ($stmt->execute()) {
            $success = 'Message sent successfully!';
            // Reset form on success
            $subject = $content = '';
        } else {
            $error = 'Error sending message: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch students for dropdown
$students = $conn->query("SELECT id, name FROM students ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Message | UMS</title>
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
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-input {
            transition: all 0.3s ease;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            width: 100%;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .recipient-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
        }
        
        .recipient-card:hover {
            border-color: #4299e1;
            background-color: #f0f9ff;
            transform: translateY(-2px);
        }
        
        .recipient-card.selected {
            border-color: #4299e1;
            background-color: #ebf5ff;
            border-width: 2px;
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
        
        .student-avatar {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
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
                <a href="faculty_messages.php" class="block py-3 px-6 bg-itu-secondary">
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
                    <h1 class="text-xl font-semibold text-gray-800">Compose Message</h1>
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

        <div class="p-6">
            <div class="bg-white rounded-lg shadow fade-in max-w-4xl mx-auto">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div>
                        <h3 class="text-xl font-semibold itu-primary">Compose New Message</h3>
                        <p class="text-sm text-gray-600">Send a message to students or administrators</p>
                    </div>
                    <div>
                        <a href="faculty_messages.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Messages
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                            <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-8">
                            <label class="form-label">Select Receiver Type</label>
                            <div class="flex space-x-4">
                                <div class="flex-1">
                                    <input type="radio" id="student-type" name="receiver_type" value="student" class="hidden peer" checked>
                                    <label for="student-type" class="block p-4 border border-gray-300 rounded-lg text-center cursor-pointer peer-checked:border-itu-primary peer-checked:bg-itu-primary peer-checked:text-white">
                                        <i class="fas fa-user-graduate text-2xl mb-2"></i>
                                        <h4 class="font-medium">Student</h4>
                                        <p class="text-sm opacity-80">Send to enrolled students</p>
                                    </label>
                                </div>
                                
                                <div class="flex-1">
                                    <input type="radio" id="admin-type" name="receiver_type" value="admin" class="hidden peer">
                                    <label for="admin-type" class="block p-4 border border-gray-300 rounded-lg text-center cursor-pointer peer-checked:border-itu-primary peer-checked:bg-itu-primary peer-checked:text-white">
                                        <i class="fas fa-user-shield text-2xl mb-2"></i>
                                        <h4 class="font-medium">Administrator</h4>
                                        <p class="text-sm opacity-80">Send to admin@itu.edu.pk</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-8">
                            <label class="form-label">Select Recipient</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-80 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                                <!-- Student Recipients -->
                                <div id="student-recipients">
                                    <?php while ($row = $students->fetch_assoc()): 
                                        $initials = substr($row['name'], 0, 1);
                                    ?>
                                    <div class="recipient-card" data-id="<?= $row['id'] ?>" data-type="student">
                                        <div class="flex items-center">
                                            <div class="avatar student-avatar">
                                                <?= strtoupper($initials) ?>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="font-medium"><?= htmlspecialchars($row['name']) ?></h4>
                                                <p class="text-sm text-gray-600">Student ID: <?= $row['id'] ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                
                                <!-- Admin Recipient (fixed) -->
                                <div id="admin-recipients" class="hidden">
                                    <div class="recipient-card selected" data-id="1" data-type="admin">
                                        <div class="flex items-center">
                                            <div class="avatar admin-avatar">
                                                A
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="font-medium">Administrator</h4>
                                                <p class="text-sm text-gray-600">admin@itu.edu.pk</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="receiver_id" name="receiver_id" required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="form-label" for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" 
                                   class="form-input"
                                   value="<?= isset($subject) ? htmlspecialchars($subject) : '' ?>"
                                   required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="form-label" for="content">Message</label>
                            <textarea id="content" name="content" rows="6"
                                      class="form-input"
                                      required><?= isset($content) ? htmlspecialchars($content) : '' ?></textarea>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i> Messages are delivered instantly
                            </div>
                            <div>
                                <button type="submit"
                                        class="bg-itu-primary text-white px-6 py-2 rounded-lg hover:bg-itu-secondary transition flex items-center">
                                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
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

    <script>
        // Toggle between student and admin recipients
        document.querySelectorAll('input[name="receiver_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'student') {
                    document.getElementById('student-recipients').classList.remove('hidden');
                    document.getElementById('admin-recipients').classList.add('hidden');
                } else {
                    document.getElementById('student-recipients').classList.add('hidden');
                    document.getElementById('admin-recipients').classList.remove('hidden');
                    // Auto-select the admin recipient
                    document.querySelectorAll('.recipient-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    document.querySelector('#admin-recipients .recipient-card').classList.add('selected');
                    document.getElementById('receiver_id').value = 1;
                }
            });
        });
        
        // Handle recipient selection
        document.querySelectorAll('.recipient-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selection from all cards
                document.querySelectorAll('.recipient-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Select this card
                this.classList.add('selected');
                
                // Set the hidden input value
                document.getElementById('receiver_id').value = this.dataset.id;
            });
        });
        
        // Auto-select first student by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstStudent = document.querySelector('#student-recipients .recipient-card');
            if (firstStudent) {
                firstStudent.classList.add('selected');
                document.getElementById('receiver_id').value = firstStudent.dataset.id;
            }
        });
    </script>
</body>
</html>