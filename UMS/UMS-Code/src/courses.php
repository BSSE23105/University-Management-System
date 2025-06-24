<?php
session_start();
require_once __DIR__ . '/../database_setup.php';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $faculty_id = $_POST['faculty_id'];
    $credits = $_POST['credits'] ?? 3;
    $status = $_POST['status'] ?? 'active';
    
    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, faculty_id, credits, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiis", $course_code, $course_name, $faculty_id, $credits, $status);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Course created successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating course: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
}

// Handle course update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $faculty_id = $_POST['faculty_id'];
    $credits = $_POST['credits'] ?? 3;
    $status = $_POST['status'] ?? 'active';
    
    $stmt = $conn->prepare("UPDATE courses SET course_code=?, course_name=?, faculty_id=?, credits=?, status=? WHERE id=?");
    $stmt->bind_param("ssiisi", $course_code, $course_name, $faculty_id, $credits, $status, $course_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Course updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating course: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
}

// Handle course deletion
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    
    // Check if there are any enrollments for this course
    // FIX: Changed table name from student_course to student_courses
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_courses WHERE course_id = ?");
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $enrollment_count = $row['count'];
    $check_stmt->close();
    
    if ($enrollment_count > 0) {
        $_SESSION['message'] = "Cannot delete course: There are students enrolled in this course.";
        $_SESSION['msg_type'] = "danger";
    } else {
        $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Course deleted successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting course: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    }
    header("location: courses.php");
    exit();
}

// Check if we're editing a course
$editing = false;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $editing = true;
    $edit_result = $conn->query("SELECT * FROM courses WHERE id = $edit_id");
    if ($edit_result->num_rows == 1) {
        $edit_course = $edit_result->fetch_assoc();
    } else {
        $_SESSION['message'] = "Course not found!";
        $_SESSION['msg_type'] = "danger";
        header("location: courses.php");
        exit();
    }
}

// Fetch courses and faculty
$courses = $conn->query("
    SELECT c.*, f.name AS faculty_name 
    FROM courses c 
    JOIN faculty f ON c.faculty_id = f.id
");
$faculty = $conn->query("SELECT id, name FROM faculty");

// Get statistics
$total_courses = $conn->query("SELECT COUNT(*) as total FROM courses")->fetch_assoc()['total'];
$total_faculty = $conn->query("SELECT COUNT(*) as total FROM faculty")->fetch_assoc()['total'];
$total_students = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM student_courses")->fetch_assoc()['total'];
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - ITU Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --itu-primary: #1a365d;
            --itu-secondary: #2c5282;
            --itu-accent: #4299e1;
            --itu-light: #ebf8ff;
            --itu-dark: #1a202c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, var(--itu-primary), var(--itu-secondary));
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .university-name {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .portal-info {
            font-size: 14px;
            color: #cbd5e0;
        }
        
        .nav-tabs {
            display: flex;
            margin-top: 15px;
        }
        
        .nav-tab {
            padding: 8px 20px;
            background-color: rgba(255,255,255,0.1);
            margin-right: 10px;
            border-radius: 4px 4px 0 0;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-tab.active {
            background-color: white;
            color: var(--itu-primary);
        }
        
        .nav-tab:hover:not(.active) {
            background-color: rgba(255,255,255,0.2);
        }
        
        .page-title {
            background-color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-size: 24px;
            font-weight: 600;
            color: var(--itu-primary);
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            background-color: var(--itu-light);
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: var(--itu-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .input-group {
            margin-bottom: 15px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #4a5568;
        }
        
        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus, 
        .input-group select:focus {
            border-color: var(--itu-accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            background-color: var(--itu-primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn:hover {
            background-color: var(--itu-secondary);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--itu-primary);
            color: var(--itu-primary);
        }
        
        .btn-outline:hover {
            background-color: var(--itu-light);
        }
        
        .btn-danger {
            background-color: #e53e3e;
        }
        
        .btn-danger:hover {
            background-color: #c53030;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background-color: var(--itu-light);
        }
        
        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--itu-dark);
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 12px 20px;
            border-bottom: 1px solid #edf2f7;
        }
        
        tr:hover {
            background-color: #f7fafc;
        }
        
        .actions-cell {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .edit-btn {
            background-color: #e6fffa;
            color: #234e52;
            border: 1px solid #81e6d9;
        }
        
        .edit-btn:hover {
            background-color: #b2f5ea;
        }
        
        .delete-btn {
            background-color: #fff5f5;
            color: #822727;
            border: 1px solid #fc8181;
        }
        
        .delete-btn:hover {
            background-color: #fed7d7;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #c6f6d5;
            color: #22543d;
        }
        
        .status-inactive {
            background-color: #fed7d7;
            color: #822727;
        }
        
        .search-bar {
            display: flex;
            gap: 15px;
            padding: 10px 20px;
            background-color: white;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }
        
        .stat-courses {
            background-color: #ebf8ff;
            color: #3182ce;
        }
        
        .stat-faculty {
            background-color: #fff5f5;
            color: #e53e3e;
        }
        
        .stat-students {
            background-color: #f0fff4;
            color: #38a169;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--itu-dark);
        }
        
        .stat-label {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
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
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header-top {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .portal-info {
                margin-top: 10px;
            }
            
            .nav-tabs {
                overflow-x: auto;
                padding-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <div class="university-name">INFORMATION TECHNOLOGY UNIVERSITY</div>
            <div class="portal-info">
                <i class="fas fa-database"></i> localhost / MySQL / ums | php
            </div>
        </div>
        <div class="nav-tabs">
    <a href="admin.php" class="nav-tab" style="text-decoration: none; color: inherit;">
        <i class="fas fa-arrow-left"></i> Back to Admin
    </a>
    <div class="nav-tab active">Courses</div>
</div>
    </header>
    
    <div class="page-title">Course Management</div>
    
    <div class="container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-courses">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $total_courses ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-faculty">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $total_faculty ?></div>
                    <div class="stat-label">Faculty Teaching</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-students">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $total_students ?></div>
                    <div class="stat-label">Students Enrolled</div>
                </div>
            </div>
        </div>
        
   <div class="card">
            <div class="card-header">
                <div><?= $editing ? 'Edit Course' : 'Create New Course' ?></div>
            </div>
            <form method="POST" class="form-grid">
                <?php if($editing): ?>
                    <input type="hidden" name="course_id" value="<?= $edit_course['id'] ?>">
                <?php endif; ?>
                
                <!-- REMOVED SCHEDULE INPUT FIELD -->
                <div class="input-group">
                    <label for="course_code">Course Code</label>
                    <input type="text" id="course_code" name="course_code" 
                           placeholder="e.g. CS-101" 
                           value="<?= $editing ? $edit_course['course_code'] : '' ?>" 
                           required>
                </div>
                <div class="input-group">
                    <label for="course_name">Course Name</label>
                    <input type="text" id="course_name" name="course_name" 
                           placeholder="e.g. Introduction to Programming" 
                           value="<?= $editing ? $edit_course['course_name'] : '' ?>" 
                           required>
                </div>
                <div class="input-group">
                    <label for="faculty_id">Instructor</label>
                    <select id="faculty_id" name="faculty_id" required>
                        <option value="">Select Instructor</option>
                        <?php while($f = $faculty->fetch_assoc()): ?>
                            <option value="<?= $f['id'] ?>" 
                                <?= $editing && $edit_course['faculty_id'] == $f['id'] ? 'selected' : '' ?>>
                                <?= $f['name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="credits">Credit Hours</label>
                    <input type="number" id="credits" name="credits" min="1" max="6" 
                           value="<?= $editing ? $edit_course['credits'] : '3' ?>">
                </div>
                <div class="input-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?= $editing && ($edit_course['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
<option value="inactive" <?= $editing && ($edit_course['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="input-group" style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px;">
                    <?php if($editing): ?>
                        <a href="courses.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="update_course" class="btn">
                            <i class="fas fa-save"></i> Update Course
                        </button>
                    <?php else: ?>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-times"></i> Reset
                        </button>
                        <button type="submit" name="create_course" class="btn">
                            <i class="fas fa-plus"></i> Create Course
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div>All Courses</div>
                <div class="search-bar">
                    <input type="text" class="search-input" placeholder="Search courses...">
                    <button class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Instructor</th>
                            <!-- REMOVED SCHEDULE COLUMN -->
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?= $course['course_code'] ?></td>
                            <td><?= $course['course_name'] ?></td>
                            <td><?= $course['faculty_name'] ?></td>
                            <td><?= $course['credits'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $course['status'] ?? 'inactive' ?>">
                                    <?= ucfirst($course['status']) ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <a href="courses.php?edit=<?= $course['id'] ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="courses.php?delete=<?= $course['id'] ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this course?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Basic form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const courseCode = document.getElementById('course_code').value;
            const courseName = document.getElementById('course_name').value;
            const facultyId = document.getElementById('faculty_id').value;
            const schedule = document.getElementById('schedule').value;
            
            if (!courseCode || !courseName || !facultyId || !schedule) {
                alert('Please fill in all required fields');
                e.preventDefault();
            }
        });
        
        
    </script>
</body>
</html>

