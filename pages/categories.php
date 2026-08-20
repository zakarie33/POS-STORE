<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); background: var(--bg-color); }
        .action-btns { display: flex; gap: 0.5rem; }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-muted); transition: color var(--transition-fast); }
        .action-btn.edit:hover { color: var(--info-color); }
        .action-btn.delete:hover { color: var(--danger-color); }
        /* Simple Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; opacity: 0; transition: opacity 0.3s; }
        .modal.show { display: flex; opacity: 1; }
        .modal-content { background: var(--card-bg); width: 100%; max-width: 400px; border-radius: var(--radius-lg); padding: 2rem; transform: translateY(-20px); transition: transform 0.3s; }
        .modal.show .modal-content { transform: translateY(0); }
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
                        <h1>Categories</h1>
                        <p class="text-muted">Organize your products</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('add')">
                        <i class="ph ph-plus"></i> Add Category
                    </button>
                </div>
                <div class="card" style="overflow-x: auto;">
                    <table id="dataTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="dataModal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Add Category</h2>
            <form id="dataForm">
                <input type="hidden" id="recordId">
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Name</label>
                    <input type="text" id="recordName" class="form-control" required>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Description</label>
                    <textarea id="recordDesc" class="form-control" rows="3"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        let currentAction = 'add';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        async function fetchData() {
            const res = await fetch('../api/categories.php');
            const data = await res.json();
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            if (data.categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No categories</td></tr>';
                return;
            }
            data.categories.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td style="font-weight: 500;">${item.name}</td>
                        <td>${item.description || '-'}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit" onclick='editRecord(${JSON.stringify(item).replace(/'/g, "&apos;")})'><i class="ph ph-pencil-simple"></i></button>
                                <button class="action-btn delete" onclick="deleteRecord(${item.id})"><i class="ph ph-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
            });
        }

        function openModal(action, item = null) {
            currentAction = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Category' : 'Edit Category';
            if (action === 'add') {
                document.getElementById('dataForm').reset();
                document.getElementById('recordId').value = '';
            } else {
                document.getElementById('recordId').value = item.id;
                document.getElementById('recordName').value = item.name;
                document.getElementById('recordDesc').value = item.description;
            }
            document.getElementById('dataModal').classList.add('show');
        }

        function closeModal() { document.getElementById('dataModal').classList.remove('show'); }

        document.getElementById('dataForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                action: currentAction, csrf_token: csrfToken,
                id: document.getElementById('recordId').value,
                name: document.getElementById('recordName').value,
                description: document.getElementById('recordDesc').value
            };
            const res = await fetch('../api/categories.php', { method: 'POST', body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) { App.showToast('Success', data.message); closeModal(); fetchData(); } 
            else { App.showToast('Error', data.message, 'error'); }
        });

        function editRecord(item) { openModal('update', item); }

        async function deleteRecord(id) {
            if (!confirm('Delete this category?')) return;
            const res = await fetch('../api/categories.php', { method: 'POST', body: JSON.stringify({action: 'delete', id: id, csrf_token: csrfToken}) });
            const data = await res.json();
            if (data.success) { App.showToast('Success', data.message); fetchData(); } 
            else { App.showToast('Error', data.message, 'error'); }
        }

        document.addEventListener('DOMContentLoaded', fetchData);
    </script>
</body>
</html>
