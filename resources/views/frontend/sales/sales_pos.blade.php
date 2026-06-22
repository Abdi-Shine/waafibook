@extends('admin.blank_master')


@section('admin')
    <div class="pos-container">
        <!-- Left Section - Products -->
        <div class="products-section">
            <div class="search-bar">
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
        <div class="cart-section">
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
                            <div class="item-price">${(i.price * i.quantity).toFixed(2)}</div>
                        </div>
                    </div>
                `).join('');
            }
            const sub = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const total = sub;
            document.getElementById('subtotal').innerText = sub.toFixed(2) + ' ' + CURRENCY;
            document.getElementById('totalAmount').innerText = total.toFixed(2) + ' ' + CURRENCY;
        }

        function processPayment() {
            if (cart.length === 0) return Swal.fire('Error', 'Cart is empty', 'error');

            const total = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            
            Swal.fire({
                title: 'Confirm Checkout',
                text: `Total: ${total.toFixed(2)} ${CURRENCY}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm'
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
                            Swal.fire('Success', 'Transaction Complete', 'success').then(() => window.location.reload());
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
                    if (r.isConfirmed) { cart = []; updateCart(); }
                });
            }
        }
    </script>
@endpush
