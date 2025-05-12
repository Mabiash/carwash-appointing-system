<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('auth/login.php', 'Please login to book an appointment', 'warning');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get selected service (if any)
$selected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;

// Get all active services
$stmt = $pdo->query("SELECT * FROM services WHERE active = 1 ORDER BY price");
$services = $stmt->fetchAll();

// Initialize variables
$errors = [];
$vehicle_type = '';
$vehicle_model = '';
$notes = '';
$appointment_date = '';
$appointment_time = '08:22'; // Use 24-hour format for time input

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $vehicle_type = sanitize($_POST['vehicle_type'] ?? '');
    $vehicle_model = sanitize($_POST['vehicle_model'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $appointment_date = sanitize($_POST['appointment_date'] ?? '');
    $appointment_time = sanitize($_POST['appointment_time'] ?? '');
    
    // Validate service
    if (empty($service_id)) {
        $errors['service_id'] = 'Please select a service';
    } else {
        // Check if service exists
        $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND active = 1");
        $stmt->execute([$service_id]);
        if (!$stmt->fetch()) {
            $errors['service_id'] = 'Invalid service selected';
        }
    }
    
    // Validate vehicle type
    if (empty($vehicle_type)) {
        $errors['vehicle_type'] = 'Vehicle type is required';
    }
    
    // Validate date
    if (empty($appointment_date)) {
        $errors['appointment_date'] = 'Please select a date';
    } else {
        // Check if date is in the future
        $selected_date = new DateTime($appointment_date);
        $today = new DateTime(date('Y-m-d'));
        
        if ($selected_date < $today) {
            $errors['appointment_date'] = 'Please select a future date';
        }
    }
    
    // Validate time
    if (empty($appointment_time)) {
        $errors['appointment_time'] = 'Please select a time slot';
    } else if (!empty($appointment_date)) {
        // Check if time slot is available
        if (!is_timeslot_available($pdo, $appointment_date, $appointment_time, $service_id)) {
            $errors['appointment_time'] = 'This time slot is no longer available. Please select another time.';
        }
    }
    
    // If no errors, create the appointment
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO appointments 
            (user_id, service_id, vehicle_type, vehicle_model, appointment_date, appointment_time, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        if ($stmt->execute([
            $user_id, 
            $service_id, 
            $vehicle_type, 
            $vehicle_model, 
            $appointment_date, 
            $appointment_time, 
            $notes
        ])) {
            // Redirect to dashboard with success message
            redirect('dashboard.php', 'Appointment booked successfully! We will confirm your appointment soon.', 'success');
        } else {
            $errors['general'] = 'Failed to book appointment. Please try again.';
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h1 class="h3 mb-4">Book an Appointment</h1>
        
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        
        <form id="booking-form" method="POST" action="" novalidate>
            <input type="hidden" id="service_id" name="service_id" value="<?php echo htmlspecialchars($selected_service_id); ?>">
            <input type="hidden" id="appointment_time" name="appointment_time" value="<?php echo htmlspecialchars($appointment_time); ?>">
            
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Select a Service</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['service_id'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['service_id']; ?></div>
                    <?php endif; ?>
                    
                    <div id="selected-service-container" class="mb-3 <?php echo empty($selected_service_id) ? 'd-none' : ''; ?>">
                        <p>Selected Service: <span id="selected-service" class="fw-bold"></span></p>
                        <button type="button" id="change-service" class="btn btn-sm btn-outline-primary">Change Service</button>
                    </div>
                    
                    <div id="service-selection" class="<?php echo !empty($selected_service_id) ? 'd-none' : ''; ?>">
                        <div class="row">
                            <?php foreach($services as $service): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card service-select-card <?php echo ($selected_service_id == $service['id']) ? 'border-primary' : ''; ?>" 
                                     data-service-id="<?php echo $service['id']; ?>"
                                     data-service-name="<?php echo htmlspecialchars($service['name']); ?>">
                                    <div class="card-body">
                                        <h3 class="h6 mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                                        <p class="small text-muted mb-2"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">₱<?php echo number_format($service['price'], 2); ?></span>
                                            <span class="small text-muted"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars($service['duration']); ?> min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Vehicle Information</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                        <select class="form-select <?php echo isset($errors['vehicle_type']) ? 'is-invalid' : ''; ?>" 
                                id="vehicle_type" name="vehicle_type" required>
                            <option value="" selected disabled>Select vehicle type</option>
                            <option value="Sedan" <?php echo $vehicle_type === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                            <option value="SUV" <?php echo $vehicle_type === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                            <option value="Truck" <?php echo $vehicle_type === 'Truck' ? 'selected' : ''; ?>>Truck</option>
                            <option value="Van" <?php echo $vehicle_type === 'Van' ? 'selected' : ''; ?>>Van</option>
                            <option value="Motorcycle" <?php echo $vehicle_type === 'Motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                            <option value="Other" <?php echo $vehicle_type === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (isset($errors['vehicle_type'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['vehicle_type']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vehicle_model" class="form-label">Vehicle Model (Optional)</label>
                        <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" 
                               value="<?php echo htmlspecialchars($vehicle_model); ?>" placeholder="e.g., Toyota Camry">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Any special requests or information we should know"><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Select Date & Time</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Date *</label>
                        <input type="date" class="form-control <?php echo isset($errors['appointment_date']) ? 'is-invalid' : ''; ?>" 
                               id="appointment_date" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date); ?>" required>
                        <?php if (isset($errors['appointment_date'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['appointment_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointment_time" class="form-label">Time Slot *</label>
                        <input type="time" class="form-control <?php echo isset($errors['appointment_time']) ? 'is-invalid' : ''; ?>" 
                               id="appointment_time" name="appointment_time" value="<?php echo htmlspecialchars($appointment_time); ?>" required>
                        <?php if (isset($errors['appointment_time'])): ?>
                        <div class="alert alert-danger mt-2"><?php echo $errors['appointment_time']; ?></div>
                        <?php endif; ?>
                        
                        <div id="time-slots" class="mt-3">
                            <p class="text-center text-muted">Please select a service and date first</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Book Appointment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize service selection
    const serviceCards = document.querySelectorAll('.service-select-card');
    const serviceIdInput = document.getElementById('service_id');
    const selectedServiceContainer = document.getElementById('selected-service-container');
    const selectedServiceText = document.getElementById('selected-service');
    const serviceSelection = document.getElementById('service-selection');
    const changeServiceBtn = document.getElementById('change-service');
    
    // Set initial selected service text if a service is pre-selected
    if (serviceIdInput.value) {
        const selectedCard = document.querySelector(`.service-select-card[data-service-id="${serviceIdInput.value}"]`);
        if (selectedCard) {
            selectedServiceText.textContent = selectedCard.getAttribute('data-service-name');
            selectedServiceContainer.classList.remove('d-none');
            serviceSelection.classList.add('d-none');
        }
    }
    
    // Add click event to service cards
    serviceCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            serviceCards.forEach(c => c.classList.remove('border-primary'));
            
            // Add active class to selected card
            this.classList.add('border-primary');
            
            // Set hidden input value
            serviceIdInput.value = this.getAttribute('data-service-id');
            
            // Update selected service text and show container
            selectedServiceText.textContent = this.getAttribute('data-service-name');
            selectedServiceContainer.classList.remove('d-none');
            serviceSelection.classList.add('d-none');
            
            // If date is already selected, update time slots
            const dateInput = document.getElementById('appointment_date');
            if (dateInput && dateInput.value) {
                updateTimeSlots();
            }
        });
    });
    
    // Change service button
    if (changeServiceBtn) {
        changeServiceBtn.addEventListener('click', function() {
            selectedServiceContainer.classList.add('d-none');
            serviceSelection.classList.remove('d-none');
            // Clear selected service hidden input
            serviceIdInput.value = '';
            selectedServiceText.textContent = '';
        });
    }

    // Placeholder function for updating time slots
    function updateTimeSlots() {
        // This function should fetch/update available time slots
        // Currently, we just clear the placeholder text
        const timeSlots = document.getElementById('time-slots');
        timeSlots.innerHTML = '<p class="text-center text-muted">Available time slots will appear here.</p>';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

