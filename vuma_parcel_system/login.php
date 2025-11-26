<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* INLINE STYLES TO GUARANTEE NO CACHE */
        .auth-background {
            background: linear-gradient(135deg, #006b54, #d31621) !important;
            background-size: 400% 400% !important;
            animation: kenyanWave 15s ease infinite !important;
        }
        @keyframes kenyanWave {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-background" style="background: linear-gradient(135deg, #006b54, #d31621) !important;"></div>
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="auth-header">
                    <a href="index.php" class="back-home">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                    <div class="logo">
                        <i class="fas fa-box-open"></i>
                        <span>VUMA LOCKERS</span>
                    </div>
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="includes/login_process.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required placeholder="Enter your email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                        </div>
                    </div>

                    <button type="submit" class="auth-button">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>