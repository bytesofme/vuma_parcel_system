<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';

checkAuth();

// Redirect if not admin
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$user_name = $_SESSION['user_name'];

// Get statistics
try {
    // Total parcels
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM parcels");
    $stmt->execute();
    $total_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active parcels (in transit + delivered)
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM parcels WHERE status IN ('in_transit', 'delivered')");
    $stmt->execute();
    $active_parcels = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Available lockers
    $stmt = $pdo->prepare("SELECT COUNT(*) as available FROM lockers WHERE status = 'available'");
    $stmt->execute();
    $available_lockers = $stmt->fetch(PDO::FETCH_ASSOC)['available'];
    
    // Parcels needing OTP
    $stmt = $pdo->prepare("SELECT COUNT(*) as need_otp FROM parcels WHERE status = 'delivered' AND otp_code IS NULL");
    $stmt->execute();
    $need_otp = $stmt->fetch(PDO::FETCH_ASSOC)['need_otp'];
    
    // Recent parcels
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location 
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $total_parcels = 0;
    $active_parcels = 0;
    $total_users = 0;
    $available_lockers = 0;
    $need_otp = 0;
    $recent_parcels = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-content h1 {
            margin: 0;
            color: #d31621;
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
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .admin-stat-card {
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .admin-stat-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-stat-card.parcels {
            background: linear-gradient(135deg, #006b54, #00a884);
        }
        
        .admin-stat-card.lockers {
            background: linear-gradient(135deg, #d31621, #ff6b6b);
        }
        
        .admin-stat-card.users {
            background: linear-gradient(135deg, #ffc107, #e6ac00);
        }
        
        .admin-stat-card.otp {
            background: linear-gradient(135deg, #6c5ce7, #5b4cda);
        }
        
        .admin-stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .admin-nav {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .nav-links-admin {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-btn-admin {
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .nav-btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .nav-btn-admin.parcels {
            background: linear-gradient(135deg, #006b54, #00a884);
        }
        
        .nav-btn-admin.add {
            background: linear-gradient(135deg, #00b894, #00a085);
        }
        
        .nav-btn-admin.otp {
            background: linear-gradient(135deg, #6c5ce7, #5b4cda);
        }
        
        .nav-btn-admin.lockers {
            background: linear-gradient(135deg, #d31621, #ff6b6b);
        }
        
        .nav-btn-admin.users {
            background: linear-gradient(135deg, #ffc107, #e6ac00);
            color: #000;
        }
        
        .admin-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #006b54;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,107,84,0.4);
        }
        
        .action-btn.lockers {
            background: #d31621;
        }
        
        .action-btn.users {
            background: #ffc107;
            color: #000;
        }
        
        .action-btn.otp {
            background: #6c5ce7;
        }
        
        .recent-parcels {
            margin-top: 1.5rem;
        }
        
        .parcel-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .parcel-item:hover {
            transform: translateX(5px);
        }
        
        .parcel-info {
            flex: 1;
        }
        
        .parcel-tracking {
            font-weight: bold;
            color: #006b54;
        }
        
        .parcel-sender {
            font-size: 0.9rem;
            color: #666;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-delivered { background: #e8f8ef; color: #00b894; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        
        .quick-stat {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
        
        .quick-actions {
            display: grid;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .quick-action-btn {
            display: block;
            padding: 0.8rem;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        
        .quick-action-btn.parcels {
            background: #006b54;
        }
        
        .quick-action-btn.otp {
            background: #6c5ce7;
        }
        
        .quick-action-btn.lockers {
            background: #d31621;
        }
        
        .quick-action-btn.users {
            background: #ffc107;
            color: #000;
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
            .admin-content {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-links-admin {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-box-open"></i>
                <span>VUMA LOCKERS - ADMIN</span>
            </div>
            <div class="nav-links">
                <span style="color: white;">Admin: <?php echo $user_name; ?></span>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Admin Dashboard 🛠️</h1>
                <p>Manage lockers, parcels, and system operations</p>
            </div>
            <a href="includes/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="admin-stats">
            <div class="admin-stat-card parcels">
                <i class="fas fa-box"></i>
                <h3>Total Parcels</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $total_parcels; ?></p>
            </div>
            <div class="admin-stat-card lockers">
                <i class="fas fa-lock"></i>
                <h3>Available Lockers</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $available_lockers; ?></p>
            </div>
            <div class="admin-stat-card users">
                <i class="fas fa-users"></i>
                <h3>Total Users</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $total_users; ?></p>
            </div>
            <div class="admin-stat-card otp">
                <i class="fas fa-key"></i>
                <h3>Need OTP</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $need_otp; ?></p>
            </div>
        </div>
        
        <!-- UPDATED NAVIGATION WITH GENERATE OTP LINK -->
        <div class="admin-nav">
            <div class="nav-links-admin">
                <a href="admin_parcels.php" class="nav-btn-admin parcels">
                    <i class="fas fa-box"></i> Manage Parcels
                </a>
                <a href="admin_add_parcel.php" class="nav-btn-admin add">
                    <i class="fas fa-plus"></i> Add New Parcel
                </a>
                <a href="admin_generate_otp.php" class="nav-btn-admin otp">
                    <i class="fas fa-key"></i> Generate OTP
                    <?php if ($need_otp > 0): ?>
                        <span class="notification-badge"><?php echo $need_otp; ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_lockers.php" class="nav-btn-admin lockers">
                    <i class="fas fa-lock"></i> Manage Lockers
                </a>
                <a href="admin_users.php" class="nav-btn-admin users">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
        </div>
        
        <div class="admin-content">
            <div class="content-card">
                <h2 style="color: #d31621; margin-bottom: 1.5rem;">
                    <i class="fas fa-cog"></i> System Management
                </h2>
                <div style="display: grid; gap: 1rem;">
                    <a href="admin_parcels.php" class="action-btn">
                        <i class="fas fa-box"></i> Manage Parcels
                    </a>
                    <a href="admin_lockers.php" class="action-btn lockers">
                        <i class="fas fa-lock"></i> Manage Lockers
                    </a>
                    <a href="admin_users.php" class="action-btn users">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="admin_generate_otp.php" class="action-btn otp">
                        <i class="fas fa-key"></i> Generate OTP
                        <?php if ($need_otp > 0): ?>
                            <span style="background: white; color: #6c5ce7; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem; margin-left: auto;">
                                <?php echo $need_otp; ?> pending
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="recent-parcels">
                    <h3 style="color: #006b54; margin: 1.5rem 0 1rem 0;">
                        <i class="fas fa-clock"></i> Recent Parcels
                    </h3>
                    <?php if (empty($recent_parcels)): ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No recent parcels</p>
                            <a href="admin_add_parcel.php" class="quick-action-btn parcels" style="display: inline-block; width: auto; margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add First Parcel
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_parcels as $parcel): ?>
                            <div class="parcel-item">
                                <div class="parcel-info">
                                    <div class="parcel-tracking">#<?php echo $parcel['tracking_number']; ?></div>
                                    <div class="parcel-sender">
                                        From: <?php echo htmlspecialchars($parcel['sender_name']); ?>
                                        <?php if ($parcel['locker_number']): ?>
                                            • Locker: <?php echo $parcel['locker_number']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo $parcel['status']; ?>">
                                    <?php 
                                    $statusText = [
                                        'in_transit' => '🚚 Transit',
                                        'delivered' => '📦 Delivered', 
                                        'picked_up' => '✅ Picked Up'
                                    ];
                                    echo $statusText[$parcel['status']];
                                    ?>
                                    <?php if ($parcel['status'] === 'delivered' && $parcel['otp_code']): ?>
                                        <br><small>OTP: <?php echo $parcel['otp_code']; ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="admin_parcels.php" style="color: #006b54; text-decoration: none; font-weight: 600;">
                                View All Parcels →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="content-card">
                <h2 style="color: #006b54; margin-bottom: 1.5rem;">
                    <i class="fas fa-chart-bar"></i> Quick Stats
                </h2>
                <div style="display: grid; gap: 0.5rem;">
                    <div class="quick-stat">
                        <span>Total Parcels:</span>
                        <strong><?php echo $total_parcels; ?></strong>
                    </div>
                    <div class="quick-stat">
                        <span>Active Parcels:</span>
                        <strong><?php echo $active_parcels; ?></strong>
                    </div>
                    <div class="quick-stat">
                        <span>Available Lockers:</span>
                        <strong><?php echo $available_lockers; ?></strong>
                    </div>
                    <div class="quick-stat">
                        <span>Total Users:</span>
                        <strong><?php echo $total_users; ?></strong>
                    </div>
                    <div class="quick-stat">
                        <span>Parcels Needing OTP:</span>
                        <strong><?php echo $need_otp; ?></strong>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e9ecef;">
                    <h3 style="color: #d31621; margin-bottom: 1rem;">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                    <div class="quick-actions">
                        <a href="admin_add_parcel.php" class="quick-action-btn parcels">
                            <i class="fas fa-plus"></i> Add New Parcel
                        </a>
                        <a href="admin_generate_otp.php" class="quick-action-btn otp">
                            <i class="fas fa-key"></i> Generate OTP
                            <?php if ($need_otp > 0): ?>
                                <span style="background: white; color: #6c5ce7; padding: 0.1rem 0.3rem; border-radius: 8px; font-size: 0.7rem; margin-left: auto;">
                                    <?php echo $need_otp; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="admin_lockers.php" class="quick-action-btn lockers">
                            <i class="fas fa-lock"></i> Manage Lockers
                        </a>
                        <a href="admin_users.php" class="quick-action-btn users">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>