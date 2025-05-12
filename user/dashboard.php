<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('/auth/login.php', 'Please login to view your dashboard', 'warning');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, s.name as service_name, s.price, s.duration 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.user_id = ? AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$user_id]);
$upcoming = $stmt->fetchAll();

// Get past appointments
$stmt = $pdo->prepare("
    SELECT a.*, s.name as service_name, s.price, s.duration 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.user_id = ? AND a.status IN ('completed', 'cancelled')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$past = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">My Dashboard</h1>
            <a href="book.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Book New Appointment
            </a>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Upcoming Appointments</h2>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming)): ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="far fa-calendar-alt text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-muted mb-3">You don't have any upcoming appointments.</p>
                    <a href="book.php" class="btn btn-primary">Book Now</a>
                </div>
                <?php else: ?>
                    <?php foreach($upcoming as $appointment): ?>
                    <div class="card mb-3 appointment-card <?php echo $appointment['status']; ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h3 class="h6 mb-1"><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                    <div class="text-muted small mb-2">
                                        <?php echo htmlspecialchars($appointment['vehicle_type']); ?> 
                                        <?php if (!empty($appointment['vehicle_model'])): ?>
                                            - <?php echo htmlspecialchars($appointment['vehicle_model']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo format_date($appointment['appointment_date']); ?> at 
                                        <?php echo format_time($appointment['appointment_time']); ?>
                                    </div>
                                    <div>
                                        <?php echo get_status_badge($appointment['status']); ?>
                                        <span class="ms-2 text-muted">
                                            <i class="far fa-clock me-1"></i><?php echo $appointment['duration']; ?> min
                                        </span>
                                        <span class="ms-2">
                                        ₱<?php echo number_format($appointment['price'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2" 
                                            data-bs-toggle="modal" data-bs-target="#viewAppointment<?php echo $appointment['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                    <?php if ($appointment['status'] !== 'cancelled'): ?>
                                    <a href="/user/cancel-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal for appointment details -->
                    <div class="modal fade" id="viewAppointment<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Appointment Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <h6>Service</h6>
                                        <p><?php echo htmlspecialchars($appointment['service_name']); ?></p>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <h6>Date</h6>
                                            <p><?php echo format_date($appointment['appointment_date']); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <h6>Time</h6>
                                            <p><?php echo format_time($appointment['appointment_time']); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <h6>Vehicle Type</h6>
                                            <p><?php echo htmlspecialchars($appointment['vehicle_type']); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <h6>Vehicle Model</h6>
                                            <p><?php echo htmlspecialchars($appointment['vehicle_model'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <h6>Price</h6>
                                            <p>₱<?php echo number_format($appointment['price'], 2); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <h6>Duration</h6>
                                            <p><?php echo $appointment['duration']; ?> minutes</p>
                                        </div>
                                    </div>
                                    <?php if (!empty($appointment['notes'])): ?>
                                    <div class="mb-3">
                                        <h6>Notes</h6>
                                        <p><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <h6>Status</h6>
                                        <p><?php echo get_status_badge($appointment['status']); ?></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <?php if ($appointment['status'] !== 'cancelled'): ?>
                                    <a href="/user/cancel-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                        Cancel Appointment
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($past)): ?>
        <div class="card shadow">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Past Appointments</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($past as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td>
                                    <?php echo format_date($appointment['appointment_date']); ?><br>
                                    <small class="text-muted"><?php echo format_time($appointment['appointment_time']); ?></small>
                                </td>
                                <td><?php echo get_status_badge($appointment['status']); ?></td>
                                <td>₱<?php echo number_format($appointment['price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>