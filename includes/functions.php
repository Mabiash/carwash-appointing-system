<?php
// Start session if not already started
function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Function to check if user is logged in
function is_logged_in() {
    ensure_session_started();
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    ensure_session_started();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to redirect with a message
function redirect($location, $message = '', $type = 'info') {
    ensure_session_started();
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $location");
    exit;
}

// Function to display message
function display_message() {
    ensure_session_started();
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
        echo $message;
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Function to sanitize user input
function sanitize($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Function to check if a time slot is available
function is_timeslot_available($pdo, $date, $time, $service_id) {
    // Get service duration
    $stmt = $pdo->prepare("SELECT duration FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    
    if (!$service) {
        return false;
    }
    
    $duration = $service['duration'];
    
    // Calculate end time
    $start_time = new DateTime($time);
    $end_time = clone $start_time;
    $end_time->add(new DateInterval('PT' . $duration . 'M'));
    
    $start_time_str = $start_time->format('H:i:s');
    $end_time_str = $end_time->format('H:i:s');
    
    // Check if there's any overlapping appointment
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.appointment_date = ?
        AND a.status IN ('pending', 'confirmed')
        AND (
            (a.appointment_time < ? AND ADDTIME(a.appointment_time, SEC_TO_TIME(s.duration * 60)) > ?)
            OR
            (a.appointment_time >= ? AND a.appointment_time < ?)
        )
    ");
    $stmt->execute([$date, $end_time_str, $start_time_str, $start_time_str, $end_time_str]);
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}

// Function to get available time slots for a specific date and service
function get_available_timeslots($pdo, $date, $service_id) {
    // Business hours (8:00 AM to 6:00 PM)
    $start_hour = 8;
    $end_hour = 18;
    
    // Get service duration
    $stmt = $pdo->prepare("SELECT duration FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    
    if (!$service) {
        return [];
    }
    
    $duration = $service['duration'];
    $interval = 30; // Slot interval in minutes
    
    $available_slots = [];
    
    // Generate time slots
    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $interval) {
            $time = sprintf("%02d:%02d:00", $hour, $minute);
            
            // Check if this slot would extend beyond business hours
            $start_time = new DateTime($time);
            $end_time = clone $start_time;
            $end_time->add(new DateInterval('PT' . $duration . 'M'));
            
            if ($end_time->format('H') >= $end_hour) {
                continue;
            }
            
            // Check if the slot is available
            if (is_timeslot_available($pdo, $date, $time, $service_id)) {
                $available_slots[] = $time;
            }
        }
    }
    
    return $available_slots;
}

// Function to format date for display
function format_date($date, $format = 'd M Y') {
    $date_obj = new DateTime($date);
    return $date_obj->format($format);
}

// Function to format time for display
function format_time($time, $format = 'h:i A') {
    $time_obj = new DateTime($time);
    return $time_obj->format($format);
}

// Function to get appointment status badge
function get_status_badge($status) {
    $badges = [
        'pending' => 'bg-warning p-2',
        'confirmed' => 'bg-primary p-2',
        'completed' => 'bg-success p-2',
        'cancelled' => 'bg-danger p-2'
    ];
    
    $badge_class = $badges[$status] ?? 'bg-secondary p-2';
    
    return "<span class='badge {$badge_class}'>" . ucfirst($status) . "</span>";
}