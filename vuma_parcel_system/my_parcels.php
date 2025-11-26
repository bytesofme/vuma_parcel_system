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

// Get user's parcels
try {
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location 
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        WHERE p.recipient_email = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_email]);
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parcels = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Parcels - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .parcels-container {
            max-width: 1200px;
            margin: 80px auto 2rem;
            padding: 0 2rem;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .parcels-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .parcel-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #006b54;
            transition: transform 0.3s;
        }
        
        .parcel-card:hover {
            transform: translateY(-3px);
        }
        
        .parcel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .parcel-id {
            font-weight: bold;
            color: #006b54;
            font-size: 1.2rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .status-delivered { background: #e8f8ef; color: #00b894; }
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        
        .parcel-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .otp-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .otp-code {
            font-size: 1.5rem;
            font-weight: bold;
            color: #d31621;
            text-align: center;
            letter-spacing: 0.5rem;
            margin: 0.5rem 0;
        }
        
        .no-parcels {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-parcels i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-box-open"></i>
                <span>VUMA LOCKERS</span>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" style="color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <span style="color: white;"><?php echo $user_name; ?></span>
                <a href="includes/logout.php" class="login-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="parcels-container">
        <div class="page-header">
            <h1>📦 My Parcels</h1>
            <p>Track and manage your package deliveries</p>
        </div>
        
        <?php if (empty($parcels)): ?>
            <div class="no-parcels">
                <i class="fas fa-inbox"></i>
                <h3>No parcels found</h3>
                <p>You don't have any parcels yet. They will appear here once delivered to our locker stations.</p>
            </div>
        <?php else: ?>
            <div class="parcels-grid">
                <?php foreach ($parcels as $parcel): ?>
                    <div class="parcel-card">
                        <div class="parcel-header">
                            <div class="parcel-id">#<?php echo $parcel['tracking_number']; ?></div>
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
                        </div>
                        
                        <div class="parcel-details">
                            <div class="detail-item">
                                <span class="detail-label">Sender</span>
                                <span class="detail-value"><?php echo htmlspecialchars($parcel['sender_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Locker</span>
                                <span class="detail-value">
                                    <?php echo $parcel['locker_number'] ? 'Locker ' . $parcel['locker_number'] : 'Not assigned'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Location</span>
                                <span class="detail-value"><?php echo $parcel['location'] ?: 'Eldoret Main Station'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Delivery Date</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($parcel['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($parcel['status'] === 'delivered' && $parcel['otp_code']): ?>
                            <div class="otp-section">
                                <div style="text-align: center;">
                                    <div class="detail-label">Your Pickup Code</div>
                                    <div class="otp-code"><?php echo $parcel['otp_code']; ?></div>
                                    <small style="color: #666;">Use this code to collect your package from the locker</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>