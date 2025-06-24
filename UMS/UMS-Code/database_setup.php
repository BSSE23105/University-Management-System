<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "UMS";

// Connect without specifying DB
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($dbname);

// Create admins table
$sql = "CREATE TABLE IF NOT EXISTS `admins` (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql) !== TRUE) {
    die("Error creating admins table: " . $conn->error);
}

// Create faculty table
$sql = "CREATE TABLE IF NOT EXISTS `faculty` (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    qualification VARCHAR(100) NOT NULL,
    experience INT NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql) !== TRUE) {
    die("Error creating faculty table: " . $conn->error);
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS `students` (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fsc_marks DECIMAL(5,2) NOT NULL,
    matric_marks DECIMAL(5,2) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql) !== TRUE) {
    die("Error creating students table: " . $conn->error);
}


$sql = "CREATE TABLE IF NOT EXISTS `courses` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    faculty_id INT NOT NULL,
    credits INT NOT NULL DEFAULT 3,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute courses table creation
if ($conn->query($sql) !== TRUE) {
    die("Error creating courses table: " . $conn->error);
}

// Create faculty_schedule table (NEW TABLE)
$sql = "CREATE TABLE IF NOT EXISTS `faculty_schedule` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    course_id INT NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) !== TRUE) {
    die("Error creating faculty_schedule table: " . $conn->error);
}

// Create schedule_slots table (NEW TABLE)
$sql = "CREATE TABLE IF NOT EXISTS `schedule_slots` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_schedule_id INT NOT NULL,
    day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (faculty_schedule_id) REFERENCES faculty_schedule(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) !== TRUE) {
    die("Error creating schedule_slots table: " . $conn->error);
}

// Create student_courses table
$sql = "CREATE TABLE IF NOT EXISTS `student_courses` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute student_courses table creation
if ($conn->query($sql) !== TRUE) {
    die("Error creating student_courses table: " . $conn->error);
}


$sql = "CREATE TABLE IF NOT EXISTS `messages` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('admin', 'faculty', 'student') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_type ENUM('admin', 'faculty', 'student') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) !== TRUE) {
    die("Error creating messages table: " . $conn->error);
}

// Insert default admin
$adminID = 1001;
$adminEmail = "admin@itu.edu.pk";
$adminName  = "Administrator";
$adminPass  = "Admin@123";

$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $ins = $conn->prepare(
        "INSERT INTO admins (id, name, email, password) VALUES (?, ?, ?, ?)"
    );
    $ins->bind_param("isss", $adminID, $adminName, $adminEmail, $adminPass);
    if (!$ins->execute()) {
        echo "Failed to insert default admin: " . $ins->error;
    }
    $ins->close();
}
$stmt->close();
?>