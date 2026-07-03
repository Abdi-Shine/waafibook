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
            <div class="cart-header">
                <div class="cart-title">
                    <i class="bi bi-cart"></i> Current Order
                </div>
            </div>

            <div class="customer-info">
                <select class="customer-input mb-2" id="customer_id">
                    <option value="">Walk-in Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <select class="customer-input" id="branch_id">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ Auth::user()->getAssignedBranchId() == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <select class="customer-input" id="account_id">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ $account->id == $defaultPaymentAccountId ? 'selected' : '' }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <p class="font-bold">Your cart is empty</p>
                    <p class="text-sm">Click products to add them</p>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">0.00 {{ $company->currency ?? 'SAR' }}</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="totalAmount">0.00 {{ $company->currency ?? 'SAR' }}</span>
                </div>
                <div class="payment-buttons">
                    <button class="btn btn-pos btn-complete" onclick="processPayment()">
                        <i class="bi bi-check-circle"></i> COMPLETE PAYMENT
                    </button>
                    <button class="btn btn-sm btn-outline-danger mt-2 pos-clear-btn" onclick="clearCart()">
                        <i class="bi bi-trash"></i> Clear Order
                    </button>
                </div>
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

@push('styles')
<style>
    .swal-pos-popup  { border-radius: 20px !important; padding: 28px 24px 20px !important; max-width: 400px !important; }
    .swal-pos-confirm { background: #99CC33 !important; color: #004161 !important; font-weight: 800 !important; font-size: 14px !important; border-radius: 10px !important; padding: 10px 22px !important; }
    .swal-pos-cancel  { background: #f1f5f9 !important; color: #64748b !important; font-weight: 700 !important; font-size: 14px !important; border-radius: 10px !important; padding: 10px 22px !important; }
    .swal-pos-confirm:hover { background: #8bb82e !important; }
    .swal-pos-cancel:hover  { background: #e2e8f0 !important; color: #334155 !important; }
    .swal2-actions { gap: 10px !important; margin-top: 4px !important; }
</style>
@endpush

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
                container.innerHTML = `<div class="empty-cart"><i class="bi bi-cart-x"></i><p>Your cart is empty</p></div>`;
            } else {
                container.innerHTML = cart.map(i => `
                    <div class="cart-item">
                        <div class="item-header">
                            <span class="item-name">${i.name}</span>
                            <button class="item-remove" onclick="removeFromCart(${i.id})"><i class="bi bi-trash"></i></button>
                        </div>
                        <div class="item-details">
                            <div class="item-quantity">
                                <button class="qty-btn" onclick="updateQuantity(${i.id}, -1)">-</button>
                                <span class="qty-display">${i.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity(${i.id}, 1)">+</button>
                            </div>
                            <input type="number" class="item-price-input" min="0" step="0.01"
                                   value="${(i.price * i.quantity).toFixed(2)}"
                                   onchange="updateItemAmount(${i.id}, this.value)"
                                   onclick="this.select()">
                        </div>
                    </div>
                `).join('');
            }
            const sub = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const total = sub;
            document.getElementById('subtotal').innerText = sub.toFixed(2) + ' ' + CURRENCY;
            document.getElementById('totalAmount').innerText = total.toFixed(2) + ' ' + CURRENCY;

            // Sync mobile elements
            const totalQty = cart.reduce((s, i) => s + i.quantity, 0);
            const badge = document.getElementById('cartBadge');
            const mobileTotal = document.getElementById('mobileTotalAmt');
            if (badge) { badge.textContent = totalQty; badge.style.display = totalQty > 0 ? 'inline-flex' : 'none'; }
            if (mobileTotal) mobileTotal.textContent = total.toFixed(2) + ' ' + CURRENCY;
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
            if (cart.length === 0) return Swal.fire({
                icon: 'warning',
                title: 'Cart is Empty',
                text: 'Please add at least one product before checking out.',
                confirmButtonColor: '#004161',
                confirmButtonText: 'Got it'
            });

            const total    = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const itemCount = cart.reduce((s, i) => s + i.quantity, 0);
            const itemRows  = cart.map(i => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:13px;color:#334155;font-weight:600;text-align:left;max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${i.name}</span>
                    <span style="font-size:12px;color:#64748b;margin:0 8px;">x${i.quantity}</span>
                    <span style="font-size:13px;font-weight:800;color:#004161;">${(i.price * i.quantity).toFixed(2)} ${CURRENCY}</span>
                </div>`).join('');

            Swal.fire({
                html: `
                    <div style="text-align:center;padding:4px 0 16px;">
                        <div style="width:56px;height:56px;background:linear-gradient(135deg,#004161,#006a9e);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 8px 20px rgba(0,65,97,0.25);">
                            <svg width="26" height="26" fill="none" stroke="#99CC33" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                        </div>
                        <h2 style="font-size:20px;font-weight:900;color:#004161;margin:0 0 2px;">Order Summary</h2>
                        <p style="font-size:12px;color:#94a3b8;margin:0 0 16px;">${itemCount} item${itemCount !== 1 ? 's' : ''} in cart</p>

                        <div style="background:#f8fafc;border-radius:12px;padding:12px 16px;margin-bottom:16px;text-align:left;">
                            ${itemRows}
                        </div>

                        <div style="background:#004161;border-radius:12px;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:1px;">Total Due</span>
                            <span style="font-size:22px;font-weight:900;color:#99CC33;">${total.toFixed(2)} ${CURRENCY}</span>
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check-circle-fill"></i>&nbsp; Confirm & Pay',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#99CC33',
                cancelButtonColor: '#e2e8f0',
                customClass: {
                    confirmButton: 'swal-pos-confirm',
                    cancelButton:  'swal-pos-cancel',
                    popup:         'swal-pos-popup'
                },
                showClass:  { popup: 'animate__animated animate__fadeInDown animate__faster' },
                hideClass:  { popup: 'animate__animated animate__fadeOutUp animate__faster' },
            }).then(res => {
                if (res.isConfirmed) {
                    Swal.showLoading();
                    const data = {
                        _token: CSRF_TOKEN,
                        customer_id: document.getElementById('customer_id').value,
                        branch_id: document.getElementById('branch_id').value,
                        payment_account_id: document.getElementById('account_id').value,
                        payment_method: 'Cash',
                        invoice_date: new Date().toISOString().split('T')[0],
                        due_date: new Date().toISOString().split('T')[0],
                        paid_amount: total,
                        tax: 0,
                        items: cart.map(i => ({
                            product_id: i.id,
                            product_name: i.name,
                            product_code: i.code,
                            quantity: i.quantity,
                            unit_price: i.price,
                            unit: 'Piece'
                        }))
                    };

                    fetch('{{ route("sales.invoice.store") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire('Success', 'Transaction Complete', 'success').then(() => {
                                // Deduct sold quantities from local stock so cards refresh immediately
                                cart.forEach(item => {
                                    const p = products.find(prod => prod.id === item.id);
                                    if (p) p.stocks_sum_quantity = Math.max(0, (p.stocks_sum_quantity || 0) - item.quantity);
                                });
                                cart = [];
                                updateCart();
                                saveCartToStorage();
                                document.getElementById('customer_id').value = '';
                                displayProducts(products);
                                switchPosTab('products');
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
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
        // Re-initialize when browser serves page from bfcache (back/forward navigation)
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                displayProducts(products);
                loadCartFromStorage();
            }
        });

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
