<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

$user_name = $_SESSION['user_name'];

// Get all users
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

// Process user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Prevent admin from deleting themselves
        if ($user_id != $_SESSION['user_id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "User deleted successfully!";
                
                // Refresh users list
                $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Error deleting user: " . $e->getMessage();
            }
        } else {
            $error = "You cannot delete your own account!";
        }
    }
    
    if (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $success = "User role updated successfully!";
            
            // Refresh users list
            $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error updating user role: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        
        .users-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .role-admin { background: #d31621; color: white; }
        .role-customer { background: #006b54; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
        
        .role-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .current-user {
            background: #fff3cd !important;
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
                <h1>👥 Manage Users</h1>
                <p>View and manage all system users</p>
            </div>
            <div class="header-actions">
                <a href="admin_add_user.php" class="action-btn">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
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
        
        <?php if (empty($users)): ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <h3>No users found</h3>
                <p>There are no users in the system yet.</p>
                <a href="admin_add_user.php" class="action-btn" style="display: inline-flex; margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> Add First User
                </a>
            </div>
        <?php else: ?>
            <div class="users-table">
                <div class="table-header">
                    <div>Name</div>
                    <div>Email</div>
                    <div>Phone</div>
                    <div>Role</div>
                    <div>Joined</div>
                    <div>Actions</div>
                </div>
                
                <?php foreach ($users as $user): ?>
                    <div class="table-row <?php echo $user['id'] == $_SESSION['user_id'] ? 'current-user' : ''; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <br><small style="color: #d31621;">(Current User)</small>
                            <?php endif; ?>
                        </div>
                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                        <div><?php echo $user['phone'] ?: 'N/A'; ?></div>
                        <div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role" class="role-select" onchange="this.form.submit()" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                    <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </div>
                        <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        <div class="action-buttons">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn-small btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #666; font-size: 0.8rem;">Current User</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; color: #666;">
                <p>Showing <?php echo count($users); ?> users in the system</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>