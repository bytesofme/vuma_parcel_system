<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

$user_name = $_SESSION['user_name'];

// Get available lockers
try {
    $stmt = $pdo->prepare("SELECT * FROM lockers WHERE status = 'available' ORDER BY locker_number");
    $stmt->execute();
    $available_lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $available_lockers = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_number = 'VUMA' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $sender_name = trim($_POST['sender_name']);
    $recipient_email = trim($_POST['recipient_email']);
    $recipient_phone = trim($_POST['recipient_phone']);
    $locker_id = $_POST['locker_id'] ?: null;
    $status = 'in_transit';

    // Validate required fields
    if (empty($sender_name) || empty($recipient_email)) {
        $error = "Sender name and recipient email are required";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert parcel
            $stmt = $pdo->prepare("
                INSERT INTO parcels (tracking_number, sender_name, recipient_email, recipient_phone, locker_id, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tracking_number, $sender_name, $recipient_email, $recipient_phone, $locker_id, $status]);

            // Update locker status if assigned
            if ($locker_id) {
                $stmt = $pdo->prepare("UPDATE lockers SET status = 'occupied' WHERE id = ?");
                $stmt->execute([$locker_id]);
            }

            // Commit transaction
            $pdo->commit();

            $success = "Parcel added successfully! Tracking Number: <strong>{$tracking_number}</strong>";
            
            // Clear form
            $_POST = [];

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Parcel - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
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
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #006b54;
            box-shadow: 0 0 0 3px rgba(0,107,84,0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #005a46, #008f70);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,107,84,0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
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
        
        .locker-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 0.5rem;
        }
        
        .optional {
            color: #666;
            font-size: 0.9rem;
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
            <h1>📦 Add New Parcel</h1>
            <p>Register a new parcel delivery in the system</p>
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
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="sender_name">
                        <i class="fas fa-user"></i> Sender Name *
                    </label>
                    <input type="text" id="sender_name" name="sender_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>" 
                           placeholder="Enter sender name (e.g., Amazon Kenya, Jumia)" required>
                </div>
                
                <div class="form-group">
                    <label for="recipient_email">
                        <i class="fas fa-envelope"></i> Recipient Email *
                    </label>
                    <input type="email" id="recipient_email" name="recipient_email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['recipient_email'] ?? ''); ?>" 
                           placeholder="Enter recipient email address" required>
                </div>
                
                <div class="form-group">
                    <label for="recipient_phone">
                        <i class="fas fa-phone"></i> Recipient Phone Number
                        <span class="optional">(optional)</span>
                    </label>
                    <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['recipient_phone'] ?? ''); ?>" 
                           placeholder="Enter recipient phone number">
                </div>
                
                <div class="form-group">
                    <label for="locker_id">
                        <i class="fas fa-lock"></i> Assign to Locker
                        <span class="optional">(optional - assign later)</span>
                    </label>
                    <select id="locker_id" name="locker_id" class="form-control">
                        <option value="">-- Select a locker --</option>
                        <?php foreach ($available_lockers as $locker): ?>
                            <option value="<?php echo $locker['id']; ?>" 
                                    <?php echo ($_POST['locker_id'] ?? '') == $locker['id'] ? 'selected' : ''; ?>>
                                Locker <?php echo $locker['locker_number']; ?> - <?php echo $locker['location']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (!empty($available_lockers)): ?>
                        <div class="locker-info">
                            <strong>Available Lockers:</strong> <?php echo count($available_lockers); ?> lockers available
                        </div>
                    <?php else: ?>
                        <div class="locker-info" style="background: #fff3cd; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> No available lockers. Parcel will be marked as "In Transit".
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-info-circle"></i> Parcel Information
                    </label>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 10px;">
                        <p><strong>Tracking Number:</strong> Will be generated automatically</p>
                        <p><strong>Initial Status:</strong> In Transit</p>
                        <p><strong>OTP Code:</strong> Will be generated when parcel is delivered</p>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus"></i> Add Parcel to System
                </button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; color: #666;">
            <p>Need to manage existing parcels? <a href="admin_parcels.php" style="color: #006b54;">Go to Parcel Management</a></p>
        </div>
    </div>
</body>
</html>