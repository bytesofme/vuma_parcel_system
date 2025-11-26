<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
checkAuth();

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locker Status - Vuma Parcel Lockers</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-container {
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
        
        .lockers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .locker-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
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
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .status-available { background: #e8f8ef; color: #00b894; }
        .status-occupied { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #ffeaea; color: #d63031; }
        
        .location-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            color: #006b54;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #006b54;
            padding-bottom: 0.5rem;
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

    <div class="status-container">
        <div class="page-header">
            <h1>📍 Locker Status</h1>
            <p>Check availability at our locker stations</p>
        </div>
        
        <?php
        // Get all lockers grouped by location
        try {
            $stmt = $pdo->prepare("SELECT * FROM lockers ORDER BY location, locker_number");
            $stmt->execute();
            $lockers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by location
            $locations = [];
            foreach ($lockers as $locker) {
                $locations[$locker['location']][] = $locker;
            }
        } catch (PDOException $e) {
            $locations = [];
        }
        ?>
        
        <?php if (empty($locations)): ?>
            <div style="text-align: center; padding: 3rem; color: #666; background: white; border-radius: 15px;">
                <i class="fas fa-map-marker-alt" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3>No locker stations available</h3>
                <p>Locker stations will appear here when they are added to the system.</p>
            </div>
        <?php else: ?>
            <?php foreach ($locations as $location => $location_lockers): ?>
                <div class="location-section">
                    <h2 class="section-title">🏢 <?php echo htmlspecialchars($location); ?></h2>
                    <div class="lockers-grid">
                        <?php foreach ($location_lockers as $locker): ?>
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
                                
                                <div style="color: #666; font-size: 0.9rem;">
                                    <?php if ($locker['status'] === 'available'): ?>
                                        Ready for new parcels
                                    <?php elseif ($locker['status'] === 'occupied'): ?>
                                        Currently in use
                                    <?php else: ?>
                                        Under maintenance
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="background: #e8f8ef; padding: 2rem; border-radius: 15px; margin-top: 2rem;">
            <h3 style="color: #006b54; margin-bottom: 1rem;">📍 Our Locations</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Eldoret Main Station</strong>
                    <p style="color: #666; margin: 0.5rem 0 0 0;">City Centre, Next to Post Office</p>
                    <p style="color: #666; margin: 0;">Open 24/7</p>
                </div>
                <div>
                    <strong>Eldoret Town Branch</strong>
                    <p style="color: #666; margin: 0.5rem 0 0 0;">Market Street, Opposite Mall</p>
                    <p style="color: #666; margin: 0;">Open 24/7</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>