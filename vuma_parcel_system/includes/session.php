<?php
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'customer';
}

function requireAdmin() {
    checkAuth();
    if (!isAdmin()) {
        header('Location: ../dashboard.php');
        exit();
    }
}
?>