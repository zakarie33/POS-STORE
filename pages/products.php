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
    <title>Products - SuperPOS</title>
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
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-color);
        }
        
        tbody tr:hover {
            background: var(--bg-color);
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--text-muted);
            transition: color var(--transition-fast);
        }

        .action-btn.edit:hover { color: var(--info-color); }
        .action-btn.delete:hover { color: var(--danger-color); }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--card-bg);
            width: 100%;
            max-width: 500px;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-xl);
            transform: translateY(-20px);
            transition: transform 0.3s;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-grid .full-width {
            grid-column: 1 / -1;
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
                        <h1>Products</h1>
                        <p class="text-muted">Manage your inventory</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('add')">
                        <i class="ph ph-plus"></i> Add Product
                    </button>
                </div>

                <div class="card" style="overflow-x: auto;">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Cost</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Add Product</h2>
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-grid">
                    <div>
                        <label class="form-label">Product Code</label>
                        <input type="text" id="productCode" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select id="productCategory" class="form-control">
                            <option value="">Select Category...</option>
                        </select>
                    </div>
                    <div class="full-width">
                        <label class="form-label">Product Name</label>
                        <input type="text" id="productName" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Cost Price</label>
                        <input type="number" id="costPrice" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Selling Price</label>
                        <input type="number" id="sellingPrice" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" id="stockQty" class="form-control" min="0" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        let currentAction = 'add';
        let categoriesList = [];

        async function fetchProducts() {
            try {
                const res = await fetch('../api/products.php');
                const data = await res.json();
                if (data.success) {
                    categoriesList = data.categories;
                    populateCategories();
                    renderTable(data.products);
                }
            } catch (error) {
                App.showToast('Error', 'Failed to load data', 'error');
            }
        }

        function populateCategories() {
            const select = document.getElementById('productCategory');
            select.innerHTML = '<option value="">Select Category...</option>';
            categoriesList.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        }

        function renderTable(products) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No products found</td></tr>';
                return;
            }

            products.forEach(p => {
                let stockColor = p.stock_quantity <= 10 ? 'var(--danger-color)' : 'inherit';
                tbody.innerHTML += `
                    <tr>
                        <td><span style="background: var(--bg-color); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">${p.code}</span></td>
                        <td style="font-weight: 500;">${p.name}</td>
                        <td>${p.category_name || '-'}</td>
                        <td>$${parseFloat(p.cost_price).toFixed(2)}</td>
                        <td style="color: var(--primary-color); font-weight: 600;">$${parseFloat(p.selling_price).toFixed(2)}</td>
                        <td style="color: ${stockColor}; font-weight: ${p.stock_quantity <= 10 ? 'bold' : 'normal'};">${p.stock_quantity}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&apos;")})'><i class="ph ph-pencil-simple"></i></button>
                                <button class="action-btn delete" onclick="deleteProduct(${p.id})"><i class="ph ph-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        function openModal(action, product = null) {
            currentAction = action;
            const modal = document.getElementById('productModal');
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Product' : 'Edit Product';
            
            if (action === 'add') {
                document.getElementById('productForm').reset();
                document.getElementById('productId').value = '';
            } else if (product) {
                document.getElementById('productId').value = product.id;
                document.getElementById('productCode').value = product.code;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCategory').value = product.category_id || '';
                document.getElementById('costPrice').value = product.cost_price;
                document.getElementById('sellingPrice').value = product.selling_price;
                document.getElementById('stockQty').value = product.stock_quantity;
            }
            
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const payload = {
                action: currentAction,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
                id: document.getElementById('productId').value,
                code: document.getElementById('productCode').value,
                name: document.getElementById('productName').value,
                category_id: document.getElementById('productCategory').value,
                cost_price: document.getElementById('costPrice').value,
                selling_price: document.getElementById('sellingPrice').value,
                stock_quantity: document.getElementById('stockQty').value
            };

            try {
                const res = await fetch('../api/products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    App.showToast('Success', data.message, 'success');
                    closeModal();
                    fetchProducts();
                } else {
                    App.showToast('Error', data.message, 'error');
                }
            } catch (error) {
                App.showToast('Error', 'An error occurred', 'error');
            }
        });

        function editProduct(product) {
            openModal('update', product);
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            
            const payload = {
                action: 'delete',
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            };

            try {
                const res = await fetch('../api/products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success) {
                    App.showToast('Success', data.message, 'success');
                    fetchProducts();
                } else {
                    App.showToast('Error', data.message, 'error');
                }
            } catch (error) {
                App.showToast('Error', 'An error occurred', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', fetchProducts);
    </script>
</body>
</html>
