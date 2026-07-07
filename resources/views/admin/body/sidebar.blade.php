<aside id="sidebar" x-data="{
       activeMenu: localStorage.getItem('sidebarActiveMenu') || '',
       toggleMenu(menu) {
           if (this.activeMenu === menu) {
               this.activeMenu = '';
           } else if (this.activeMenu.startsWith(menu.split('_')[0] + '_') && !menu.includes('_')) {
                this.activeMenu = menu;
           } else {
               this.activeMenu = menu;
           }
           localStorage.setItem('sidebarActiveMenu', this.activeMenu);
       },
       isOpen(menu) {
           return this.activeMenu === menu || this.activeMenu.startsWith(menu + '_');
       }
   }"
    class="fixed top-0 left-0 w-[260px] h-screen bg-primary z-50 transition-transform duration-300 lg:translate-x-0 -translate-x-full overflow-y-auto shadow-2xl border-r border-white/5">

    <!-- Brand (fixed: always the software owner, never the tenant's own company) -->
    <div class="sticky top-0 bg-primary/95 backdrop-blur-sm z-10 px-6 py-6 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg overflow-hidden p-1">
                @include('partials.logo_svg', ['width' => 36, 'height' => 36])
            </div>
            <div>
                <h4 class="text-white text-lg font-bold tracking-tight">Waafi Book</h4>
                <p class="text-accent text-[10px] font-bold uppercase tracking-widest">Enterprise POS</p>
            </div>
        </div>
    </div>

    <!-- Menu Sections -->
    @php
        $__planLevel = $currentPlanLevel ?? 3;
        // Badge helper — shows a green lock badge when a feature is inaccessible.
        // required=1 → any paid plan (locked when trial expired)
        // required=2 → Business plan or higher
        // required=3 → Enterprise plan only
        $__isRestricted = $subscriptionRestricted ?? false;
        $__badge = function(int $required) use ($__planLevel, $__isRestricted): string {
            $locked = $__isRestricted ? ($required >= 1) : ($__planLevel < $required);
            if (!$locked) return '';
            return '<span style="display:inline-flex;align-items:center;gap:3px;background:rgba(153,204,51,.15);color:#99CC33;font-size:.58rem;font-weight:700;border-radius:20px;padding:2px 8px;margin-left:6px;border:1px solid rgba(153,204,51,.3);white-space:nowrap;vertical-align:middle;"><i class="bi bi-lock-fill" style="font-size:.5rem;"></i> Locked</span>';
        };
    @endphp
    <div class="px-4 py-4 space-y-1">

        {{-- Tenant-only modules: Super Admin has no company_id, so none of these
        business pages (Cash In Hand, Sales & POS, etc.) apply to them —
        they manage the platform via Subscribers below instead. --}}
        @unless(is_null(Auth::user()->company_id))

            <!-- 1. Company Dashboard -->
            @if(Auth::user()->hasPermission('Dashboard'))
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full {{ Route::currentRouteName() == 'dashboard' ? 'bg-white/5 text-white' : '' }}">
                    <i class="bi bi-speedometer2 text-lg"></i>
                    <span>Dashboard</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('Parties'))
                <div class="space-y-1">
                    <button @click="toggleMenu('parties')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('parties') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-people text-lg"></i>
                        <span>Parties</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('parties') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('parties')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('customer.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Customers</a>
                        <a href="{{ route('supplier.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Suppliers</a>
                    </div>
                </div>
            @endif
            <!-- 2. Branch & Store Management -->
            @if(Auth::user()->hasPermission('Branch & Store') && app(\App\Services\FeatureService::class)->isEnabled('multibranch'))
                <div class="space-y-1">
                    <button @click="toggleMenu('branch')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('branch') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-building text-lg"></i>
                        <span>Branch & Store</span>
                        {!! $__badge(3) !!}
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('branch') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('branch')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('branches-view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('branches-view') ? 'text-white bg-white/10' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Branches</a>
                    </div>
                </div>
            @endif

            <!-- 3. Inventory Management -->
            @if(Auth::user()->hasPermission('Product'))
                <div class="space-y-1">
                    <button @click="toggleMenu('inventory')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('inventory') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-box-seam text-lg"></i>
                        <span>Product</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('inventory') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('inventory')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('product.index') }}"
                           onclick="if(window.innerWidth<1024){event.preventDefault();window.location='{{ route('product.ledger') }}?mobile=1';}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('product.index') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Product List
                        </a>
                        <a href="{{ route('product.ledger') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('product.ledger') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Item Ledger
                        </a>
                        <a href="{{ route('low-stock.view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Low Stock Alerts</a>
                    </div>
                </div>
            @endif

            <!-- 4. Procurement -->
            @if(Auth::user()->hasPermission('Purchase'))
                <div class="space-y-1">
                    <button @click="toggleMenu('procurement')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('procurement') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-cart-check text-lg"></i>
                        <span>Purchase</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('procurement') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('procurement')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('purchase.order.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('purchase.order.index') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Purchase Order {!! $__badge(2) !!}</a>
                        <a href="{{ route('purchase.bill') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('purchase.bill') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Purchase Bill {!! $__badge(1) !!}</a>
                        <a href="{{ route('purchase.expense') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('purchase.expense') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Direct Expenses {!! $__badge(1) !!}</a>
                        <a href="{{ route('purchase.returns') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('purchase.returns') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Purchase Returns {!! $__badge(2) !!}</a>
                        <a href="{{ route('view_payment_out') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('view_payment_out') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Supplier Payment {!! $__badge(1) !!}</a>
                    </div>
                </div>
            @endif

            <!-- 5. Sales & POS -->
            @if(Auth::user()->hasPermission('Sales & POS'))
                <div class="space-y-1">
                    <button @click="toggleMenu('sales')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('sales') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-cash-stack text-lg"></i>
                        <span>Sales & POS</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('sales') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('sales')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('sales.invoice.view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('sales.invoice.*') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Sales {!! $__badge(1) !!}</a>
                        <a href="{{ route('sales.pos.view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('sales.pos.*') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> POS Terminal {!! $__badge(1) !!}</a>
                        <a href="{{ route('view_payment_in') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('view_payment_in') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Customer Payment {!! $__badge(1) !!}</a>
                        <a href="{{ route('sales.return.view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('sales.return.*') ? 'text-white bg-white/5' : '' }}"><i
                                class="bi bi-plus text-lg"></i> Sale Return / Credit Note {!! $__badge(2) !!}</a>
                    </div>
                </div>
            @endif
            <!-- 6. Expenses & Payroll -->
            @if(Auth::user()->hasPermission('Expenses'))
                <div class="space-y-1">
                    <button @click="toggleMenu('users')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('users') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-person-badge text-lg"></i>
                        <span>Expenses</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('users') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('users')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('expenses_view_all') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Expenses {!! $__badge(1) !!}</a>
                        <a href="{{ route('payroll.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Salary List {!! $__badge(2) !!}</a>
                        <a href="{{ route('loan.view') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Loans {!! $__badge(2) !!}</a>
                    </div>
                </div>
            @endif
            <!-- 6. Accounting & Finance -->
            @if(Auth::user()->hasPermission('Accounting'))
                <div class="space-y-1">
                    <button @click="toggleMenu('finance')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('finance') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-bank text-lg"></i>
                        <span>Accounting</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('finance') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('finance')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('account_management.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Accounts {!! $__badge(1) !!}</a>
                        <a href="{{ route('cash_in_hand.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Cash In Hand {!! $__badge(1) !!}</a>
                    </div>
                </div>
            @endif

            <!-- 7. Advanced Reports & Intelligence -->
            @if(Auth::user()->hasPermission('Reports'))
                <div class="space-y-1">
                    <button @click="toggleMenu('reports')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('reports') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-graph-up-arrow text-lg"></i>
                        <span>Reports</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('reports') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('reports')" x-collapse class="pl-4 space-y-0.5">
                        {{-- Financial --}}
                        <a href="{{ route('reports.profit_loss') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.profit_loss') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Profit & Loss</a>
                        <a href="{{ route('reports.bill_wise_profit') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.bill_wise_profit') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Bill Wise Profit</a>
                        <a href="{{ route('reports.party_wise_profit_loss') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.party_wise_profit_loss') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Party Profit & Loss</a>
                        <a href="{{ route('reports.item_wise_profit_loss') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.item_wise_profit_loss') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Product Profit & Loss</a>
                        <a href="{{ route('reports.balance_sheet') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.balance_sheet') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Balance Sheet</a>
                        <a href="{{ route('reports.cash_flow') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.cash_flow') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Cash Flow</a>

                        {{-- Sales --}}
                        <a href="{{ route('reports.sales') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.sales') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Sales Report</a>
                        {{-- Purchase --}}
                        <a href="{{ route('reports.purchases') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.purchases') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Purchase Analysis</a>
                        <a href="{{ route('reports.expense_item_report') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.expense_item_report') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Expense Items</a>

                        {{-- Inventory --}}

                        <a href="{{ route('reports.summary_stock') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.summary_stock') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Stock Summary</a>
                        <a href="{{ route('reports.stock_details') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.stock_details') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Stock Details</a>

                        {{-- Party --}}
                        <a href="{{ route('reports.party_statement') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.party_statement') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Party Statement</a>
                        <a href="{{ route('reports.sales_purchase_by_party') }}"
                            class="flex items-center gap-3 pl-8 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('reports.sales_purchase_by_party') ? 'text-white bg-white/5' : '' }}">
                            <i class="bi bi-plus text-lg"></i> Party Trade</a>
                    </div>
                </div>
            @endif
            <!-- 9. System Administration -->
            @if(Auth::user()->hasPermission('System Admin'))
                <div class="space-y-1">
                    <button @click="toggleMenu('admin')"
                        class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="isOpen('admin') ? 'bg-white/5 text-white' : ''">
                        <i class="bi bi-gear text-lg"></i>
                        <span>System Admin</span>
                        <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                            :class="isOpen('admin') ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="isOpen('admin')" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                        class="space-y-1 overflow-hidden transition-all duration-300">
                        <a href="{{ route('company-settings') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Company Settings</a>
                        <a href="{{ route('capital-deposit') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Share Holders</a>
                        <a href="{{ route('employee.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Employee</a>
                        <a href="{{ route('employee.assign-login') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Access Login</a>
                        <a href="{{ route('role.index') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Role & Permission</a>
                        <a href="{{ route('backup-restore') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Backup & Restore</a>
                        <a href="{{ route('audit-logs') }}"
                            class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200"><i
                                class="bi bi-plus text-lg"></i> Audit Logs</a>
                    </div>
                </div>
            @endif

        @endunless

        <!-- 10. Subscribers -->
        @if(Auth::user()->hasPermission('subscribers'))
            <div class="space-y-1">
                <button @click="toggleMenu('subscribers_menu')"
                    class="flex items-center gap-3 px-4 py-3 text-white/70 hover:bg-white/5 hover:text-white rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                    :class="isOpen('subscribers_menu') ? 'bg-white/5 text-white' : ''">
                    <i class="bi bi-people-fill text-lg"></i>
                    <span>Subscribers</span>
                    <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                        :class="isOpen('subscribers_menu') ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="isOpen('subscribers_menu')" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-[500px]"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 max-h-[500px]" x-transition:leave-end="opacity-0 max-h-0"
                    class="space-y-1 overflow-hidden transition-all duration-300">
                    <a href="{{ route('subscribers.subscriptions.index') }}"
                        class="flex items-center gap-3 pl-12 pr-4 py-2 text-white/50 hover:text-white text-[13px] font-medium transition-all duration-200 {{ Route::is('subscribers.subscriptions.index') ? 'text-white bg-white/10' : '' }}"><i
                            class="bi bi-plus text-lg"></i> Active Subs</a>
                </div>
            </div>
        @endif

    </div>

    <!-- Logout -->
    <div class="px-4 py-4 border-t border-white/5 bg-primary/50">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-4 py-2 text-accent hover:text-white hover:bg-white/5 rounded-xl font-bold text-[14px] transition-all duration-200 cursor-pointer">
                <i class="bi bi-power text-lg"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>