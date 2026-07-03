@extends('admin.blank_master')


@section('admin')
    <div class="pos-container">

        {{-- ── MOBILE TAB BAR (hidden on desktop) ── --}}
        <div class="pos-mobile-tabbar">
            <a href="{{ route('sales.invoice.view') }}" class="pos-mobile-back-btn">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="pos-mobile-tabs">
                <button class="pos-mobile-tab pos-tab-active" id="tabProducts" onclick="switchPosTab('products')">
                    <i class="bi bi-grid-3x3-gap"></i> Products
                </button>
                <button class="pos-mobile-tab" id="tabCart" onclick="switchPosTab('cart')">
                    <i class="bi bi-cart3"></i> Cart
                    <span class="pos-cart-badge" id="cartBadge" style="display:none">0</span>
                </button>
            </div>
        </div>

        <!-- Left Section - Products -->
        <div class="products-section" id="posPanelProducts">
            {{-- Desktop back button (hidden on mobile — mobile tab bar handles it) --}}
            <div class="search-bar">
                <a href="{{ route('sales.invoice.view') }}" class="pos-back-btn pos-back-desktop" title="Back to Sales">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div class="search-input-group">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput"
                           placeholder="Search by name, SKU or scan barcode...">
                </div>
            </div>

            <div class="categories-bar">
                <button class="category-btn active" onclick="filterCategory('all', this)">
                    <i class="bi bi-grid"></i> All
                </button>
                @foreach($categories as $cat)
                <button class="category-btn" onclick="filterCategory('{{ $cat->id }}', this)">
                    <i class="bi bi-tag"></i> {{ $cat->name }}
                </button>
                @endforeach
            </div>

            <div class="products-grid" id="productsGrid">
                <!-- Products Loaded by JS -->
            </div>
        </div>

        <!-- Right Section - Cart -->
        <div class="cart-section" id="posPanelCart">

            {{-- Header --}}
            <div class="cart-header">
                <div class="cart-title">
                    <i class="bi bi-cart3"></i> Current Order
                </div>
                <div class="cart-item-count" id="cartCount" style="display:none">
                    <span id="cartCountNum">0</span> items
                </div>
            </div>

            {{-- Order meta --}}
            <div class="order-meta-strip">
                <div class="order-meta-row">
                    <span class="order-meta-label"><i class="bi bi-person"></i> Customer</span>
                    <select class="order-meta-select" id="customer_id">
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-meta-row">
                    <span class="order-meta-label"><i class="bi bi-building"></i> Branch</span>
                    <select class="order-meta-select" id="branch_id">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ Auth::user()->getAssignedBranchId() == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-meta-row">
                    <span class="order-meta-label"><i class="bi bi-cash"></i> Paid via</span>
                    <select class="order-meta-select" id="account_id">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ $account->id == $defaultPaymentAccountId ? 'selected' : '' }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Divider --}}
            <div class="cart-divider"></div>

            {{-- Cart Items --}}
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <div class="empty-cart-icon"><i class="bi bi-cart-x"></i></div>
                    <p class="empty-cart-title">Cart is empty</p>
                    <p class="empty-cart-hint">Tap a product to add it</p>
                </div>
            </div>

            {{-- Summary --}}
            <div class="cart-summary">
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value" id="subtotal">0.00 {{ $company->currency ?? '$' }}</span>
                </div>
                <div class="summary-row summary-items-count">
                    <span class="summary-label">Items</span>
                    <span class="summary-value" id="summaryItemCount">0</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row total-row">
                    <span class="total-label">TOTAL</span>
                    <span class="total-value" id="totalAmount">0.00 {{ $company->currency ?? '$' }}</span>
                </div>

                <button class="btn-complete-payment" onclick="processPayment()">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Complete Payment</span>
                </button>
                <button class="btn-clear-order" onclick="clearCart()">
                    <i class="bi bi-trash3"></i> Clear Order
                </button>
            </div>
        </div>

        {{-- ── MOBILE PAYMENT FOOTER (hidden on desktop) ── --}}
        <div class="pos-mobile-footer">
            <div class="pos-mobile-footer-total">
                <span class="pos-mobile-footer-label">Total</span>
                <span class="pos-mobile-footer-val" id="mobileTotalAmt">0.00 {{ $company->currency ?? 'SAR' }}</span>
            </div>
            <button class="pos-mobile-pay-btn" onclick="processPayment()">
                <i class="bi bi-check-circle-fill"></i> Pay Now
            </button>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const CURRENCY = '{{ $company->currency ?? "SAR" }}';
        const products = @json($products);
        let cart = [];
        let selectedPaymentMethod = 'Cash';

        document.addEventListener('DOMContentLoaded', () => {
            displayProducts(products);
            document.getElementById('searchInput').addEventListener('input', (e) => {
                filterProducts(e.target.value.toLowerCase());
            });
        });

        function displayProducts(list) {
            const grid = document.getElementById('productsGrid');
            if (list.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center p-5 text-muted">No products available</div>';
                return;
            }
            grid.innerHTML = list.map(p => `
                <div class="product-card" onclick="addToCart(${p.id})">
                    <div class="product-image">
                        ${p.image ? `<img src="/${p.image}" class="pos-product-image-img">` : `<i class="bi bi-box"></i>`}
                    </div>
                    <div class="product-name">${p.product_name}</div>
                    <div class="product-stock ${p.stocks_sum_quantity <= 0 ? 'text-danger fw-bold' : ''}">
                        Stock: ${p.stocks_sum_quantity || 0}
                    </div>
                    <div class="product-purchase-price text-muted" style="font-size: 11px;">
                        Cost: ${parseFloat(p.purchase_price || 0).toFixed(2)} ${CURRENCY}
                    </div>
                    <div class="product-price">${parseFloat(p.selling_price).toFixed(2)} ${CURRENCY}</div>
                </div>
            `).join('');
        }

        function filterCategory(catId, btn) {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filtered = catId === 'all' ? products : products.filter(p => p.category_id == catId);
            displayProducts(filtered);
        }

        function filterProducts(term) {
            const filtered = products.filter(p => 
                p.product_name.toLowerCase().includes(term) || 
                (p.product_code && p.product_code.toLowerCase().includes(term))
            );
            displayProducts(filtered);
        }

        function addToCart(pid) {
            const p = products.find(prod => prod.id === pid);
            const stock = p.stocks_sum_quantity || 0;
            if (stock <= 0) {
                Swal.fire('Out of Stock', 'Cannot add unavailable items', 'error');
                return;
            }
            const existing = cart.find(i => i.id === pid);
            if (existing) {
                if (existing.quantity < stock) {
                    existing.quantity++;
                } else {
                    Swal.fire('Stock Limit', 'No more units available', 'warning');
                }
            } else {
                cart.push({
                    id: p.id,
                    name: p.product_name,
                    price: parseFloat(p.selling_price),
                    quantity: 1,
                    stock: stock,
                    code: p.product_code
                });
                // On mobile: auto-switch to cart tab when first item is added
                if (window.innerWidth < 1024 && cart.length === 1) switchPosTab('cart');
            }
            updateCart();
        }

        function updateQuantity(pid, delta) {
            const item = cart.find(i => i.id === pid);
            if (item) {
                const newQ = item.quantity + delta;
                if (newQ > 0 && newQ <= item.stock) {
                    item.quantity = newQ;
                } else if (newQ > item.stock) {
                    Swal.fire('Limit Reached', 'Insufficient stock', 'warning');
                }
                updateCart();
            }
        }

        function updateItemAmount(pid, newAmount) {
            const item = cart.find(i => i.id === pid);
            if (!item) return;

            const amount = parseFloat(newAmount);
            if (isNaN(amount) || amount < 0) {
                updateCart(); // re-render to snap the field back to its real value
                return;
            }

            // The field edits this line's total, not the per-unit price, so
            // back it out across the current quantity (qty stays controlled
            // by the +/- buttons; only the per-unit price implicitly changes).
            item.price = item.quantity > 0 ? (amount / item.quantity) : amount;
            updateCart();
        }

        function removeFromCart(pid) {
            cart = cart.filter(i => i.id !== pid);
            updateCart();
        }

        function updateCart() {
            const container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <div class="empty-cart-icon"><i class="bi bi-cart-x"></i></div>
                        <p class="empty-cart-title">Cart is empty</p>
                        <p class="empty-cart-hint">Tap a product to add it</p>
                    </div>`;
            } else {
                container.innerHTML = cart.map(i => `
                    <div class="cart-item">
                        <div class="cart-item-top">
                            <span class="cart-item-name">${i.name}</span>
                            <button class="cart-item-remove" onclick="removeFromCart(${i.id})" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="cart-item-bottom">
                            <div class="cart-item-unit-price">${parseFloat(i.price).toFixed(2)} ${CURRENCY} / unit</div>
                            <div class="cart-item-controls">
                                <button class="qty-btn" onclick="updateQuantity(${i.id}, -1)">−</button>
                                <span class="qty-num">${i.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity(${i.id}, 1)">+</button>
                            </div>
                            <input type="number" class="cart-item-total-input" min="0" step="0.01"
                                value="${(i.price * i.quantity).toFixed(2)}"
                                onchange="updateItemAmount(${i.id}, this.value)"
                                onclick="this.select()" title="Edit line total">
                        </div>
                    </div>
                `).join('');
            }

            const sub      = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const totalQty = cart.reduce((s, i) => s + i.quantity, 0);

            document.getElementById('subtotal').innerText      = sub.toFixed(2) + ' ' + CURRENCY;
            document.getElementById('totalAmount').innerText   = sub.toFixed(2) + ' ' + CURRENCY;
            document.getElementById('summaryItemCount').innerText = totalQty;

            // Header count badge
            const countEl = document.getElementById('cartCount');
            const countNum = document.getElementById('cartCountNum');
            if (countEl) { countEl.style.display = cart.length > 0 ? 'flex' : 'none'; }
            if (countNum) countNum.textContent = totalQty;

            // Mobile
            const badge      = document.getElementById('cartBadge');
            const mobileTotal = document.getElementById('mobileTotalAmt');
            if (badge) { badge.textContent = totalQty; badge.style.display = totalQty > 0 ? 'inline-flex' : 'none'; }
            if (mobileTotal) mobileTotal.textContent = sub.toFixed(2) + ' ' + CURRENCY;
        }

        // ── Mobile tab switching ──────────────────────────────────────
        function switchPosTab(tab) {
            const panels  = { products: document.getElementById('posPanelProducts'), cart: document.getElementById('posPanelCart') };
            const tabs    = { products: document.getElementById('tabProducts'),      cart: document.getElementById('tabCart') };
            Object.keys(panels).forEach(k => {
                const isCurrent = k === tab;
                panels[k].classList.toggle('pos-panel-hidden', !isCurrent);
                tabs[k].classList.toggle('pos-tab-active', isCurrent);
            });
            // Auto-switch to cart after adding first item on mobile
            if (tab === 'cart') document.getElementById('posPanelCart').scrollTop = 0;
        }

        function processPayment() {
            if (cart.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Cart is Empty', text: 'Please add at least one product before checkout.', confirmButtonColor: '#004161' });
                return;
            }

            const total    = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const totalQty = cart.reduce((s, i) => s + i.quantity, 0);
            const customerEl   = document.getElementById('customer_id');
            const accountEl    = document.getElementById('account_id');
            const customerName = customerEl.options[customerEl.selectedIndex]?.text || 'Walk-in Customer';
            const accountName  = accountEl.options[accountEl.selectedIndex]?.text  || 'Cash on Hand';

            const itemRows = cart.map(i => `
                <tr>
                    <td style="padding:7px 10px;text-align:left;font-size:12px;color:#334155;border-bottom:1px solid #f1f5f9;">${i.name}</td>
                    <td style="padding:7px 10px;text-align:center;font-size:12px;color:#64748b;border-bottom:1px solid #f1f5f9;">${i.quantity}</td>
                    <td style="padding:7px 10px;text-align:right;font-size:12px;font-weight:700;color:#004161;border-bottom:1px solid #f1f5f9;">${(i.price * i.quantity).toFixed(2)}</td>
                </tr>`).join('');

            Swal.fire({
                title: '',
                html: `
                    <div style="text-align:left;">
                        <div style="background:#004161;border-radius:12px 12px 0 0;margin:-20px -20px 0;padding:18px 24px 16px;display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:2px;">Order Summary</div>
                                <div style="font-size:20px;font-weight:900;color:#fff;">${totalQty} item${totalQty > 1 ? 's' : ''}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:10px;color:rgba(255,255,255,0.45);margin-bottom:2px;">TOTAL DUE</div>
                                <div style="font-size:26px;font-weight:900;color:#99CC33;">${total.toFixed(2)} <span style="font-size:14px;">${CURRENCY}</span></div>
                            </div>
                        </div>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:10px 16px;display:flex;justify-content:space-between;font-size:11px;margin-bottom:12px;">
                            <div><span style="color:#94a3b8;font-weight:700;margin-right:6px;">Customer</span><span style="color:#004161;font-weight:800;">${customerName}</span></div>
                            <div><span style="color:#94a3b8;font-weight:700;margin-right:6px;">Payment</span><span style="color:#004161;font-weight:800;">${accountName}</span></div>
                        </div>
                        <table style="width:100%;border-collapse:collapse;font-size:12px;">
                            <thead><tr style="background:#f1f5f9;">
                                <th style="padding:7px 10px;text-align:left;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Product</th>
                                <th style="padding:7px 10px;text-align:center;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Qty</th>
                                <th style="padding:7px 10px;text-align:right;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Total</th>
                            </tr></thead>
                            <tbody>${itemRows}</tbody>
                        </table>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 10px;background:#004161;border-radius:0 0 10px 10px;">
                            <span style="font-size:13px;font-weight:900;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:1px;">Grand Total</span>
                            <span style="font-size:20px;font-weight:900;color:#99CC33;">${total.toFixed(2)} ${CURRENCY}</span>
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Confirm & Pay',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#99CC33',
                cancelButtonColor: '#94a3b8',
                width: '440px',
                padding: '20px',
                focusConfirm: false,
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => {
                    const data = {
                        _token: CSRF_TOKEN,
                        customer_id: document.getElementById('customer_id').value,
                        branch_id:   document.getElementById('branch_id').value,
                        payment_account_id: document.getElementById('account_id').value,
                        payment_method: 'Cash',
                        invoice_date: new Date().toISOString().split('T')[0],
                        due_date:     new Date().toISOString().split('T')[0],
                        paid_amount: total,
                        tax: 0,
                        items: cart.map(i => ({
                            product_id: i.id, product_name: i.name,
                            product_code: i.code, quantity: i.quantity,
                            unit_price: i.price, unit: 'Piece'
                        }))
                    };
                    return fetch('{{ route("sales.invoice.store") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) Swal.showValidationMessage(res.message || 'Payment failed. Please try again.');
                        return res;
                    })
                    .catch(() => Swal.showValidationMessage('Network error. Please check your connection.'));
                }
            }).then(result => {
                if (result.isConfirmed && result.value?.success) {
                    Swal.close();
                    cart = [];
                    localStorage.removeItem('waafibook-pos-cart');
                    updateCart();
                    window.location.reload();
                }
            });
        }

        function clearCart() {
            if (cart.length > 0) {
                Swal.fire({ title: 'Clear Order?', icon: 'warning', showCancelButton: true }).then(r => {
                    if (r.isConfirmed) { cart = []; updateCart(); saveCartToStorage(); }
                });
            }
        }

        // ── PWA: IndexedDB cart persistence ──────────────────────────────
        const CART_KEY = 'waafibook-pos-cart';
        function saveCartToStorage() {
            try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch(e) {}
        }
        function loadCartFromStorage() {
            try {
                const saved = localStorage.getItem(CART_KEY);
                if (saved) { cart = JSON.parse(saved); updateCart(); }
            } catch(e) {}
        }
        // Patch updateCart to also persist on every change
        const _origUpdateCart = updateCart;
        updateCart = function() { _origUpdateCart(); saveCartToStorage(); };
        // Restore cart on load
        window.addEventListener('DOMContentLoaded', loadCartFromStorage);

        // ── PWA: offline sale queue with Background Sync ──────────────────
        async function queueOfflineSale(data) {
            return new Promise((resolve, reject) => {
                const req = indexedDB.open('waafibook-pos', 1);
                req.onupgradeneeded = e => e.target.result.createObjectStore('offline_sales', { keyPath: 'id', autoIncrement: true });
                req.onsuccess = e => {
                    const tx = e.target.result.transaction('offline_sales', 'readwrite');
                    tx.objectStore('offline_sales').add({ data, queuedAt: Date.now() });
                    tx.oncomplete = resolve;
                    tx.onerror = reject;
                };
                req.onerror = reject;
            });
        }
        // Notify the user when a queued sale is synced
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data?.type === 'SALE_SYNCED') {
                    Swal.fire({ icon: 'success', title: 'Sale Synced!', text: 'Your offline sale has been saved to the server.', timer: 3000, showConfirmButton: false });
                }
            });
        }
    </script>
@endpush
