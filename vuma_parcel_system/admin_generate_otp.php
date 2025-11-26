<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

$user_name = $_SESSION['user_name'];

// Get parcels that are delivered but don't have OTP yet
try {
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location 
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        WHERE p.status = 'delivered' AND p.otp_code IS NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $parcels_needing_otp = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parcels_needing_otp = [];
}

// Process OTP generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parcel_id'])) {
    $parcel_id = $_POST['parcel_id'];
    
    // Generate 4-digit OTP
    $otp_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    
    try {
        $stmt = $pdo->prepare("UPDATE parcels SET otp_code = ? WHERE id = ?");
        $stmt->execute([$otp_code, $parcel_id]);
        
        $success = "OTP generated successfully! Code: <strong>{$otp_code}</strong>";
        
        // Refresh the parcels list
        $stmt = $pdo->prepare("
            SELECT p.*, l.locker_number, l.location 
            FROM parcels p 
            LEFT JOIN lockers l ON p.locker_id = l.id 
            WHERE p.status = 'delivered' AND p.otp_code IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $parcels_needing_otp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate OTP - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
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
            padding: 2rem;
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
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .parcel-id {
            font-weight: bold;
            color: #006b54;
            font-size: 1.3rem;
        }
        
        .parcel-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
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
        
        .generate-otp-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #d31621, #ff6b6b);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .generate-otp-btn:hover {
            background: linear-gradient(135deg, #b3121a, #e05555);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211,22,33,0.4);
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .alert-success {
            background: #e8f8ef;
            color: #00b894;
            border: 1px solid #00b894;
        }
        
        .alert-error {
            background: #ffeaea;
            color: #d63031;
            border: 1px solid #ff7675;
        }
        
        .no-parcels {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-parcels i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .otp-generated {
            background: #e8f8ef;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1rem;
        }
        
        .otp-code {
            font-size: 2rem;
            font-weight: bold;
            color: #d31621;
            letter-spacing: 0.5rem;
            margin: 0.5rem 0;
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
                <a href="admin_dashboard.php" style="color: white;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <span style="color: white;">Admin: <?php echo $user_name; ?></span>
                <a href="includes/logout.php" class="login-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1>🔐 Generate OTP Codes</h1>
            <p>Create secure 4-digit codes for parcel pickup</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($parcels_needing_otp)): ?>
            <div class="no-parcels">
                <i class="fas fa-check-circle"></i>
                <h3>All Good! 🎉</h3>
                <p>All delivered parcels already have OTP codes generated.</p>
                <p style="margin-top: 1rem;">New OTP codes will be needed when new parcels are marked as delivered.</p>
                <a href="admin_parcels.php" style="display: inline-block; margin-top: 1.5rem; padding: 1rem 2rem; background: #006b54; color: white; text-decoration: none; border-radius: 10px;">
                    <i class="fas fa-box"></i> Manage Parcels
                </a>
            </div>
        <?php else: ?>
            <div class="parcels-grid">
                <?php foreach ($parcels_needing_otp as $parcel): ?>
                    <div class="parcel-card">
                        <div class="parcel-header">
                            <div class="parcel-id">#<?php echo $parcel['tracking_number']; ?></div>
                            <div style="padding: 0.5rem 1rem; background: #e8f8ef; color: #00b894; border-radius: 20px; font-weight: bold;">
                                📦 Ready for OTP
                            </div>
                        </div>
                        
                        <div class="parcel-details">
                            <div class="detail-item">
                                <span class="detail-label">Sender</span>
                                <span class="detail-value"><?php echo htmlspecialchars($parcel['sender_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Recipient</span>
                                <span class="detail-value"><?php echo htmlspecialchars($parcel['recipient_email']); ?></span>
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
                        </div>
                        
                        <form method="POST" action="" style="text-align: center;">
                            <input type="hidden" name="parcel_id" value="<?php echo $parcel['id']; ?>">
                            <button type="submit" class="generate-otp-btn">
                                <i class="fas fa-key"></i> Generate 4-Digit OTP
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; color: #666;">
                <p>Showing <?php echo count($parcels_needing_otp); ?> parcels needing OTP codes</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>