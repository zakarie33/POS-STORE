<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }

        .badge-active { background: var(--success-light); color: var(--success-color); }
        .badge-inactive { background: var(--danger-light); color: var(--danger-color); }

        .badge-admin { background: var(--primary-light); color: var(--primary-color); }
        .badge-manager { background: var(--warning-light); color: var(--warning-color); }
        .badge-cashier { background: var(--info-light); color: var(--info-color); }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.25rem;
            transition: color var(--transition-fast);
        }
        
        .action-btn:hover { color: var(--primary-color); }
        .action-btn.delete:hover { color: var(--danger-color); }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0; visibility: hidden;
            transition: all var(--transition-normal);
        }

        .modal-overlay.active { opacity: 1; visibility: visible; }

        .modal-content {
            background: var(--card-bg);
            width: 100%; max-width: 500px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            transform: translateY(20px);
            transition: all var(--transition-normal);
        }

        .modal-overlay.active .modal-content { transform: translateY(0); }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
        }

        .close-modal {
            background: none; border: none;
            font-size: 1.5rem; color: var(--text-muted); cursor: pointer;
        }

        .modal-body { padding: 1.5rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        
        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex; justify-content: flex-end; gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar.php'; ?>
        
        <div class="main-wrapper">
            <?php include '../components/header.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <div>
                        <h1 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Manage Users</h1>
                        <p class="text-muted">Add, edit, or deactivate system users.</p>
                    </div>
                    <button class="btn btn-primary" id="addUserBtn">
                        <i class="ph ph-plus"></i> Add New User
                    </button>
                </div>

                <div class="card">
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Loaded via JS -->
                                <tr><td colspan="6" style="text-align: center; padding: 2rem;"><i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New User</h3>
                <button class="close-modal" id="closeModalBtn">&times;</button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="userName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" id="userUsername" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password <small class="text-muted" id="passwordHint">(Leave blank to keep existing password)</small></label>
                        <input type="password" id="userPassword" name="password" class="form-control" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select id="userRole" name="role" class="form-control" required>
                            <option value="cashier">Cashier</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="userStatus" name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/users.js"></script>
</body>
</html>
