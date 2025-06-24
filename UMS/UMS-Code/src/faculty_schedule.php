<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';
$faculty_id = $_SESSION['faculty']['id'];
$faculty = $_SESSION['faculty'];

// Handle schedule submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_schedule'])) {
    $course_id = $_POST['course_id'];
    
    // Get existing schedule or create new
    $stmt = $conn->prepare("SELECT id FROM faculty_schedule WHERE faculty_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $faculty_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        $schedule_id = $schedule['id'];
    } else {
        $insert = $conn->prepare("INSERT INTO faculty_schedule (faculty_id, course_id) VALUES (?, ?)");
        $insert->bind_param("ii", $faculty_id, $course_id);
        $insert->execute();
        $schedule_id = $conn->insert_id;
        $insert->close();
    }
    $stmt->close();
    
    // Delete existing slots
    $delete = $conn->prepare("DELETE FROM schedule_slots WHERE faculty_schedule_id = ?");
    $delete->bind_param("i", $schedule_id);
    $delete->execute();
    $delete->close();
    
    // Add new slots
    $days = $_POST['day'] ?? [];
    $start_times = $_POST['start_time'] ?? [];
    $end_times = $_POST['end_time'] ?? [];
    
    $insertSlot = $conn->prepare("INSERT INTO schedule_slots (faculty_schedule_id, day, start_time, end_time) 
                                 VALUES (?, ?, ?, ?)");
    
    for ($i = 0; $i < count($days); $i++) {
        if (!empty($days[$i]) && !empty($start_times[$i]) && !empty($end_times[$i])) {
            $insertSlot->bind_param("isss", $schedule_id, $days[$i], $start_times[$i], $end_times[$i]);
            $insertSlot->execute();
        }
    }
    $insertSlot->close();
    
    $_SESSION['message'] = "Schedule updated successfully!";
    $_SESSION['msg_type'] = "success";
}

// Fetch faculty's assigned courses
$courses = $conn->query("
    SELECT c.id, c.course_code, c.course_name, fs.id AS schedule_id
    FROM courses c
    LEFT JOIN faculty_schedule fs ON c.id = fs.course_id AND fs.faculty_id = $faculty_id
    WHERE c.faculty_id = $faculty_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Schedule - Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
        .bg-itu-dark { background-color: var(--itu-dark); }
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
        .schedule-card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            border-top: 4px solid var(--itu-primary);
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .schedule-header {
            background-color: rgba(1, 149, 195, 0.1);
            padding: 15px 20px;
            font-weight: 600;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert-danger {
            background-color: #fed7d7;
            color: #822727;
            border: 1px solid #feb2b2;
        }
        .schedule-input {
            background-color: #f8fafc;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .schedule-input:focus {
            border-color: var(--itu-primary);
            box-shadow: 0 0 0 3px rgba(1, 149, 195, 0.2);
            outline: none;
        }
        .schedule-btn {
            background: linear-gradient(135deg, var(--itu-primary), #0275a1);
            transition: all 0.3s;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .schedule-btn:hover {
            background: linear-gradient(135deg, #0275a1, #015c7f);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(1, 149, 195, 0.3);
        }
        .no-courses {
            background: linear-gradient(135deg, #f8fafc, #edf2f7);
            border-radius: 12px;
        }
        .slot-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .remove-slot {
            cursor: pointer;
            color: #e53e3e;
        }
        .time-input {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-lg md:block hidden flex flex-col">
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
                        href="../faculty_dashboard.php"
                        class="flex items-center px-4 py-2 hover:bg-gray-200 rounded"
                    >
                        <svg class="h-5 w-5 itu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 7h18M3 12h18M3 17h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                
                <li class="mb-2">
                    <a href="faculty_schedule.php" class="flex items-center px-4 py-2 bg-gray-200 rounded">
                        <svg class="h-5 w-5 itu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
                                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="ml-3">Set Schedule</span>
                    </a>
                </li>
                
            </ul>
        </nav>
        <div class="p-4 border-t">
            <a href="../logout.php" class="w-full text-left text-red-600 hover:bg-gray-200 px-4 py-2 rounded">
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
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <h1 class="text-xl font-semibold itu-primary">Set Course Schedule</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="relative text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full"></span>
                    </button>
                </div>
                <div class="relative">
                    <button onclick="toggleProfileMenu()">
                        <img
                            src="https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg?auto=compress&cs=tinysrgb&dpr=1&h=40&w=40"
                            alt="Avatar"
                            class="h-8 w-8 rounded-full"
                        />
                    </button>
                    <div id="profileMenu" class="hidden absolute right-0 mt-2 bg-white border rounded shadow-lg">
                        <a href="../faculty_dashboard.php#profile" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                        <button onclick="logout()" class="w-full text-left px-4 py-2 hover:bg-gray-100">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6 flex-1 slide-up">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> fade-in">
                    <?= $_SESSION['message']; ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold itu-primary">Manage Your Course Schedules</h2>
                <p class="text-gray-600 mt-2">Set or update class times for your courses</p>
            </div>

            <?php if ($courses->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($course = $courses->fetch_assoc()): 
                        // Get existing slots
                        $slots = [];
                        if ($course['schedule_id']) {
                            $slotQuery = $conn->query("
                                SELECT day, start_time, end_time 
                                FROM schedule_slots 
                                WHERE faculty_schedule_id = {$course['schedule_id']}
                            ");
                            $slots = $slotQuery->fetch_all(MYSQLI_ASSOC);
                        }
                    ?>
                        <div class="schedule-card bg-white">
                            <div class="schedule-header flex items-center">
                                <div class="bg-itu-primary w-10 h-10 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                    <?= substr($course['course_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold"><?= htmlspecialchars($course['course_name']) ?></h3>
                                    <p class="text-sm text-gray-500"><?= $course['course_code'] ?></p>
                                </div>
                            </div>
                            <form method="POST" class="p-4">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Class Schedule
                                    </label>
                                    
                                    <div id="slots-container-<?= $course['id'] ?>">
                                        <?php if (count($slots) > 0): ?>
                                            <?php foreach ($slots as $index => $slot): ?>
                                                <div class="slot-row">
                                                    <select name="day[]" class="schedule-input">
                                                        <option value="Monday" <?= $slot['day'] == 'Monday' ? 'selected' : '' ?>>Monday</option>
                                                        <option value="Tuesday" <?= $slot['day'] == 'Tuesday' ? 'selected' : '' ?>>Tuesday</option>
                                                        <option value="Wednesday" <?= $slot['day'] == 'Wednesday' ? 'selected' : '' ?>>Wednesday</option>
                                                        <option value="Thursday" <?= $slot['day'] == 'Thursday' ? 'selected' : '' ?>>Thursday</option>
                                                        <option value="Friday" <?= $slot['day'] == 'Friday' ? 'selected' : '' ?>>Friday</option>
                                                        <option value="Saturday" <?= $slot['day'] == 'Saturday' ? 'selected' : '' ?>>Saturday</option>
                                                        <option value="Sunday" <?= $slot['day'] == 'Sunday' ? 'selected' : '' ?>>Sunday</option>
                                                    </select>
                                                    <div class="time-input">
                                                        <input type="time" name="start_time[]" value="<?= $slot['start_time'] ?>" class="schedule-input">
                                                    </div>
                                                    <div class="time-input">
                                                        <input type="time" name="end_time[]" value="<?= $slot['end_time'] ?>" class="schedule-input">
                                                    </div>
                                                    <button type="button" class="remove-slot" onclick="removeSlot(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="slot-row">
                                                <select name="day[]" class="schedule-input">
                                                    <option value="Monday">Monday</option>
                                                    <option value="Tuesday">Tuesday</option>
                                                    <option value="Wednesday">Wednesday</option>
                                                    <option value="Thursday">Thursday</option>
                                                    <option value="Friday">Friday</option>
                                                    <option value="Saturday">Saturday</option>
                                                    <option value="Sunday">Sunday</option>
                                                </select>
                                                <div class="time-input">
                                                    <input type="time" name="start_time[]" class="schedule-input">
                                                </div>
                                                <div class="time-input">
                                                    <input type="time" name="end_time[]" class="schedule-input">
                                                </div>
                                                <button type="button" class="remove-slot" onclick="removeSlot(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button 
                                        type="button" 
                                        class="add-slot-btn mt-2 bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-3 rounded text-sm"
                                        onclick="addSlot(<?= $course['id'] ?>)"
                                    >
                                        <i class="fas fa-plus mr-1"></i> Add Time Slot
                                    </button>
                                </div>
                                
                                <button 
                                    type="submit" 
                                    name="set_schedule" 
                                    class="schedule-btn w-full text-white py-3 rounded-lg"
                                >
                                    <i class="fas fa-calendar-check mr-2"></i> Save Schedule
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-courses max-w-2xl mx-auto p-8 text-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16 mx-auto flex items-center justify-center">
                        <i class="fas fa-book-open text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-500 mt-4">No courses assigned</h3>
                    <p class="text-gray-400 mt-2">You haven't been assigned to any courses yet.</p>
                    <div class="mt-6">
                        <a href="../faculty_dashboard.php" class="inline-flex items-center px-4 py-2 bg-itu-primary text-white rounded-lg hover:bg-itu-dark transition">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sidebar toggle
        document.getElementById("sidebarToggle").addEventListener("click", () => {
            document.getElementById("sidebar").classList.toggle("hidden");
        });
        
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }
        
        function logout() {
            window.location.href = '../logout.php';
        }
        
        // Slot management functions
        function addSlot(courseId) {
            const container = document.getElementById(`slots-container-${courseId}`);
            const slotCount = container.querySelectorAll('.slot-row').length;
            
            const slotDiv = document.createElement('div');
            slotDiv.className = 'slot-row';
            slotDiv.innerHTML = `
                <select name="day[]" class="schedule-input">
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
                <div class="time-input">
                    <input type="time" name="start_time[]" class="schedule-input">
                </div>
                <div class="time-input">
                    <input type="time" name="end_time[]" class="schedule-input">
                </div>
                <button type="button" class="remove-slot" onclick="removeSlot(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(slotDiv);
        }
        
        function removeSlot(button) {
            const slotRow = button.closest('.slot-row');
            if (slotRow) {
                slotRow.remove();
            }
        }
        
        // Close profile menu when clicking outside
        document.addEventListener('click', function(event) {
            const profileMenu = document.getElementById('profileMenu');
            const profileBtn = document.querySelector('[onclick="toggleProfileMenu()"]');
            
            if (!profileBtn.contains(event.target) && !profileMenu.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>