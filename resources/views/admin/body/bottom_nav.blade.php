@auth
@unless(is_null(Auth::user()->company_id))
<div x-data="{ fabOpen: false }" @click.away="fabOpen = false">

    {{-- FAB Quick-Add Sheet --}}
    <div x-show="fabOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         x-cloak
         class="fixed bottom-[72px] left-1/2 -translate-x-1/2 z-50 bg-white rounded-2xl shadow-2xl border border-gray-100 p-3 w-64 lg:hidden">
        <p class="text-[10px] font-black text-text-secondary uppercase tracking-widest px-2 pb-2">Quick Add</p>
        <a href="{{ route('sales.invoice.create') }}" @click="fabOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-background transition-colors text-sm font-semibold text-text-primary">
            <span class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-file-earmark-text text-primary text-sm"></i>
            </span>
            New Invoice
        </a>
        <a href="{{ route('purchase.bill.create') }}" @click="fabOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-background transition-colors text-sm font-semibold text-text-primary">
            <span class="w-8 h-8 bg-accent/10 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-clipboard text-accent text-sm"></i>
            </span>
            New Bill
        </a>
        <a href="{{ route('view_payment_in') }}" @click="fabOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-background transition-colors text-sm font-semibold text-text-primary">
            <span class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-arrow-down-circle text-primary text-sm"></i>
            </span>
            Record Payment
        </a>
        <a href="{{ route('expenses_view_all') }}" @click="fabOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-background transition-colors text-sm font-semibold text-text-primary">
            <span class="w-8 h-8 bg-accent/10 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-cash text-accent text-sm"></i>
            </span>
            New Expense
        </a>
        <a href="{{ route('sales.pos.view') }}" @click="fabOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-background transition-colors text-sm font-semibold text-text-primary">
            <span class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-display text-primary text-sm"></i>
            </span>
            POS Terminal
        </a>
    </div>

    {{-- Backdrop for FAB sheet --}}
    <div x-show="fabOpen" x-cloak @click="fabOpen=false"
         class="fixed inset-0 z-40 lg:hidden"></div>

    {{-- Bottom Navigation Bar --}}
    <nav class="bottom-nav lg:hidden">
        {{-- Home --}}
        <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ Route::currentRouteName() === 'dashboard' ? 'bottom-nav-active' : '' }}">
            <i class="bi bi-house{{ Route::currentRouteName() === 'dashboard' ? '-fill' : '' }} bottom-nav-icon"></i>
            <span class="bottom-nav-label">Home</span>
        </a>

        {{-- Sales --}}
        <a href="{{ route('sales.invoice.view') }}" class="bottom-nav-item {{ request()->routeIs('sales.invoice.*') ? 'bottom-nav-active' : '' }}">
            <i class="bi bi-bag{{ request()->routeIs('sales.invoice.*') ? '-fill' : '' }} bottom-nav-icon"></i>
            <span class="bottom-nav-label">Sales</span>
        </a>

        {{-- FAB --}}
        <button @click.stop="fabOpen = !fabOpen" class="bottom-nav-fab" aria-label="Quick Add">
            <i class="bi bottom-nav-fab-icon" :class="fabOpen ? 'bi-x' : 'bi-plus'"></i>
        </button>

        {{-- Reports --}}
        <a href="{{ route('all_reports') }}" class="bottom-nav-item {{ request()->routeIs('all_reports') ? 'bottom-nav-active' : '' }}">
            <i class="bi bi-bar-chart{{ request()->routeIs('all_reports') ? '-fill' : '' }} bottom-nav-icon"></i>
            <span class="bottom-nav-label">Reports</span>
        </a>

        {{-- Menu --}}
        <button onclick="openSidebar()" class="bottom-nav-item">
            <i class="bi bi-list bottom-nav-icon"></i>
            <span class="bottom-nav-label">Menu</span>
        </button>
    </nav>
</div>
@endunless
@endauth
