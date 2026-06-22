@extends('admin.admin_master')
@section('page_title', 'Share Holders')



@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="{ 
    activeTab: 'partners',
    activeModal: null,
    editingShareholder: null,

    openCreateModal() {
        this.activeModal = 'deposit-modal';
        document.getElementById('depositForm').reset();
    },

    openShareholderModal(sh = null) {
        this.editingShareholder = sh;
        this.activeModal = 'shareholder-modal';
        if (sh) {
            document.getElementById('sh-form').action = `/shareholder/update/${sh.id}`;
            document.getElementById('sh-method').value = 'PUT';
            document.getElementById('sh-name').value = sh.name;
            document.getElementById('sh-phone').value = sh.phone;
            document.getElementById('sh-email').value = sh.email;
            document.getElementById('sh-amount').value = sh.share_amount;
        } else {
            document.getElementById('sh-form').action = `{{ route('shareholder.store') }}`;
            document.getElementById('sh-method').value = 'POST';
            document.getElementById('sh-form').reset();
        }
    }
}">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Equity & Shareholder Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openShareholderModal()"
                class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] hover:bg-primary/90 hover:-translate-y-0.5 transition-all shadow-md text-sm group">
                <i class="bi bi-person-plus text-lg group-hover:scale-110 transition-transform duration-300"></i>
                New Shareholder
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Capital -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Total Capital</p>
                <h3 class="text-[24px] font-black text-primary">{{ $companyCurrency }} {{ number_format($totalCapital, 2) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1"><i
                        class="bi bi-check-circle-fill text-[10px]"></i> Primary Asset</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-safe text-lg"></i>
            </div>
        </div>
        
        <!-- FY Deposits -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">FY Deposits</p>
                <h3 class="text-[24px] font-black text-primary">{{ $companyCurrency }} {{ number_format($thisYearCapital, 2) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1"><i
                        class="bi bi-arrow-up text-[10px]"></i> CY {{ date('Y') }} Tracker</p>
            </div>
            <div class="w-11 h-11 bg-accent/20 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-graph-up-arrow text-lg"></i>
            </div>
        </div>

        <!-- Active Partners -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Active Partners</p>
                <h3 class="text-[24px] font-black text-primary">{{ $activeShareholdersCount }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5">Registered Shareholders</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-people text-lg"></i>
            </div>
        </div>

        <!-- Equity Structure -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Equity Structure</p>
                <h3 class="text-[24px] font-black text-primary">100%</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1"><i
                        class="bi bi-pie-chart-fill text-[10px]"></i> Balanced</p>
            </div>
            <div class="w-11 h-11 bg-accent/20 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-pie-chart text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Navigation Section -->
    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6" x-data="{ searchTerm: '' }">

        <!-- Filters -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" placeholder="Search shareholders..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>
        </div>

        <!-- Table Title -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Shareholders List</h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Shareholder</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Contact</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Email</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Equity Stake</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Invested Capital</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shareholders as $sh)
                        @php 
                            $pct = $totalCapital > 0 ? ($sh->share_amount / $totalCapital) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                            x-show="searchTerm === '' || '{{ strtolower($sh->name) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($sh->phone ?? '') }}'.includes(searchTerm.toLowerCase())">
                            <td class="px-5 py-4 text-xs font-bold text-primary-dark text-center leading-tight">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-primary-dark leading-tight">{{ $sh->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold text-primary-dark leading-tight">
                                    {{ $sh->phone ?: '-' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold text-primary-dark leading-tight">
                                    {{ $sh->email ?? '-' }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs font-bold text-primary-dark leading-tight">{{ number_format($pct, 1) }}%</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs font-bold text-primary-dark leading-tight">
                                    {{ $companyCurrency }} {{ number_format($sh->share_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('shareholder.statement', $sh->id) }}" target="_blank"
                                        class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm"
                                        title="View Statement">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                    <button @click="openShareholderModal(@js($sh))"
                                        class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('shareholder.delete', $sh->id) }}" method="POST" id="delete-sh-{{ $sh->id }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDelete('delete-sh-{{ $sh->id }}', 'Remove shareholder?', 'Registry history will be preserved.')"
                                            class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <i class="bi bi-people text-4xl mb-3 opacity-20"></i>
                                    <p class="text-sm font-medium">No shareholders found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Empty Pagination Area (Matching UI structure) -->
        @if($shareholders->count() > 0)
        <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                Showing 1 to {{ $shareholders->count() }} of {{ $shareholders->count() }} entries
            </span>
            <div class="flex items-center gap-1">
                <button class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 bg-gray-50" disabled><i class="bi bi-chevron-left text-[10px]"></i></button>
                <button class="w-7 h-7 flex items-center justify-center rounded-md bg-primary-dark text-white font-bold text-xs shadow-sm">1</button>
                <button class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 bg-gray-50" disabled><i class="bi bi-chevron-right text-[10px]"></i></button>
            </div>
        </div>
        @endif
    </div>

    <!-- SHAREHOLDER MODAL -->
    <div x-show="activeModal === 'shareholder-modal'" x-cloak 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl overflow-hidden border border-white/20 shadow-2xl animate-in zoom-in-95 duration-200" @click.away="activeModal = null">
            
            <!-- Modal Header (Branding Model) -->
            <div class="px-7 py-7 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-13 h-13 bg-white/10 border border-white/10 rounded-2xl flex items-center justify-center text-white text-2xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-[22px] font-black text-white tracking-tight" x-text="editingShareholder ? 'Edit Shareholder' : 'Add Shareholder'"></h2>
                            <p class="text-xs text-primary font-bold uppercase tracking-[0.1em] mt-0.5">Equity Holder Registry</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-9 h-9 bg-white/10 border border-white/10 text-white rounded-xl hover:bg-white/20 transition-all flex items-center justify-center shadow-lg backdrop-blur-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
                <!-- Premium Background Effect -->
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-accent/20 rounded-full blur-3xl"></div>
                <div class="absolute -left-8 -top-8 w-32 h-32 bg-primary-dark/50 rounded-full blur-3xl"></div>
            </div>

            <div class="p-8">
                <form id="sh-form" action="" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="sh-method" value="POST">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Row 1: Primary Contact (3 Inputs) -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Shareholder Name <span class="text-primary">*</span></label>
                            <input type="text" name="name" id="sh-name" required placeholder="Full Name" 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Phone</label>
                            <input type="text" name="phone" id="sh-phone" placeholder="Phone Number" 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Email</label>
                            <input type="email" name="email" id="sh-email" placeholder="Email Address" 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>

                        <!-- Row 2: Location, Status & Info (3 Inputs) -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Address</label>
                            <input type="text" name="address" id="sh-address" placeholder="Physical Address" 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Status</label>
                            <select name="status" id="sh-status" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all appearance-none cursor-pointer">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <!-- Conditional 3rd slot for Row 2 -->
                        <div class="space-y-1.5" x-show="editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Internal Notes</label>
                            <input type="text" name="notes" placeholder="Reference notes..." 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>
                        <div class="space-y-1.5" x-show="!editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Payment Method</label>
                            <select name="payment_method" :required="!editingShareholder" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all appearance-none cursor-pointer">
                                <option value="Cash Deposit" selected>Cash Deposit</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                            </select>
                        </div>

                        <!-- Row 3: Investment Details (Only when Adding - 3 Inputs) -->
                        <div class="space-y-1.5" x-show="!editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Initial Investment <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" name="share_amount" id="sh-amount" value="0" :required="!editingShareholder"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none transition-all pr-10 text-center">
                                <i class="bi bi-currency-dollar absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5" x-show="!editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Credit Account (Equity)</label>
                            <select name="credit_account_id" :required="!editingShareholder" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all appearance-none cursor-pointer">
                                @foreach($allAccounts as $ba)
                                    <option value="{{ $ba->id }}" {{ $ba->code == '3011' ? 'selected' : '' }}>{{ $ba->code }} - {{ $ba->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5" x-show="!editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Entry Date</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" :required="!editingShareholder"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-bold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>

                        <!-- Row 4: Notes (Only when Adding) -->
                        <div class="space-y-1.5 col-span-3" x-show="!editingShareholder">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-[0.12em]">Internal Reference Notes</label>
                            <input type="text" name="notes" placeholder="Extra tracking info..." 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold text-gray-700 focus:bg-white outline-none focus:border-primary transition-all">
                        </div>
                    </div>
                </form>
            </div>
            <!-- Modal Footer (Design Branding Model) -->
            <div class="px-8 py-5 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between shrink-0">
                <button type="button" @click="activeModal = null" 
                    class="px-8 py-2.5 bg-white border border-gray-200 text-gray-500 font-bold rounded-xl hover:bg-gray-50 hover:text-gray-700 hover:border-gray-300 transition-all text-[10px] shadow-sm uppercase tracking-[0.12em]">
                    Cancel
                </button>
                <button type="submit" form="sh-form" 
                    class="flex items-center gap-2.5 px-10 py-2.5 bg-accent text-primary font-bold rounded-xl hover:opacity-90 hover:-translate-y-0.5 transition-all text-[10px] shadow-lg shadow-accent/15 uppercase tracking-[0.14em]">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span>Save Partner</span>
                </button>
            </div>
        </div>
    </div>

    <!-- CAPITAL DEPOSIT MODAL -->
    <div x-show="activeModal === 'deposit-modal'" x-cloak 
         class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-5 bg-slate-900/50 backdrop-blur-sm duration-300">
        <div class="bg-white rounded-[1.25rem] w-full max-w-xl shadow-2xl flex flex-col modal-max-h-95" @click.away="activeModal = null">
            
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-lg shadow-inner">
                            <i class="bi bi-safe-fill"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-base font-bold text-white tracking-tight">Record Contribution</h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Post automated ledger entry</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body — Scrollable -->
            <div class="overflow-y-auto flex-1 px-5 py-4 custom-scrollbar">
                <form id="depositForm" action="{{ route('capital-deposit.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3" x-data="{ selectedBalance: 0 }">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Shareholder</label>
                                <select name="shareholder_id" required 
                                    @change="selectedBalance = $event.target.options[$event.target.selectedIndex].dataset.balance || 0"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold focus:bg-white outline-none cursor-pointer appearance-none">
                                    <option value="" data-balance="0">-- Choose Partner --</option>
                                    @foreach($shareholders as $sh)
                                        @php $pct = $totalCapital > 0 ? ($sh->share_amount / $totalCapital) * 100 : 0; @endphp
                                        <option value="{{ $sh->id }}" data-balance="{{ $sh->share_amount }}">
                                            {{ $sh->name }} ({{ number_format($pct, 2) }}%)
                                        </option>
                                    @endforeach
                                </select>
                                <div x-show="selectedBalance > 0" class="text-[9px] font-bold text-primary uppercase mt-1">
                                    Stake: <span x-text="'$ ' + new Intl.NumberFormat().format(selectedBalance)"></span>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Entry Date</label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" required 
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-bold outline-none focus:bg-white transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Amount ({{ $companyCurrency }})</label>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00" 
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-black text-primary text-center focus:bg-white transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Credit Account (Equity/Liability)</label>
                                <select name="credit_account_id" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold outline-none cursor-pointer focus:bg-white">
                                    @foreach($allAccounts as $ba)
                                        <option value="{{ $ba->id }}" {{ $ba->code == '3011' ? 'selected' : '' }}>
                                            {{ $ba->code }} - {{ $ba->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Payment Method</label>
                                <select name="payment_method" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold outline-none cursor-pointer focus:bg-white">
                                    <option value="Cash Deposit" selected>Cash Deposit</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Notes</label>
                                <input type="text" name="notes" placeholder="Reference or internal notes..." class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-semibold outline-none focus:bg-white">
                            </div>
                        </div>

                        <div class="p-4 bg-primary rounded-xl space-y-3 shadow-lg shadow-primary/10">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 bg-accent rounded-full animate-pulse shadow-[0_0_8px_#99CC33]"></div>
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-[0.3em] uppercase">Accounting automation</span>
                            </div>
                            <div class="flex justify-between items-center opacity-70">
                                <span class="text-[10px] text-white font-bold uppercase tracking-wide">Debit Bank (Asset ↑)</span>
                                <i class="bi bi-check-circle-fill text-accent text-xs"></i>
                            </div>
                            <div class="flex justify-between items-center opacity-70">
                                <span class="text-[10px] text-white font-bold uppercase tracking-wide">Credit Share Capital (Equity ↑)</span>
                                <i class="bi bi-check-circle-fill text-accent text-xs"></i>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Modal Footer — Fixed -->
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0 rounded-b-[1.25rem]">
                <button type="button" @click="activeModal = null" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all text-[13px]">Cancel</button>
                <button type="submit" form="depositForm" class="flex items-center gap-2 px-8 py-2.5 btn-brand-gradient text-white font-bold rounded-[0.5rem] hover:opacity-90 transition-all text-[13px] shadow-sm uppercase tracking-widest">
                    <i class="bi bi-shield-lock text-base"></i>
                    <span>Authorize Post</span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function confirmDelete(formId, title, text) {
    const form = document.getElementById(formId);
    const label = (title || 'this record').replace(/^(Delete|Remove)\s+/i, '').replace(/\?$/, '');
    deleteRecordWithPassword(form.action, label, {
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this!"
    });
}
</script>
@endpush

@endsection


