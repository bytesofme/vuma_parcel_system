<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parcel_id'])) {
    $parcel_id = $_POST['parcel_id'];
    
    try {
        // Get parcel details to free locker if assigned
        $stmt = $pdo->prepare("SELECT locker_id FROM parcels WHERE id = ?");
        $stmt->execute([$parcel_id]);
        $parcel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the parcel
        $stmt = $pdo->prepare("DELETE FROM parcels WHERE id = ?");
        $stmt->execute([$parcel_id]);
        
        // Free the locker if it was assigned
        if ($parcel && $parcel['locker_id']) {
            $stmt = $pdo->prepare("UPDATE lockers SET status = 'available' WHERE id = ?");
            $stmt->execute([$parcel['locker_id']]);
        }
        
        header('Location: admin_parcels.php?success=Parcel deleted successfully');
        exit();
        
    } catch (PDOException $e) {
        header('Location: admin_parcels.php?error=Error deleting parcel: ' . $e->getMessage());
        exit();
    }
} else {
    header('Location: admin_parcels.php?error=Invalid request');
    exit();
}
?>