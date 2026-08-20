<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();
require_role('admin'); // Only admin can view settings

$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

$users_stmt = $pdo->query("SELECT id, name, username, role, status, created_at FROM users ORDER BY id ASC");
$users = $users_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .page-header { margin-bottom: 2rem; }
        .settings-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .settings-nav { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; }
        .settings-nav-item { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; gap: 0.75rem; color: var(--text-muted); transition: all var(--transition-fast); }
        .settings-nav-item:last-child { border-bottom: none; }
        .settings-nav-item:hover, .settings-nav-item.active { background: var(--primary-light); color: var(--primary-color); }
        .settings-content-section { display: none; }
        .settings-content-section.active { display: block; animation: fadeIn 0.3s ease-out; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); background: var(--bg-color); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include '../components/header.php'; ?>
            <main class="main-content">
                <div class="page-header">
                    <h1>System Settings</h1>
                    <p class="text-muted">Manage your store configuration and staff</p>
                </div>

                <div class="settings-grid">
                    <div>
                        <div class="settings-nav">
                            <div class="settings-nav-item active" onclick="switchTab('store')">
                                <i class="ph ph-storefront"></i> Store Information
                            </div>
                            <div class="settings-nav-item" onclick="switchTab('users')">
                                <i class="ph ph-users"></i> User Management
                            </div>
                        </div>
                    </div>

                    <div>
                        <!-- Store Info Tab -->
                        <div id="store-tab" class="settings-content-section active card" style="padding: 2rem;">
                            <h2 style="margin-bottom: 1.5rem;">Store Information</h2>
                            <form id="settingsForm">
                                <div style="margin-bottom: 1rem;">
                                    <label class="form-label">Store Name</label>
                                    <input type="text" id="storeName" class="form-control" value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>" required>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="storePhone" class="form-control" value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>" required>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <label class="form-label">Address</label>
                                    <textarea id="storeAddress" class="form-control" rows="3"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>

                        <!-- Users Tab -->
                        <div id="users-tab" class="settings-content-section card" style="padding: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <h2 style="margin: 0;">User Management</h2>
                            </div>
                            <div style="overflow-x: auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($users as $u): ?>
                                        <tr>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></td>
                                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td>
                                                <span style="background: var(--bg-color); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; text-transform: capitalize;">
                                                    <?php echo htmlspecialchars($u['role']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted" style="margin-top: 1rem; font-size: 0.85rem;">* User addition is currently handled via database import. See documentation for SQL commands to add users.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.settings-nav-item').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.settings-content-section').forEach(el => el.classList.remove('active'));
            
            event.currentTarget.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
        }

        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const payload = {
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
                store_name: document.getElementById('storeName').value,
                phone: document.getElementById('storePhone').value,
                address: document.getElementById('storeAddress').value
            };

            try {
                const res = await fetch('../api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    App.showToast('Success', data.message);
                } else {
                    App.showToast('Error', data.message, 'error');
                }
            } catch (err) {
                App.showToast('Error', 'Connection failed', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
        });
    </script>
</body>
</html>
