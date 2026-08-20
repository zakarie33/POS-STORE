<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();

// Include a dummy product just so the POS isn't empty initially if DB is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO categories (name) VALUES ('General')");
    $cat_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO products (code, name, category_id, cost_price, selling_price, stock_quantity) VALUES 
        ('PRD001', 'Bottled Water 500ml', $cat_id, 0.50, 1.00, 100),
        ('PRD002', 'Coca Cola 1L', $cat_id, 1.00, 2.50, 50),
        ('PRD003', 'Lays Potato Chips', $cat_id, 0.80, 1.50, 30),
        ('PRD004', 'Chocolate Bar', $cat_id, 1.20, 2.00, 25),
        ('PRD005', 'Fresh Apple (1kg)', $cat_id, 2.00, 3.50, 15)
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pos.css">
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar.php'; ?>
        
        <div class="main-wrapper">
            <?php include '../components/header.php'; ?>
        
        <div class="pos-container">
            <!-- Left: Products List -->
            <div class="pos-products">
                <div class="pos-search-bar">
                    <div class="input-group">
                        <input type="text" id="posSearch" class="form-control" placeholder="Search by Product Code or Name (Shortcut: F2)..." autocomplete="off" autofocus>
                        <i class="ph ph-magnifying-glass"></i>
                    </div>
                </div>

                <div class="products-grid" id="productsGrid">
                    <!-- Products will be loaded here via JS -->
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-muted);">
                        <i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i>
                        <p>Loading products...</p>
                    </div>
                </div>
            </div>

            <!-- Right: Cart -->
            <div class="pos-cart">
                <div class="cart-header">
                    <h2><i class="ph ph-shopping-cart"></i> Current Sale</h2>
                    <button class="btn btn-outline" id="clearCartBtn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                        <i class="ph ph-trash"></i> Clear
                    </button>
                </div>

                <div class="cart-items" id="cartItems">
                    <!-- Cart Items go here -->
                    <div id="emptyCartMsg" style="text-align: center; color: var(--text-muted); margin-top: 2rem;">
                        <i class="ph ph-shopping-cart" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p style="margin-top: 1rem;">Cart is empty</p>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotalDisplay">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (0%)</span>
                        <span id="taxDisplay">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount</span>
                        <span id="discountDisplay">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="totalDisplay">$0.00</span>
                    </div>

                    <button class="btn btn-primary checkout-btn" id="checkoutBtn" disabled>
                        <i class="ph ph-credit-card"></i> Pay Now
                    </button>
                </div>
            </div>
        </div>
        </div> <!-- Close main-wrapper -->
    </div>

    <!-- Checkout Modal -->
    <div class="modal-overlay" id="checkoutModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Complete Payment</h3>
                <button class="close-modal" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <span class="text-muted">Total Amount Due</span>
                    <h2 style="font-size: 2.5rem; color: var(--primary-color);" id="modalTotalAmount">$0.00</h2>
                </div>

                <div class="payment-methods" id="paymentMethods">
                    <div class="payment-method-card selected" data-method="Cash">
                        <i class="ph ph-money text-success"></i>
                        <span>Cash</span>
                    </div>
                    <div class="payment-method-card" data-method="Zaad">
                        <i class="ph ph-device-mobile text-info"></i>
                        <span>Zaad</span>
                    </div>
                    <div class="payment-method-card" data-method="eDahab">
                        <i class="ph ph-device-mobile text-warning"></i>
                        <span>eDahab</span>
                    </div>
                    <div class="payment-method-card" data-method="Sahal">
                        <i class="ph ph-device-mobile text-accent"></i>
                        <span>Sahal</span>
                    </div>
                </div>

                <div class="amount-input-group">
                    <label class="form-label">Amount Received</label>
                    <input type="number" id="amountReceived" class="form-control" step="0.01" min="0" placeholder="0.00">
                </div>
                
                <div class="form-group" id="referenceGroup" style="display: none;">
                    <label class="form-label">Phone / Reference Number</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="Enter reference...">
                </div>

                <div class="change-display">
                    <span class="text-muted" style="font-size: 0.9rem; font-weight: normal; margin-right: 0.5rem;">Change:</span>
                    <span id="changeAmount">$0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelPaymentBtn">Cancel</button>
                <button class="btn btn-primary" id="confirmPaymentBtn" style="min-width: 120px;">
                    <span class="btn-text">Confirm Sale</span>
                    <div class="loader" style="display: none;"></div>
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/pos.js"></script>
</body>
</html>
