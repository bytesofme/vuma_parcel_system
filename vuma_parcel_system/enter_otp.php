<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';

checkAuth();

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user's parcels that have OTP codes (ready for pickup)
try {
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location 
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        WHERE p.recipient_email = ? AND p.status = 'delivered' AND p.otp_code IS NOT NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_email]);
    $pickup_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pickup_parcels = [];
}

// Process OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code'])) {
    $entered_otp = trim($_POST['otp_code']);
    
    if (!empty($entered_otp)) {
        try {
            // Find parcel with this OTP for the current user
            $stmt = $pdo->prepare("
                SELECT p.*, l.locker_number, l.location 
                FROM parcels p 
                LEFT JOIN lockers l ON p.locker_id = l.id 
                WHERE p.recipient_email = ? AND p.otp_code = ? AND p.status = 'delivered'
            ");
            $stmt->execute([$user_email, $entered_otp]);
            $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parcel) {
                // OTP is valid! Update parcel status and free the locker
                $pdo->beginTransaction();
                
                // Update parcel status to picked_up
                $stmt = $pdo->prepare("UPDATE parcels SET status = 'picked_up', otp_code = NULL WHERE id = ?");
                $stmt->execute([$parcel['id']]);
                
                // Free up the locker
                if ($parcel['locker_id']) {
                    $stmt = $pdo->prepare("UPDATE lockers SET status = 'available' WHERE id = ?");
                    $stmt->execute([$parcel['locker_id']]);
                }
                
                $pdo->commit();
                
                $success = "🎉 SUCCESS! Locker opened! Parcel #{$parcel['tracking_number']} has been collected.";
                
                // Refresh the parcels list
                $stmt = $pdo->prepare("
                    SELECT p.*, l.locker_number, l.location 
                    FROM parcels p 
                    LEFT JOIN lockers l ON p.locker_id = l.id 
                    WHERE p.recipient_email = ? AND p.status = 'delivered' AND p.otp_code IS NOT NULL
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$user_email]);
                $pickup_parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else {
                $error = "❌ Invalid OTP code. Please check the code and try again.";
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter an OTP code";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter OTP - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .otp-container {
            max-width: 800px;
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
            text-align: center;
        }
        
        .otp-entry-form {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .otp-input {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 1rem;
            border: 3px solid #006b54;
            border-radius: 15px;
            width: 200px;
            margin: 0 auto 1.5rem;
            letter-spacing: 0.5rem;
        }
        
        .submit-btn {
            padding: 1.2rem 3rem;
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #005a46, #008f70);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,107,84,0.4);
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
        
        .otp-display {
            background: #f8f9fa;
            padding: 1.5rem;
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
        
        .alert {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            justify-content: center;
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
        
        .instructions {
            background: #fff3cd;
            color: #856404;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #ffc107;
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

    <div class="otp-container">
        <div class="page-header">
            <h1>🔐 Collect Your Parcel</h1>
            <p>Enter your OTP code at the locker station to collect your package</p>
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
        
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> How to Collect Your Parcel:</h3>
            <ol style="text-align: left; margin: 1rem 0; padding-left: 1.5rem;">
                <li>Find your OTP code below or in "My Parcels"</li>
                <li>Go to the locker station at the specified location</li>
                <li>Enter the 4-digit OTP code on the locker keypad</li>
                <li>The locker will open automatically</li>
                <li>Collect your package and close the locker door</li>
            </ol>
        </div>
        
        <div class="otp-entry-form">
            <h2 style="color: #006b54; margin-bottom: 2rem;">
                <i class="fas fa-keyboard"></i> Enter OTP Code
            </h2>
            <form method="POST" action="">
                <input type="text" name="otp_code" class="otp-input" 
                       placeholder="0000" maxlength="4" pattern="[0-9]{4}" 
                       title="Enter 4-digit OTP code" required>
                <br>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-lock-open"></i> Open Locker
                </button>
            </form>
        </div>
        
        <?php if (empty($pickup_parcels)): ?>
            <div class="no-parcels">
                <i class="fas fa-inbox"></i>
                <h3>No parcels ready for pickup</h3>
                <p>You don't have any parcels with active OTP codes.</p>
                <p>When you receive a parcel, the OTP code will appear here.</p>
                <a href="my_parcels.php" style="display: inline-block; margin-top: 1.5rem; padding: 1rem 2rem; background: #006b54; color: white; text-decoration: none; border-radius: 10px;">
                    <i class="fas fa-box"></i> Check My Parcels
                </a>
            </div>
        <?php else: ?>
            <h2 style="text-align: center; color: #006b54; margin: 2rem 0 1rem 0;">
                <i class="fas fa-box"></i> Your Parcels Ready for Pickup
            </h2>
            <div class="parcels-grid">
                <?php foreach ($pickup_parcels as $parcel): ?>
                    <div class="parcel-card">
                        <div class="parcel-header">
                            <div class="parcel-id">#<?php echo $parcel['tracking_number']; ?></div>
                            <div style="padding: 0.5rem 1rem; background: #e8f8ef; color: #00b894; border-radius: 20px; font-weight: bold;">
                                📦 Ready for Pickup
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
                                    Locker <?php echo $parcel['locker_number']; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Location</span>
                                <span class="detail-value"><?php echo $parcel['location']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Delivery Date</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($parcel['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="otp-display">
                            <div class="detail-label">Your Pickup Code</div>
                            <div class="otp-code"><?php echo $parcel['otp_code']; ?></div>
                            <small style="color: #666;">Enter this code at the locker station</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>