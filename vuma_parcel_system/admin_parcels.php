[file name]: admin_parcels.php
[file content begin]
<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

$user_name = $_SESSION['user_name'];

// Get all parcels with user and locker info
try {
    $stmt = $pdo->prepare("
        SELECT p.*, l.locker_number, l.location, l.status as locker_status
        FROM parcels p 
        LEFT JOIN lockers l ON p.locker_id = l.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parcels = [];
}

// Get available lockers
try {
    $stmt = $pdo->prepare("SELECT * FROM lockers WHERE status = 'available'");
    $stmt->execute();
    $available_lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $available_lockers = [];
}

// Show success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parcels - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,107,84,0.4);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #d31621, #ff6b6b);
        }
        
        .parcels-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
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
            display: inline-block;
            min-width: 120px;
        }
        
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-delivered { background: #e8f8ef; color: #00b894; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: white !important;
            font-weight: 600;
        }
        
        .btn-edit { background: #ffc107; color: #000 !important; }
        .btn-deliver { background: #28a745; color: white !important; }
        .btn-delete { background: #dc3545; color: white !important; }
        .btn-otp { background: #006b54; color: white !important; }
        
        .btn-small:hover {
            transform: translateY(-1px);
            opacity: 0.9;
            color: white !important;
        }
        
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
        
        .delete-form {
            display: inline;
        }
        
        .otp-display {
            background: #f8f9fa;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #d31621;
            margin-top: 0.3rem;
            display: inline-block;
        }
        
        .locker-info {
            font-weight: 600;
            color: #006b54;
        }
        
        .locker-location {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.2rem;
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
            <div>
                <h1>📦 Manage Parcels</h1>
                <p>View and manage all parcel deliveries in the system</p>
            </div>
            <div class="header-actions">
                <a href="admin_add_parcel.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add New Parcel
                </a>
                <a href="admin_dashboard.php" class="action-btn secondary">
                    <i class="fas fa-chart-bar"></i> Dashboard
                </a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($parcels)): ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>No parcels found</h3>
                <p>There are no parcels in the system yet.</p>
                <a href="admin_add_parcel.php" class="action-btn" style="display: inline-flex; margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Add First Parcel
                </a>
            </div>
        <?php else: ?>
            <div class="parcels-table">
                <div class="table-header">
                    <div>Tracking #</div>
                    <div>Sender</div>
                    <div>Recipient</div>
                    <div>Locker</div>
                    <div>Status</div>
                    <div>Actions</div>
                </div>
                
                <?php foreach ($parcels as $parcel): ?>
                    <div class="table-row">
                        <div>
                            <strong>#<?php echo htmlspecialchars($parcel['tracking_number']); ?></strong>
                        </div>
                        <div><?php echo htmlspecialchars($parcel['sender_name']); ?></div>
                        <div>
                            <div><?php echo htmlspecialchars($parcel['recipient_email']); ?></div>
                            <?php if ($parcel['recipient_phone']): ?>
                                <div style="font-size: 0.8rem; color: #666; margin-top: 0.2rem;">
                                    📞 <?php echo htmlspecialchars($parcel['recipient_phone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($parcel['locker_number']): ?>
                                <div class="locker-info">Locker <?php echo htmlspecialchars(trim($parcel['locker_number'])); ?></div>
                                <div class="locker-location">📍 <?php echo htmlspecialchars($parcel['location']); ?></div>
                            <?php else: ?>
                                <span style="color: #666; font-style: italic;">Not assigned</span>
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
                            <?php if ($parcel['otp_code']): ?>
                                <div class="otp-display">
                                    🔐 OTP: <?php echo htmlspecialchars($parcel['otp_code']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="action-buttons">
                            <?php if ($parcel['status'] === 'in_transit'): ?>
                                <a href="admin_mark_delivered.php?id=<?php echo $parcel['id']; ?>" class="btn-small btn-deliver">
                                    <i class="fas fa-truck"></i> Mark Delivered
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($parcel['status'] === 'delivered' && !$parcel['otp_code']): ?>
                                <a href="admin_generate_otp.php?parcel_id=<?php echo $parcel['id']; ?>" class="btn-small btn-otp">
                                    <i class="fas fa-key"></i> Generate OTP
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" action="admin_delete_parcel.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this parcel?')">
                                <input type="hidden" name="parcel_id" value="<?php echo $parcel['id']; ?>">
                                <button type="submit" class="btn-small btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; color: #666;">
                <p>Showing <?php echo count($parcels); ?> parcels in the system</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
[file content end]