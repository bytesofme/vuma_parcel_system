<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
checkAuth();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate OTP - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo"><i class="fas fa-box-open"></i> VUMA LOCKERS</div>
            <div class="nav-links">
                <a href="dashboard.php" style="color: white;">← Back to Dashboard</a>
                <a href="includes/logout.php" class="login-btn">Logout</a>
            </div>
        </div>
    </nav>
    <div style="max-width: 1200px; margin: 100px auto; padding: 2rem; text-align: center;">
        <h1>Generate OTP</h1>
        <p>OTP generation feature coming soon...</p>
    </div>
</body>
</html>