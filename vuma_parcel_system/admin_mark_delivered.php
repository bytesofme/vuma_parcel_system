<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: admin_parcels.php?error=Invalid parcel ID');
    exit();
}

$parcel_id = $_GET['id'];

try {
    // Check if parcel exists and is in transit
    $stmt = $pdo->prepare("SELECT * FROM parcels WHERE id = ? AND status = 'in_transit'");
    $stmt->execute([$parcel_id]);
    $parcel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parcel) {
        header('Location: admin_parcels.php?error=Parcel not found or already delivered');
        exit();
    }

    // Check if parcel has a locker assigned
    if (!$parcel['locker_id']) {
        header('Location: admin_parcels.php?error=Cannot mark as delivered without locker assignment');
        exit();
    }

    // Generate 4-digit OTP
    $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    // Update parcel status and OTP
    $stmt = $pdo->prepare("UPDATE parcels SET status = 'delivered', otp_code = ? WHERE id = ?");
    $stmt->execute([$otp, $parcel_id]);

    header('Location: admin_parcels.php?success=Parcel marked as delivered and OTP generated: ' . $otp);
    exit();

} catch (PDOException $e) {
    header('Location: admin_parcels.php?error=Database error: ' . $e->getMessage());
    exit();
}
?>