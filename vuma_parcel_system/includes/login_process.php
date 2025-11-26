<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        header('Location: ../login.php?error=Email and password are required');
        exit();
    }

    try {
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Temporary admin access with simple password
        if ($user && $user['email'] === 'admin@vuma.com' && $password === 'admin123') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: ../admin_dashboard.php');
            exit();
        }
        // Normal user login
        elseif ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ../dashboard.php');
            exit();
        } else {
            header('Location: ../login.php?error=Invalid email or password');
            exit();
        }

    } catch (PDOException $e) {
        header('Location: ../login.php?error=Database error: ' . $e->getMessage());
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>