<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';

checkAuth();

// If user is admin, redirect to admin dashboard
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user parcel statistics
try {
    // Total parcels
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM parcels WHERE recipient_email = ?");
    $stmt->execute([$user_email]);
    $total_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Waiting parcels (delivered but not picked up)
    $stmt = $pdo->prepare("SELECT COUNT(*) as waiting FROM parcels WHERE recipient_email = ? AND status = 'delivered'");
    $stmt->execute([$user_email]);
    $waiting_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['waiting'];
    
    // Delivered parcels (picked up)
    $stmt = $pdo->prepare("SELECT COUNT(*) as delivered FROM parcels WHERE recipient_email = ? AND status = 'picked_up'");
    $stmt->execute([$user_email]);
    $delivered_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['delivered'];
    
    // In transit parcels
    $stmt = $pdo->prepare("SELECT COUNT(*) as transit FROM parcels WHERE recipient_email = ? AND status = 'in_transit'");
    $stmt->execute([$user_email]);
    $transit_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['transit'];
    
    // Parcels ready for pickup (with OTP)
    $stmt = $pdo->prepare("SELECT COUNT(*) as pickup_ready FROM parcels WHERE recipient_email = ? AND status = 'delivered' AND otp_code IS NOT NULL");
    $stmt->execute([$user_email]);
    $pickup_ready = $stmt->fetch(PDO::FETCH_ASSOC)['pickup_ready'];
    
    // Recent parcels
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location 
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        WHERE p.recipient_email = ?
        ORDER BY p.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_email]);
    $recent_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parcels for the navigation notification
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE recipient_email = ? AND status = 'delivered' AND otp_code IS NOT NULL");
    $stmt->execute([$user_email]);
    $pickup_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $total_parcels = 0;
    $waiting_parcels = 0;
    $delivered_parcels = 0;
    $transit_parcels = 0;
    $pickup_ready = 0;
    $recent_parcels = [];
    $pickup_parcels = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 80px auto 2rem;
            padding: 0 2rem;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-content h1 {
            margin: 0;
            color: #006b54;
        }
        
        .header-content p {
            margin: 0.5rem 0 0 0;
            color: #666;
        }
        
        .logout-btn {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #d31621, #ff6b6b);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211,22,33,0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card.waiting {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }
        
        .stat-card.delivered {
            background: linear-gradient(135deg, #00b894, #00a085);
        }
        
        .stat-card.transit {
            background: linear-gradient(135deg, #0984e3, #086cc3);
        }
        
        .stat-card.total {
            background: linear-gradient(135deg, #6c5ce7, #5b4cda);
        }
        
        .stat-card.pickup {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .dashboard-nav {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .nav-links-dash {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,107,84,0.4);
        }
        
        .nav-btn.otp {
            background: linear-gradient(135deg, #d31621, #ff6b6b);
        }
        
        .nav-btn.locker {
            background: linear-gradient(135deg, #0984e3, #086cc3);
        }
        
        .nav-btn.history {
            background: linear-gradient(135deg, #6c5ce7, #5b4cda);
        }
        
        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-text {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .quick-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        
        .quick-stat:hover {
            transform: translateY(-2px);
        }
        
        .quick-stat .number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .quick-stat.waiting .number { color: #d31621; }
        .quick-stat.delivered .number { color: #006b54; }
        .quick-stat.transit .number { color: #0984e3; }
        .quick-stat.otp .number { color: #6c5ce7; }
        .quick-stat.total .number { color: #00b894; }
        
        .quick-stat .label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .parcel-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
            border-left: 4px solid #006b54;
        }
        
        .parcel-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .parcel-info {
            flex: 1;
        }
        
        .parcel-tracking {
            font-weight: bold;
            color: #006b54;
            font-size: 1.1rem;
        }
        
        .parcel-details {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-delivered { background: #e8f8ef; color: #00b894; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        
        .otp-badge {
            background: #d31621;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .notification-badge {
            background: white;
            color: #d31621;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .nav-links-dash {
                flex-direction: column;
            }
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }
            
            .parcel-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-box-open"></i>
                <span>VUMA LOCKERS</span>
            </div>
            <div class="nav-links">
                <span style="color: white;">Welcome, <?php echo $user_name; ?></span>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Welcome, <?php echo $user_name; ?>! 👋</h1>
                <p class="welcome-text">Manage your parcels and locker access from your personal dashboard</p>
            </div>
            <a href="includes/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card waiting">
                <i class="fas fa-clock"></i>
                <h3>Waiting for Pickup</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0;"><?php echo $waiting_parcels; ?></p>
            </div>
            
            <div class="stat-card delivered">
                <i class="fas fa-check-circle"></i>
                <h3>Successfully Delivered</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0;"><?php echo $delivered_parcels; ?></p>
            </div>
            
            <div class="stat-card transit">
                <i class="fas fa-shipping-fast"></i>
                <h3>In Transit</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0;"><?php echo $transit_parcels; ?></p>
            </div>
            
            <div class="stat-card total">
                <i class="fas fa-boxes"></i>
                <h3>Total Orders</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0;"><?php echo $total_parcels; ?></p>
            </div>

            <div class="stat-card pickup">
                <i class="fas fa-key"></i>
                <h3>Ready with OTP</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0;"><?php echo $pickup_ready; ?></p>
            </div>
        </div>
        
        <div class="dashboard-nav">
            <div class="nav-links-dash">
                <a href="my_parcels.php" class="nav-btn">
                    <i class="fas fa-box"></i> My Parcels
                </a>
                <a href="enter_otp.php" class="nav-btn otp">
                    <i class="fas fa-lock-open"></i> Collect Parcel
                    <?php if (count($pickup_parcels) > 0): ?>
                        <span class="notification-badge"><?php echo count($pickup_parcels); ?></span>
                    <?php endif; ?>
                </a>
                <a href="locker_status.php" class="nav-btn locker">
                    <i class="fas fa-map-marker-alt"></i> Locker Status
                </a>
                <a href="order_history.php" class="nav-btn history">
                    <i class="fas fa-history"></i> Order History
                </a>
            </div>
        </div>
        
        <div class="content-section">
            <h2 style="margin-bottom: 1.5rem; color: #006b54;">
                <i class="fas fa-box-open"></i> Recent Parcels
            </h2>
            
            <?php if (empty($recent_parcels)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No parcels yet</h3>
                    <p>Your parcels will appear here once they are delivered to our locker stations</p>
                </div>
            <?php else: ?>
                <div>
                    <?php foreach ($recent_parcels as $parcel): ?>
                        <div class="parcel-item">
                            <div class="parcel-info">
                                <div class="parcel-tracking">#<?php echo $parcel['tracking_number']; ?></div>
                                <div class="parcel-details">
                                    From: <?php echo htmlspecialchars($parcel['sender_name']); ?>
                                    <?php if ($parcel['locker_number']): ?>
                                        • Locker: <?php echo $parcel['locker_number']; ?> (<?php echo $parcel['location']; ?>)
                                    <?php endif; ?>
                                    • <?php echo date('M j, Y', strtotime($parcel['created_at'])); ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="status-badge status-<?php echo $parcel['status']; ?>">
                                    <?php 
                                    $statusText = [
                                        'in_transit' => '🚚 In Transit',
                                        'delivered' => '📦 Delivered', 
                                        'picked_up' => '✅ Picked Up'
                                    ];
                                    echo $statusText[$parcel['status']];
                                    ?>
                                </div>
                                <?php if ($parcel['status'] === 'delivered' && $parcel['otp_code']): ?>
                                    <div class="otp-badge">
                                        OTP: <?php echo $parcel['otp_code']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="my_parcels.php" class="nav-btn" style="display: inline-flex; width: auto;">
                        <i class="fas fa-eye"></i> View All Parcels
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="quick-stats">
            <div class="quick-stat waiting">
                <span class="number"><?php echo $waiting_parcels; ?></span>
                <span class="label">Waiting for Pickup</span>
            </div>
            <div class="quick-stat delivered">
                <span class="number"><?php echo $delivered_parcels; ?></span>
                <span class="label">Successfully Delivered</span>
            </div>
            <div class="quick-stat transit">
                <span class="number"><?php echo $transit_parcels; ?></span>
                <span class="label">Currently in Transit</span>
            </div>
            <div class="quick-stat otp">
                <span class="number"><?php echo $pickup_ready; ?></span>
                <span class="label">Ready with OTP</span>
            </div>
            <div class="quick-stat total">
                <span class="number"><?php echo $total_parcels; ?></span>
                <span class="label">Total Orders</span>
            </div>
        </div>
    </div>
</body>
</html>