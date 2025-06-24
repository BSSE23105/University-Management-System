<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../database_setup.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];  // Plain text
    $fsc_marks = $_POST['fsc_marks'];
    $matric_marks = $_POST['matric_marks'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];

    // Validate ID
    if (!is_numeric($id)) {
        $error = 'ID must be a number';
    } else {
        $id = (int)$id;
        
        // FIX: Changed binding type for gender from integer (i) to string (s)
        $stmt = $conn->prepare("INSERT INTO students (id, name, email, password, fsc_marks, matric_marks, age, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssddss", $id, $name, $email, $password, $fsc_marks, $matric_marks, $age, $gender);

        if ($stmt->execute()) {
            $success = 'Student registered successfully!';
        } else {
            $error = 'Error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - University Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --itu-primary: #0195c3;
            --itu-accent: #f7941d;
            --itu-light: #f0f9ff;
            --itu-dark: #2c3e50;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 35px;
            width: 100%;
            max-width: 650px;
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(to right, var(--itu-primary), var(--itu-accent));
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        .header h2 {
            color: var(--itu-dark);
            margin-bottom: 8px;
            font-size: 28px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .header i {
            font-size: 42px;
            color: var(--itu-primary);
            margin-bottom: 15px;
        }
        .error {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .error i {
            margin-right: 10px;
            font-size: 20px;
        }
        .success {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .success i {
            margin-right: 10px;
            font-size: 20px;
        }
        .form-group {
            margin-bottom: 22px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            display: flex;
            align-items: center;
        }
        label i {
            margin-right: 8px;
            color: var(--itu-primary);
            width: 20px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            padding: 14px 15px 14px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #fafafa;
        }
        input:focus {
            border-color: var(--itu-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(1, 149, 195, 0.2);
            background-color: white;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 40px;
            color: #777;
        }
        .gender-options {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        .gender-options label {
            display: flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 8px;
            background: #f8f8f8;
            transition: all 0.2s;
            flex: 1;
        }
        .gender-options label:hover {
            background: #eef7ff;
        }
        .gender-options input {
            margin-right: 8px;
            width: auto;
        }
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .btn-back {
            background-color: #f1f5f9;
            color: var(--itu-dark);
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            text-align: center;
        }
        .btn-back i {
            margin-right: 8px;
        }
        .btn-back:hover {
            background-color: #e2e8f0;
            transform: translateY(-2px);
        }
        .btn-submit {
            background: linear-gradient(to right, var(--itu-primary), #02b3e4);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-submit i {
            margin-right: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 149, 195, 0.3);
        }
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-user-graduate"></i>
            <h2>Student Registration</h2>
            <p>Register new students into the university system</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- ID Field -->
            <div class="form-group">
                <label for="id"><i class="fas fa-id-card"></i> Student ID</label>
                <i class="fas fa-hashtag input-icon"></i>
                <input type="number" id="id" name="id" min="1" required placeholder="Enter unique student ID">
            </div>
            
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <i class="fas fa-signature input-icon"></i>
                <input type="text" id="name" name="name" required placeholder="Enter student's full name">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <i class="fas fa-at input-icon"></i>
                <input type="email" id="email" name="email" required placeholder="Enter student's email">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <i class="fas fa-key input-icon"></i>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>
            
            <div class="form-group">
                <label for="fsc_marks"><i class="fas fa-graduation-cap"></i> FSc Marks (%)</label>
                <i class="fas fa-percent input-icon"></i>
                <input type="number" id="fsc_marks" name="fsc_marks" min="0" max="100" step="0.01" required placeholder="Enter FSc marks">
            </div>
            
            <div class="form-group">
                <label for="matric_marks"><i class="fas fa-graduation-cap"></i> Matric Marks (%)</label>
                <i class="fas fa-percent input-icon"></i>
                <input type="number" id="matric_marks" name="matric_marks" min="0" max="100" step="0.01" required placeholder="Enter Matric marks">
            </div>
            
            <div class="form-group">
                <label for="age"><i class="fas fa-birthday-cake"></i> Age</label>
                <i class="fas fa-user-clock input-icon"></i>
                <input type="number" id="age" name="age" min="1" required placeholder="Enter student's age">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-venus-mars"></i> Gender</label>
                <div class="gender-options">
                    <label><input type="radio" name="gender" value="Male" required> <i class="fas fa-male"></i> Male</label>
                    <label><input type="radio" name="gender" value="Female"> <i class="fas fa-female"></i> Female</label>
                    <label><input type="radio" name="gender" value="Other"> <i class="fas fa-genderless"></i> Other</label>
                </div>
            </div>
            
            <div class="btn-container">
                <a href="users.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Register Student
                </button>
            </div>
        </form>
        
        <div class="footer">
            <p>University Management System &copy; <?= date('Y') ?></p>
        </div>
    </div>
</body>
</html>