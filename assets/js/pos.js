/**
 * POS Logic
 */

const POS = {
    products: [],
    cart: [],
    taxRate: 0.00, // Can be fetched from settings API
    discount: 0,
    
    // DOM Elements
    gridEl: document.getElementById('productsGrid'),
    searchEl: document.getElementById('posSearch'),
    cartEl: document.getElementById('cartItems'),
    emptyMsgEl: document.getElementById('emptyCartMsg'),
    
    // Displays
    subtotalDisp: document.getElementById('subtotalDisplay'),
    taxDisp: document.getElementById('taxDisplay'),
    totalDisp: document.getElementById('totalDisplay'),
    checkoutBtn: document.getElementById('checkoutBtn'),

    init() {
        this.fetchProducts();
        this.bindEvents();
    },

    bindEvents() {
        // Search
        this.searchEl.addEventListener('input', (e) => this.filterProducts(e.target.value));
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                this.searchEl.focus();
            } else if (e.key === 'F9' && this.cart.length > 0) {
                e.preventDefault();
                this.openCheckout();
            }
        });

        // Checkout Modal Events
        this.checkoutBtn.addEventListener('click', () => this.openCheckout());
        document.getElementById('closeModalBtn').addEventListener('click', () => this.closeCheckout());
        document.getElementById('cancelPaymentBtn').addEventListener('click', () => this.closeCheckout());
        document.getElementById('clearCartBtn').addEventListener('click', () => {
            if(confirm("Clear cart?")) {
                this.cart = [];
                this.renderCart();
            }
        });

        // Payment Method Selection
        const methods = document.querySelectorAll('.payment-method-card');
        methods.forEach(card => {
            card.addEventListener('click', () => {
                methods.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                
                const method = card.dataset.method;
                const refGroup = document.getElementById('referenceGroup');
                if(method === 'Cash') {
                    refGroup.style.display = 'none';
                } else {
                    refGroup.style.display = 'block';
                }
            });
        });

        // Change Calculation
        document.getElementById('amountReceived').addEventListener('input', (e) => {
            const received = parseFloat(e.target.value) || 0;
            const total = this.calculateTotal().total;
            const change = received - total;
            const changeEl = document.getElementById('changeAmount');
            
            if (change >= 0) {
                changeEl.textContent = App.formatCurrency(change);
                changeEl.style.color = 'var(--success-color)';
                document.getElementById('confirmPaymentBtn').disabled = false;
            } else {
                changeEl.textContent = 'Insufficient amount';
                changeEl.style.color = 'var(--danger-color)';
                document.getElementById('confirmPaymentBtn').disabled = true;
            }
        });

        // Confirm Payment
        document.getElementById('confirmPaymentBtn').addEventListener('click', () => this.processPayment());
    },

    async fetchProducts() {
        try {
            const res = await fetch('../api/products.php');
            const data = await res.json();
            if (data.success) {
                this.products = data.products;
                this.renderProducts(this.products);
            }
        } catch (error) {
            App.showToast('Error', 'Failed to load products', 'error');
            this.gridEl.innerHTML = '<p class="text-danger">Failed to load products.</p>';
        }
    },

    filterProducts(query) {
        query = query.toLowerCase();
        const filtered = this.products.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.code.toLowerCase().includes(query)
        );
        this.renderProducts(filtered);
    },

    renderProducts(items) {
        this.gridEl.innerHTML = '';
        if (items.length === 0) {
            this.gridEl.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">No products found.</p>';
            return;
        }

        items.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            
            let stockClass = product.stock_quantity > 10 ? '' : 'low';
            let stockText = product.stock_quantity;
            
            card.innerHTML = `
                <div class="stock-badge ${stockClass}">${stockText}</div>
                <div class="product-code">${product.code}</div>
                <div class="product-name">${product.name}</div>
                <div class="product-price">${App.formatCurrency(product.selling_price)}</div>
            `;

            card.addEventListener('click', () => this.addToCart(product));
            this.gridEl.appendChild(card);
        });
    },

    addToCart(product) {
        if (product.stock_quantity <= 0) {
            App.showToast('Warning', 'Product is out of stock!', 'warning');
            return;
        }

        const existing = this.cart.find(item => item.id === product.id);
        if (existing) {
            if (existing.qty >= product.stock_quantity) {
                App.showToast('Warning', 'Cannot add more than available stock', 'warning');
                return;
            }
            existing.qty++;
        } else {
            this.cart.push({
                ...product,
                qty: 1
            });
        }
        
        App.showToast('Added', `${product.name} added to cart.`, 'success');
        this.renderCart();
    },

    updateQty(index, newQty) {
        const item = this.cart[index];
        newQty = parseInt(newQty);
        
        if (isNaN(newQty) || newQty <= 0) {
            this.removeFromCart(index);
            return;
        }

        if (newQty > item.stock_quantity) {
            App.showToast('Warning', `Only ${item.stock_quantity} available in stock`, 'warning');
            newQty = item.stock_quantity;
        }

        item.qty = newQty;
        this.renderCart();
    },

    removeFromCart(index) {
        this.cart.splice(index, 1);
        this.renderCart();
    },

    calculateTotal() {
        let subtotal = 0;
        this.cart.forEach(item => {
            subtotal += (item.selling_price * item.qty);
        });
        
        let tax = subtotal * (this.taxRate / 100);
        let total = subtotal + tax - this.discount;

        return { subtotal, tax, total };
    },

    renderCart() {
        const cartItemsEl = document.querySelectorAll('.cart-item');
        cartItemsEl.forEach(el => el.remove()); // remove existing
        
        if (this.cart.length === 0) {
            this.emptyMsgEl.style.display = 'block';
            this.checkoutBtn.disabled = true;
        } else {
            this.emptyMsgEl.style.display = 'none';
            this.checkoutBtn.disabled = false;
            
            this.cart.forEach((item, index) => {
                const itemEl = document.createElement('div');
                itemEl.className = 'cart-item';
                
                const itemTotal = item.selling_price * item.qty;
                
                itemEl.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-title">${item.name}</div>
                        <div class="cart-item-price">${App.formatCurrency(item.selling_price)}</div>
                        <div class="cart-item-controls">
                            <button class="qty-btn" onclick="POS.updateQty(${index}, ${item.qty - 1})">-</button>
                            <input type="number" class="qty-input" value="${item.qty}" onchange="POS.updateQty(${index}, this.value)">
                            <button class="qty-btn" onclick="POS.updateQty(${index}, ${item.qty + 1})">+</button>
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between;">
                        <div class="cart-item-total">${App.formatCurrency(itemTotal)}</div>
                        <button class="cart-item-remove" onclick="POS.removeFromCart(${index})">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                `;
                this.cartEl.appendChild(itemEl);
            });
        }

        // Update Totals
        const totals = this.calculateTotal();
        this.subtotalDisp.textContent = App.formatCurrency(totals.subtotal);
        this.taxDisp.textContent = App.formatCurrency(totals.tax);
        this.totalDisp.textContent = App.formatCurrency(totals.total);
    },

    openCheckout() {
        if (this.cart.length === 0) return;
        const total = this.calculateTotal().total;
        document.getElementById('modalTotalAmount').textContent = App.formatCurrency(total);
        
        // Auto fill exact amount for cash
        const amountInput = document.getElementById('amountReceived');
        amountInput.value = total.toFixed(2);
        amountInput.dispatchEvent(new Event('input')); // trigger change calculation
        
        document.getElementById('checkoutModal').classList.add('active');
        setTimeout(() => amountInput.focus(), 100);
    },

    closeCheckout() {
        document.getElementById('checkoutModal').classList.remove('active');
        // reset forms
        document.getElementById('paymentReference').value = '';
    },

    async processPayment() {
        const btn = document.getElementById('confirmPaymentBtn');
        const btnText = btn.querySelector('.btn-text');
        const loader = btn.querySelector('.loader');
        
        btn.disabled = true;
        btnText.style.display = 'none';
        loader.style.display = 'block';

        const totals = this.calculateTotal();
        const paymentMethod = document.querySelector('.payment-method-card.selected').dataset.method;
        const amountReceived = parseFloat(document.getElementById('amountReceived').value);
        const change = amountReceived - totals.total;
        const reference = document.getElementById('paymentReference').value;

        const payload = {
            cart: this.cart.map(item => ({
                id: item.id,
                qty: item.qty,
                price: item.selling_price
            })),
            subtotal: totals.subtotal,
            tax: totals.tax,
            discount: this.discount,
            total: totals.total,
            amount_paid: amountReceived,
            change_returned: change,
            payment_method: paymentMethod,
            payment_reference: reference,
            csrf_token: document.querySelector('meta[name="csrf-token"]').content
        };

        try {
            const res = await fetch('../api/sales.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            
            if (data.success) {
                App.showToast('Success', 'Sale completed successfully!');
                this.cart = [];
                this.renderCart();
                this.closeCheckout();
                this.fetchProducts(); // Refresh stock
                
                // Show printable receipt (simple alert for now)
                if(confirm("Print Receipt?")) {
                    window.open(`../api/receipt.php?id=${data.sale_id}`, '_blank');
                }
            } else {
                throw new Error(data.message || 'Payment failed');
            }
        } catch (error) {
            App.showToast('Error', error.message, 'error');
        } finally {
            btn.disabled = false;
            btnText.style.display = 'block';
            loader.style.display = 'none';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => POS.init());
