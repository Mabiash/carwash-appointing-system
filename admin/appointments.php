<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not admin
if (!is_admin()) {
    redirect('/index.php', 'You do not have permission to access this page', 'danger');
}

// Handle appointment status update
if (isset($_POST['update_status']) && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = sanitize($_POST['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $appointment_id])) {
            redirect('appointments.php', 'Appointment status updated successfully', 'success');
        } else {
            redirect('appointments.php', 'Failed to update appointment status', 'danger');
        }
    } else {
        redirect('appointments.php', 'Invalid status', 'danger');
    }
}

// Set filter defaults
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query based on filters
$query = "
    SELECT a.*, s.name as service_name, s.price, s.duration, u.name as customer_name, u.email as customer_email 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

// Add date filter
if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Add order by
$query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

// Prepare and execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">Manage Appointments</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer name, email or service">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h2 class="h5 mb-0">Appointments</h2>
            <?php if (!empty($date_filter)): ?>
            <div class="ms-3 d-flex align-items-center">
                <span id="current-date" class="fw-bold"><?php echo date('l, F j, Y', strtotime($date_filter)); ?></span>
                <div class="ms-2">
                    <button id="prev-date" class="btn btn-sm btn-outline-secondary me-1" title="Previous Day">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button id="next-date" class="btn btn-sm btn-outline-secondary" title="Next Day">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <span class="badge bg-primary"><?php echo count($appointments); ?> appointments</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($appointments)): ?>
        <div class="text-center p-4">
            <div class="mb-3">
                <i class="far fa-calendar-alt text-muted" style="font-size: 3rem;"></i>
            </div>
            <p class="text-muted">No appointments found for the selected filters.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($appointment['customer_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($appointment['customer_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                        <td>
                            <?php echo format_date($appointment['appointment_date']); ?><br>
                            <small class="text-muted"><?php echo format_time($appointment['appointment_time']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($appointment['vehicle_type']); ?>
                            <?php if (!empty($appointment['vehicle_model'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($appointment['vehicle_model']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo get_status_badge($appointment['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                    <li>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" name="update_status" class="dropdown-item text-primary">
                                                <i class="fas fa-check me-1"></i>Confirm
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                    <li>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" name="update_status" class="dropdown-item text-success">
                                                <i class="fas fa-check-double me-1"></i>Mark as Complete
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] !== 'cancelled' && $appointment['status'] !== 'completed'): ?>
                                    <li>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" name="update_status" class="dropdown-item text-danger" 
                                                    onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <!-- Modal -->
                            <div class="modal fade" id="viewModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Appointment Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <h6>Customer</h6>
                                                <p>
                                                    <?php echo htmlspecialchars($appointment['customer_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($appointment['customer_email']); ?></small>
                                                </p>
                                            </div>
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
                                                    <p>$<?php echo number_format($appointment['price'], 2); ?></p>
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
                                            <div class="mb-0">
                                                <h6>Created</h6>
                                                <p><?php echo date('M d, Y g:i A', strtotime($appointment['created_at'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" name="update_status" class="btn btn-primary">
                                                    Confirm Appointment
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($appointment['status'] === 'confirmed'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" name="update_status" class="btn btn-success">
                                                    Mark as Complete
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($appointment['status'] !== 'cancelled' && $appointment['status'] !== 'completed'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-danger" 
                                                        onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                    Cancel Appointment
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden form for date navigation -->
<form id="date-filter-form" method="GET" action="">
    <input type="hidden" id="filter-date" name="date" value="<?php echo $date_filter; ?>">
    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeDateNav();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>