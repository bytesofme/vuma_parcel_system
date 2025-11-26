<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vuma Parcel Lockers - Smart Package Management</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Hero Section with Background -->
    <div class="hero-section">
        <div class="hero-overlay">
            <nav class="navbar">
                <div class="nav-container">
                    <div class="logo">
                        <i class="fas fa-box-open"></i>
                        <span>VUMA LOCKERS</span>
                    </div>
                    <div class="nav-links">
                        <a href="#features">Features</a>
                        <a href="#about">How It Works</a>
                        <a href="login.php" class="login-btn">Login</a>
                        <a href="signup.php" class="signup-btn">Sign Up</a>
                    </div>
                </div>
            </nav>

            <div class="hero-content">
                <h1>Smart Parcel Management</h1>
                <p class="hero-subtitle">Secure, convenient, and automated package delivery with 24/7 access to smart lockers in Eldoret</p>
                <div class="hero-buttons">
                    <a href="signup.php" class="cta-button primary">Get Started</a>
                    <a href="#features" class="cta-button secondary">Learn More</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2>Why Choose Vuma Lockers?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Access</h3>
                    <p>One-time passwords ensure only you can access your packages</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bell"></i>
                    <h3>Instant Notifications</h3>
                    <p>Get real-time updates when your package arrives</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h3>24/7 Availability</h3>
                    <p>Pick up your parcels anytime that suits you</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Convenient Locations</h3>
                    <p>Multiple locker stations across Eldoret</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="about" class="how-it-works">
        <div class="container">
            <h2>How It Works</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Package Arrival</h3>
                    <p>Your package is securely stored in one of our smart lockers</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Receive OTP</h3>
                    <p>Get a unique 4-digit code sent to your registered contact</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Collect Package</h3>
                    <p>Visit the locker station and enter your code to retrieve your package</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Vuma Parcel Lockers. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>