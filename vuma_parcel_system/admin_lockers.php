<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

$user_name = $_SESSION['user_name'];

// Get all lockers
try {
    $stmt = $pdo->prepare("SELECT * FROM lockers ORDER BY locker_number");
    $stmt->execute();
    $lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lockers = [];
}

// Process locker actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_locker'])) {
        $locker_number = trim($_POST['locker_number']);
        $location = trim($_POST['location']);
        
        if (!empty($locker_number) && !empty($location)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO lockers (locker_number, location, status) VALUES (?, ?, 'available')");
                $stmt->execute([$locker_number, $location]);
                $success = "Locker added successfully!";
                
                // Refresh lockers list
                $stmt = $pdo->prepare("SELECT * FROM lockers ORDER BY locker_number");
                $stmt->execute();
                $lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Error adding locker: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all fields";
        }
    }
    
    if (isset($_POST['update_status'])) {
        $locker_id = $_POST['locker_id'];
        $new_status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE lockers SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $locker_id]);
            $success = "Locker status updated successfully!";
            
            // Refresh lockers list
            $stmt = $pdo->prepare("SELECT * FROM lockers ORDER BY locker_number");
            $stmt->execute();
            $lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error updating locker status: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_locker'])) {
        $locker_id = $_POST['locker_id'];
        
        try {
            // Check if locker is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) as in_use FROM parcels WHERE locker_id = ? AND status IN ('in_transit', 'delivered')");
            $stmt->execute([$locker_id]);
            $in_use = $stmt->fetch(PDO::FETCH_ASSOC)['in_use'];
            
            if ($in_use == 0) {
                $stmt = $pdo->prepare("DELETE FROM lockers WHERE id = ?");
                $stmt->execute([$locker_id]);
                $success = "Locker deleted successfully!";
                
                // Refresh lockers list
                $stmt = $pdo->prepare("SELECT * FROM lockers ORDER BY locker_number");
                $stmt->execute();
                $lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = "Cannot delete locker: It is currently assigned to active parcels";
            }
        } catch (PDOException $e) {
            $error = "Error deleting locker: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lockers - Admin Panel</title>
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
        
        .lockers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .locker-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            border-top: 5px solid #006b54;
        }
        
        .locker-card:hover {
            transform: translateY(-5px);
        }
        
        .locker-number {
            font-size: 2rem;
            font-weight: bold;
            color: #006b54;
            margin-bottom: 0.5rem;
        }
        
        .locker-location {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .status-available { background: #e8f8ef; color: #00b894; }
        .status-occupied { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #ffeaea; color: #d63031; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        
        .btn-edit { background: #ffc107; color: #000; }
        .btn-delete { background: #dc3545; color: white; }
        
        .btn-small:hover {
            transform: translateY(-1px);
        }
        
        .add-locker-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #006b54, #00a884);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #005a46, #008f70);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,107,84,0.4);
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
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .status-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
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
                <h1>🔐 Manage Lockers</h1>
                <p>View and manage all locker stations</p>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="action-btn" style="background: linear-gradient(135deg, #d31621, #ff6b6b);">
                    <i class="fas fa-chart-bar"></i> Dashboard
                </a>
            </div>
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
        
        <!-- Add Locker Form -->
        <div class="add-locker-form">
            <h3 style="color: #006b54; margin-bottom: 1.5rem;">
                <i class="fas fa-plus"></i> Add New Locker
            </h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem; align-items: end;">
                    <div class="form-group">
                        <label for="locker_number">Locker Number</label>
                        <input type="text" id="locker_number" name="locker_number" class="form-control" 
                               placeholder="e.g., A01, B12" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               placeholder="e.g., Eldoret Main Station" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_locker" class="submit-btn" style="width: 100%;">
                            <i class="fas fa-plus"></i> Add Locker
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (empty($lockers)): ?>
            <div class="no-data">
                <i class="fas fa-lock"></i>
                <h3>No lockers found</h3>
                <p>There are no lockers in the system yet. Add your first locker above.</p>
            </div>
        <?php else: ?>
            <div class="lockers-grid">
                <?php foreach ($lockers as $locker): ?>
                    <div class="locker-card">
                        <div class="locker-number">Locker <?php echo $locker['locker_number']; ?></div>
                        <div class="locker-location"><?php echo htmlspecialchars($locker['location']); ?></div>
                        
                        <div class="status-badge status-<?php echo $locker['status']; ?>">
                            <?php 
                            $statusText = [
                                'available' => '✅ Available',
                                'occupied' => '📦 Occupied', 
                                'maintenance' => '🔧 Maintenance'
                            ];
                            echo $statusText[$locker['status']];
                            ?>
                        </div>
                        
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="locker_id" value="<?php echo $locker['id']; ?>">
                                <select name="status" class="status-select" onchange="this.form.submit()">
                                    <option value="available" <?php echo $locker['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="occupied" <?php echo $locker['status'] == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                    <option value="maintenance" <?php echo $locker['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="locker_id" value="<?php echo $locker['id']; ?>">
                                <button type="submit" name="delete_locker" class="btn-small btn-delete" onclick="return confirm('Are you sure you want to delete this locker?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; color: #666;">
                <p>Showing <?php echo count($lockers); ?> lockers in the system</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>