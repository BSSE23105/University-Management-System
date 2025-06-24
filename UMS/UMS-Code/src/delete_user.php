<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

// Validate type
if (!in_array($type, ['students', 'faculty'])) {
    die("Invalid user type");
}

$table = ($type === 'faculty') ? 'faculty' : 'students';

// Delete user
$stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = ucfirst($type) . " deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting user: " . $stmt->error;
}

header("Location: users.php?type=$type");
exit;