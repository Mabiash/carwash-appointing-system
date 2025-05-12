<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('/auth/login.php', 'Please login to access this page', 'warning');
}

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/user/dashboard.php', 'Invalid appointment', 'danger');
}

$appointment_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get appointment details and verify ownership
$stmt = $pdo->prepare("
    SELECT * FROM appointments 
    WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
");
$stmt->execute([$appointment_id, $user_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    redirect('/user/dashboard.php', 'Appointment not found or cannot be cancelled', 'danger');
}

// Cancel the appointment
$stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
if ($stmt->execute([$appointment_id])) {
    redirect('/user/dashboard.php', 'Appointment cancelled successfully', 'success');
} else {
    redirect('/user/dashboard.php', 'Failed to cancel appointment', 'danger');
}