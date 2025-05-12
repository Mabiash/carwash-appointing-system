<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_GET['service_id']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Service ID and date are required']);
    exit;
}

$service_id = (int)$_GET['service_id'];
$date = sanitize($_GET['date']);

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

// Validate service exists
$stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND active = 1");
$stmt->execute([$service_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Invalid service']);
    exit;
}

// Get available time slots
$available_slots = get_available_timeslots($pdo, $date, $service_id);

// Return the time slots
echo json_encode($available_slots);