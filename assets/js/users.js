document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('usersTableBody');
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const addBtn = document.getElementById('addUserBtn');
    const closeBtns = [document.getElementById('closeModalBtn'), document.getElementById('cancelBtn')];
    const passwordHint = document.getElementById('passwordHint');
    const userPasswordInput = document.getElementById('userPassword');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let users = [];

    // Fetch and render users
    const loadUsers = async () => {
        try {
            const response = await fetch('../api/users.php');
            const data = await response.json();

            if (data.success) {
                users = data.data;
                renderUsers();
            } else {
                App.showToast('Error', data.message || 'Failed to load users', 'error');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            App.showToast('Error', 'Network error occurred', 'error');
        }
    };

    const renderUsers = () => {
        if (users.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">No users found.</td></tr>';
            return;
        }

        tableBody.innerHTML = users.map(user => {
            const roleBadgeClass = `badge-${user.role}`;
            const statusBadgeClass = `badge-${user.status}`;
            
            return `
                <tr>
                    <td><strong>${escapeHtml(user.name)}</strong></td>
                    <td>${escapeHtml(user.username)}</td>
                    <td><span class="badge ${roleBadgeClass}">${escapeHtml(user.role)}</span></td>
                    <td><span class="badge ${statusBadgeClass}">${escapeHtml(user.status)}</span></td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn edit-btn" data-id="${user.id}" title="Edit User">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button class="action-btn toggle-status-btn ${user.status === 'active' ? 'delete' : ''}" data-id="${user.id}" data-status="${user.status === 'active' ? 'inactive' : 'active'}" title="${user.status === 'active' ? 'Deactivate' : 'Activate'} User">
                                <i class="ph ph-${user.status === 'active' ? 'prohibit' : 'check-circle'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    // Modal Handling
    const openModal = (user = null) => {
        form.reset();
        document.getElementById('userId').value = user ? user.id : '';
        
        if (user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userName').value = user.name;
            document.getElementById('userUsername').value = user.username;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            passwordHint.style.display = 'inline';
            userPasswordInput.required = false;
        } else {
            document.getElementById('modalTitle').textContent = 'Add New User';
            passwordHint.style.display = 'none';
            userPasswordInput.required = true;
        }
        
        modal.classList.add('active');
    };

    const closeModal = () => {
        modal.classList.remove('active');
    };

    // Event Listeners
    addBtn.addEventListener('click', () => openModal());
    closeBtns.forEach(btn => btn?.addEventListener('click', closeModal));

    // Handle Edit and Toggle clicks
    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        const toggleBtn = e.target.closest('.toggle-status-btn');

        if (editBtn) {
            const id = editBtn.dataset.id;
            const user = users.find(u => u.id == id);
            if (user) openModal(user);
        }

        if (toggleBtn) {
            const id = toggleBtn.dataset.id;
            const newStatus = toggleBtn.dataset.status;
            
            if (confirm(`Are you sure you want to ${newStatus === 'inactive' ? 'deactivate' : 'activate'} this user?`)) {
                try {
                    const response = await fetch(`../api/users.php?action=toggle_status`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, status: newStatus, csrf_token: csrfToken })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        App.showToast('Success', data.message);
                        loadUsers();
                    } else {
                        App.showToast('Error', data.message, 'error');
                    }
                } catch (error) {
                    App.showToast('Error', 'Failed to toggle status', 'error');
                }
            }
        }
    });

    // Form Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('userId').value;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.csrf_token = csrfToken;
        
        const isEdit = !!id;
        const url = '../api/users.php';
        const method = isEdit ? 'PUT' : 'POST';
        
        const btn = document.getElementById('saveBtn');
        const originalText = btn.textContent;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
        btn.disabled = true;

        try {
            const fetchOptions = {
                method,
                headers: isEdit ? { 'Content-Type': 'application/json' } : {}
            };

            if (isEdit) {
                fetchOptions.body = JSON.stringify(data);
            } else {
                fetchOptions.body = formData;
                formData.append('csrf_token', csrfToken); // Ensure it's in FormData for POST
            }

            const response = await fetch(url, fetchOptions);
            const result = await response.json();

            if (result.success) {
                App.showToast('Success', result.message);
                closeModal();
                loadUsers();
            } else {
                App.showToast('Error', result.message, 'error');
            }
        } catch (error) {
            console.error(error);
            App.showToast('Error', 'An error occurred while saving', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    // Utility
    const escapeHtml = (unsafe) => {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    // Initial load
    loadUsers();
});
