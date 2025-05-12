<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not admin
if (!is_admin()) {
    redirect('/index.php', 'You do not have permission to access this page', 'danger');
}

// Initialize variables
$errors = [];
$service = [
    'id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'duration' => '',
    'image' => '',
    'active' => 1
];

// Get service for editing if ID is provided
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $service_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        $service = $result;
    } else {
        redirect('services.php', 'Service not found', 'danger');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['add_service']) || isset($_POST['update_service'])) {
        // Add or update service
        $service['name'] = sanitize($_POST['name'] ?? '');
        $service['description'] = sanitize($_POST['description'] ?? '');
        $service['price'] = filter_var($_POST['price'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $service['duration'] = (int)($_POST['duration'] ?? 0);
        $service['image'] = sanitize($_POST['image'] ?? '');
        $service['active'] = isset($_POST['active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($service['name'])) {
            $errors['name'] = 'Service name is required';
        }
        
        if (empty($service['price']) || $service['price'] <= 0) {
            $errors['price'] = 'Price must be greater than zero';
        }
        
        if (empty($service['duration']) || $service['duration'] <= 0) {
            $errors['duration'] = 'Duration must be greater than zero';
        }
        
        // If no errors, proceed with database operation
        if (empty($errors)) {
            if (isset($_POST['update_service'])) {
                // Update existing service
                $service_id = (int)$_POST['service_id'];
                $stmt = $pdo->prepare("
                    UPDATE services 
                    SET name = ?, description = ?, price = ?, duration = ?, image = ?, active = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([
                    $service['name'],
                    $service['description'],
                    $service['price'],
                    $service['duration'],
                    $service['image'],
                    $service['active'],
                    $service_id
                ])) {
                    redirect('services.php', 'Service updated successfully', 'success');
                } else {
                    $errors['general'] = 'Failed to update service. Please try again.';
                }
            } else {
                // Add new service
                $stmt = $pdo->prepare("
                    INSERT INTO services (name, description, price, duration, image, active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([
                    $service['name'],
                    $service['description'],
                    $service['price'],
                    $service['duration'],
                    $service['image'],
                    $service['active']
                ])) {
                    redirect('services.php', 'Service added successfully', 'success');
                } else {
                    $errors['general'] = 'Failed to add service. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['delete_service'])) {
        // Delete service
        $service_id = (int)$_POST['service_id'];
        
        // Check if service has any appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $appointment_count = $stmt->fetchColumn();
        
        if ($appointment_count > 0) {
            redirect('services.php', 'Cannot delete service with existing appointments. Consider deactivating it instead.', 'warning');
        } else {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            if ($stmt->execute([$service_id])) {
                redirect('services.php', 'Service deleted successfully', 'success');
            } else {
                redirect('services.php', 'Failed to delete service', 'danger');
            }
        }
    }
}

// Get all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY name");
$services = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">Manage Services</h1>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
            <i class="fas fa-plus-circle me-1"></i>Add New Service
        </button>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $errors['general']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3">No services found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($services as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($s['image'])): ?>
                                    <div class="me-3">
                                        <img src="<?php echo $s['image']; ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" 
                                             class="rounded" width="50" height="50" style="object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="small text-muted"><?php echo substr(htmlspecialchars($s['description']), 0, 50); ?><?php echo strlen($s['description']) > 50 ? '...' : ''; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($s['price'], 2); ?></td>
                            <td><?php echo $s['duration']; ?> min</td>
                            <td>
                                <?php if ($s['active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="services.php?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $s['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $s['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete the service "<?php echo htmlspecialchars($s['name']); ?>"?</p>
                                                <p class="text-danger mb-0">This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                                    <button type="submit" name="delete_service" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Service Modal (Add/Edit) -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit Service' : 'Add New Service'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <?php if (isset($_GET['edit'])): ?>
                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Service Name *</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($service['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (₱) *</label>
                            <input type="number" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                   id="price" name="price" value="<?php echo $service['price']; ?>" step="0.01" min="0" required>
                            <?php if (isset($errors['price'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Duration (minutes) *</label>
                            <input type="number" class="form-control <?php echo isset($errors['duration']) ? 'is-invalid' : ''; ?>" 
                                   id="duration" name="duration" value="<?php echo $service['duration']; ?>" min="1" required>
                            <?php if (isset($errors['duration'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['duration']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Image Filename (Optional)</label>
                        <input type="text" class="form-control" id="image" name="image" value="<?php echo htmlspecialchars($service['image']); ?>">
                        <div class="form-text">Enter the filename of the image (e.g., "basic-wash.jpg"). Upload the image to the assets/img directory.</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" <?php echo $service['active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Active</label>
                        <div class="form-text">Inactive services won't be visible to customers.</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="<?php echo isset($_GET['edit']) ? 'update_service' : 'add_service'; ?>" class="btn btn-primary">
                        <?php echo isset($_GET['edit']) ? 'Update Service' : 'Add Service'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show the modal when in edit mode
    var serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
    serviceModal.show();
    
    // Handle modal close - redirect to services page without edit parameter
    document.getElementById('serviceModal').addEventListener('hidden.bs.modal', function () {
        window.location.href = 'services.php';
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>