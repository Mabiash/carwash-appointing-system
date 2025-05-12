<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if not admin
if (!is_admin()) {
    redirect('/index.php', 'You do not have permission to access this page', 'danger');
}

// Handle user updates or deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user']) && isset($_POST['user_id'])) {
        $user_id = (int) $_POST['user_id'];
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $role = sanitize($_POST['role'] ?? 'customer');

        // Validate inputs
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Email already registered to another user';
            }
        }

        // Validate role
        if (!in_array($role, ['customer', 'admin'])) {
            $errors[] = 'Invalid role';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");

            if ($stmt->execute([$name, $email, $phone, $role, $user_id])) {
                redirect('users.php', 'User updated successfully', 'success');
            } else {
                redirect('users.php', 'Failed to update user', 'danger');
            }
        } else {
            redirect('users.php', 'Validation errors: ' . implode(', ', $errors), 'danger');
        }
    } elseif (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $user_id = (int) $_POST['user_id'];

        // Make sure the admin isn't deleting their own account
        if ($user_id == $_SESSION['user_id']) {
            redirect('users.php', 'You cannot delete your own account', 'danger');
            exit;
        }

        // Check if user has any appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $appointment_count = $stmt->fetchColumn();

        if ($appointment_count > 0) {
            redirect('users.php', 'Cannot delete user with appointments. Cancel their appointments first.', 'warning');
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                redirect('users.php', 'User deleted successfully', 'success');
            } else {
                redirect('users.php', 'Failed to delete user', 'danger');
            }
        }
    } elseif (isset($_POST['reset_password']) && isset($_POST['user_id'])) {
        $user_id = (int) $_POST['user_id'];
        $new_password = substr(md5(rand()), 0, 8); // Generate a random 8-character password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            redirect('users.php', "Password reset successfully. New password: $new_password", 'success');
        } else {
            redirect('users.php', 'Failed to reset password', 'danger');
        }
    }
}

// Get search filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? 'all';

// Build the query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($role !== 'all') {
    $query .= " AND role = ?";
    $params[] = $role;
}

$query .= " ORDER BY name";

// Get users
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">Manage Users</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email or phone">
                    </div>
                    <div class="col-md-4">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customers
                            </option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">Users</h2>
        <span class="badge bg-primary"><?php echo count($users); ?> users</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                </div>
                <p class="text-muted">No users found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger px-3 py-2">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info px-3 py-2">Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                    data-bs-target="#resetModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-key me-1"></i>Reset Password
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash me-1"></i>Delete User
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="name<?php echo $user['id']; ?>"
                                                                class="form-label">Name</label>
                                                            <input type="text" class="form-control"
                                                                id="name<?php echo $user['id']; ?>" name="name"
                                                                value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="email<?php echo $user['id']; ?>"
                                                                class="form-label">Email</label>
                                                            <input type="email" class="form-control"
                                                                id="email<?php echo $user['id']; ?>" name="email"
                                                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                                                required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="phone<?php echo $user['id']; ?>"
                                                                class="form-label">Phone</label>
                                                            <input type="text" class="form-control"
                                                                id="phone<?php echo $user['id']; ?>" name="phone"
                                                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="role<?php echo $user['id']; ?>"
                                                                class="form-label">Role</label>
                                                            <select class="form-select" id="role<?php echo $user['id']; ?>"
                                                                name="role">
                                                                <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_user" class="btn btn-primary">Save
                                                            Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the user
                                                        "<?php echo htmlspecialchars($user['name']); ?>"?</p>
                                                    <p class="text-danger mb-0">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user"
                                                            class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetModal<?php echo $user['id']; ?>" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reset Password</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to reset the password for
                                                        "<?php echo htmlspecialchars($user['name']); ?>"?</p>
                                                    <p>A new random password will be generated and displayed.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="reset_password"
                                                            class="btn btn-warning">Reset Password</button>
                                                    </form>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>