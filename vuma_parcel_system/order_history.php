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
</html><?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
checkAuth();

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get all user parcels for history
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
    <title>Order History - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .history-container {
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
        
        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,107,84,0.3);
        }
        
        .parcels-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-delivered { background: #e8f8ef; color: #00b894; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-data i {
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

    <div class="history-container">
        <div class="page-header">
            <h1>📋 Order History</h1>
            <p>Your complete parcel delivery history</p>
        </div>
        
        <?php
        // Calculate statistics
        $total_parcels = count($parcels);
        $delivered = array_filter($parcels, fn($p) => $p['status'] === 'delivered');
        $picked_up = array_filter($parcels, fn($p) => $p['status'] === 'picked_up');
        $in_transit = array_filter($parcels, fn($p) => $p['status'] === 'in_transit');
        ?>
        
        <div class="history-stats">
            <div class="stat-card">
                <i class="fas fa-boxes"></i>
                <h3>Total Orders</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $total_parcels; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3>In Transit</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo count($in_transit); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-truck"></i>
                <h3>Delivered</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo count($delivered); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>Picked Up</h3>
                <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo count($picked_up); ?></p>
            </div>
        </div>
        
        <?php if (empty($parcels)): ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>No order history</h3>
                <p>You haven't received any parcels yet.</p>
                <p>Your parcel history will appear here once you start receiving packages.</p>
            </div>
        <?php else: ?>
            <div class="parcels-table">
                <div class="table-header">
                    <div>Tracking #</div>
                    <div>Sender</div>
                    <div>Locker</div>
                    <div>Status</div>
                    <div>Date</div>
                </div>
                
                <?php foreach ($parcels as $parcel): ?>
                    <div class="table-row">
                        <div>
                            <strong>#<?php echo $parcel['tracking_number']; ?></strong>
                        </div>
                        <div><?php echo htmlspecialchars($parcel['sender_name']); ?></div>
                        <div>
                            <?php if ($parcel['locker_number']): ?>
                                Locker <?php echo $parcel['locker_number']; ?>
                                <br><small><?php echo $parcel['location']; ?></small>
                            <?php else: ?>
                                <span style="color: #666;">Not assigned</span>
                            <?php endif; ?>
                        </div>
                        <div>
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
                        <div><?php echo date('M j, Y', strtotime($parcel['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; color: #666;">
                <p>Showing <?php echo count($parcels); ?> parcels in your history</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>