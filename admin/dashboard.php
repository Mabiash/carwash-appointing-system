<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not admin
if (!is_admin()) {
    redirect('/carwash-appoinment/index.php', 'You do not have permission to access this page', 'danger');
}

// Get dashboard statistics

// Total appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments");
$total_appointments = $stmt->fetch()['count'];

// Pending appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$pending_appointments = $stmt->fetch()['count'];

// Today's appointments
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?");
$stmt->execute([$today]);
$today_appointments = $stmt->fetch()['count'];

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$total_customers = $stmt->fetch()['count'];

// Recent appointments
$stmt = $pdo->query("
    SELECT a.*, s.name as service_name, s.price, u.name as customer_name, u.email as customer_email 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recent_appointments = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">Admin Dashboard</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow stat-card bg-primary text-white px-3 py-2">
            <div class="position-relative">
                <div class="stat-value"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
                <i class="fas fa-calendar-check stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow stat-card bg-warning text-white px-3 py-2">
            <div class="position-relative">
                <div class="stat-value"><?php echo $pending_appointments; ?></div>
                <div class="stat-label">Pending Approval</div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow stat-card bg-success text-white px-3 py-2">
            <div class="position-relative">
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
                <i class="fas fa-car-side stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow stat-card bg-info text-white px-3 py-2">
            <div class="position-relative">
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Recent Appointments</h2>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_appointments)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">No appointments found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recent_appointments as $appointment): ?>
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
            
                                        <?php echo get_status_badge($appointment['status']); ?>
                            
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="appointments.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                            Manage Appointments
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $total_appointments; ?></span>
                    </a>
                    <a href="services.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-car-wash me-2 text-primary"></i>
                        Manage Services
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users me-2 text-primary"></i>
                            Manage Users
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $total_customers; ?></span>
                    </a>
                </div>
                
                <div class="card bg-light mt-4">
                    <div class="card-body">
                        <h3 class="h6 mb-3">Today's Schedule</h3>
                        <?php
                        // Get today's appointments
                        $stmt = $pdo->prepare("
                            SELECT a.*, s.name as service_name, u.name as customer_name
                            FROM appointments a
                            JOIN services s ON a.service_id = s.id
                            JOIN users u ON a.user_id = u.id
                            WHERE a.appointment_date = ?
                            ORDER BY a.appointment_time ASC
                        ");
                        $stmt->execute([$today]);
                        $today_schedule = $stmt->fetchAll();
                        
                        if (empty($today_schedule)):
                        ?>
                        <p class="text-muted mb-0">No appointments scheduled for today.</p>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($today_schedule as $appointment): ?>
                            <li class="list-group-item bg-transparent px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo format_time($appointment['appointment_time']); ?></strong>
                                        <div><?php echo htmlspecialchars($appointment['customer_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($appointment['service_name']); ?></div>
                                    </div>
                                    <div><?php echo get_status_badge($appointment['status']); ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>